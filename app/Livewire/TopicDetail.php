<?php

namespace App\Livewire;

use App\Ai\Agents\TopicChatAgent;
use App\Enums\DocumentStatus;
use App\Enums\MessageRole;
use App\Models\Topic;
use App\Models\TopicDocument;
use App\Models\TopicMessage;
use App\Parsers\ParserManager;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Ai\Streaming\Events\TextDelta;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

class TopicDetail extends Component
{
    public Topic $topic;

    #[Validate('required|string|max:4000')]
    public string $message = '';

    public string $pendingMessage = '';

    public bool $isStreaming = false;

    #[Url(as: 'view')]
    public ?int $viewingDocumentId = null;

    /** @var string[] */
    public array $followUpSuggestions = [];

    public function mount(Topic $topic): void
    {
        $this->topic = $topic;

        if ($this->viewingDocumentId !== null) {
            $valid = TopicDocument::where('topic_id', $topic->id)
                ->where('id', $this->viewingDocumentId)
                ->exists();
            if (! $valid) {
                $this->viewingDocumentId = null;
            }
        }
    }

    public function sendMessage(): void
    {
        $this->validate(['message' => 'required|string|max:4000']);

        $this->pendingMessage = $this->message;
        $this->isStreaming = true;
        $this->followUpSuggestions = [];
        $this->reset('message');

        $this->js('$wire.streamResponse()');
    }

    public function sendFollowUp(string $question): void
    {
        $this->message = $question;
        $this->sendMessage();
    }

    /**
     * @throws Exception
     */
    public function streamResponse(): void
    {
        $userMessage = $this->pendingMessage;

        $agent = new TopicChatAgent($this->topic);
        $stream = $agent->stream($userMessage);

        foreach ($stream as $event) {
            if ($event instanceof TextDelta) {
                $this->streamMessage($event->delta);
            }
        }

        $rawText = trim($stream->text);
        $usage = $stream->usage;

        [$assistantText, $followUps] = $this->extractFollowUps($rawText);

        if (! $assistantText) {
            $assistantText = TopicMessage::ASSISTANT_ERROR;
            $this->streamMessage($assistantText, replace: true);
        }

        $this->pendingMessage = '';
        $this->isStreaming = false;

        $this->followUpSuggestions = $followUps;

        TopicMessage::create([
            'topic_id' => $this->topic->id,
            'role' => MessageRole::User,
            'content' => $userMessage,
        ]);

        TopicMessage::create([
            'topic_id' => $this->topic->id,
            'role' => MessageRole::Assistant,
            'content' => $assistantText,
            'input_tokens' => $usage?->promptTokens ?? 0,
            'output_tokens' => $usage?->completionTokens ?? 0,
        ]);
    }

    public function streamMessage(string $content, bool $replace = false): void
    {
        $this->stream(content: $content, replace: $replace, to: 'streamingMessage');
    }

    public function viewDocument(int $documentId): void
    {
        TopicDocument::where('topic_id', $this->topic->id)->findOrFail($documentId);
        $this->viewingDocumentId = $documentId;
    }

    public function closeDocument(): void
    {
        $this->viewingDocumentId = null;
    }

    public function clearChat(): void
    {
        $this->topic->messages()->delete();
        $this->followUpSuggestions = [];
    }

    public function hasProcessingDocuments(): bool
    {
        return $this->topic->documents()
            ->whereIn('status', [DocumentStatus::Pending, DocumentStatus::Processing])
            ->exists();
    }

    public function render(): View
    {
        $documents = $this->topic->documents()->latest()->get();

        $readyDocumentsByFilename = $documents
            ->where('status', DocumentStatus::Ready)
            ->keyBy('filename');

        $messages = $this->topic->messages()->orderBy('id')->whereNotNull('content')->where('content', '!=', '')->get()->map(function ($msg) use ($readyDocumentsByFilename) {
            $renderedContent = null;

            if ($msg->role === MessageRole::Assistant) {
                $html = Str::markdown($msg->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]);

                foreach ($readyDocumentsByFilename as $filename => $document) {
                    $escaped = e($filename);
                    $viewUrl = route('topics.show', $this->topic).'?view='.$document->id;
                    $html = str_replace(
                        $escaped,
                        '<a href="'.e($viewUrl).'" wire:navigate class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">'.$escaped.'</a>',
                        $html
                    );
                }

                $renderedContent = $html;
            }

            return (object) [
                'id' => $msg->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'renderedContent' => $renderedContent,
                'inputTokens' => $msg->input_tokens,
                'outputTokens' => $msg->output_tokens,
            ];
        });

        $viewingDocument = $this->viewingDocumentId
            ? TopicDocument::where('topic_id', $this->topic->id)->find($this->viewingDocumentId)
            : null;

        /** @var ParserManager $parserManager */
        $parserManager = app(ParserManager::class);

        $viewingContent = '';
        $viewingFileUrl = '';
        $viewingViewerMode = null;

        if ($viewingDocument) {
            $viewingViewerMode = $parserManager->viewerModeFor($viewingDocument->filename);

            if ($viewingViewerMode->isBinary()) {
                $viewingFileUrl = Storage::disk($viewingDocument->disk)->url($viewingDocument->path);
            } else {
                $viewingContent = Storage::disk($viewingDocument->disk)->get($viewingDocument->path) ?? '';
            }
        }

        $parsedBy = $viewingDocument?->metadata['parsed_by'] ?? null;
        $parsedByMetadata = $parsedBy['metadata'] ?? [];

        return view('livewire.topic-detail', [
            'documents' => $documents,
            'messages' => $messages,
            'hasProcessingDocuments' => $this->hasProcessingDocuments(),
            'viewingFilename' => $viewingDocument?->filename ?? '',
            'viewingViewerMode' => $viewingViewerMode,
            'viewingContent' => $viewingContent,
            'viewingFileUrl' => $viewingFileUrl,
            'viewingExtractedText' => $viewingDocument?->extracted_text ?? '',
            'viewingIsFailed' => $viewingDocument?->status === DocumentStatus::Failed,
            'viewingErrorMessage' => $viewingDocument?->error_message ?? '',
            'viewingParsedBy' => $parsedBy ? [
                'class' => class_basename($parsedBy['class']),
                'metadata' => $parsedByMetadata,
                'languages' => ! empty($parsedByMetadata['ocr'])
                    ? ($viewingDocument->metadata['languages'] ?? [])
                    : [],
            ] : null,
        ]);
    }

    /**
     * @return array{string, string[]}
     */
    private function extractFollowUps(string $text): array
    {
        if (preg_match('/<followups>(.*?)<\/followups>/s', $text, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);
            $followUps = is_array($decoded) ? array_values(array_slice($decoded, 0, 3)) : [];
            $text = trim(preg_replace('/<followups>.*?<\/followups>/s', '', $text));

            return [$text, $followUps];
        }

        return [$text, []];
    }
}
