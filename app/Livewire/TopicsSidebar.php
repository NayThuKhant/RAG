<?php

namespace App\Livewire;

use App\Enums\DocumentStatus;
use App\Jobs\ProcessTopicDocument;
use App\Models\Topic;
use App\Models\TopicDocument;
use App\Parsers\ParserManager;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use thiagoalessio\TesseractOCR\TesseractOCR;

class TopicsSidebar extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    public ?int $expandedTopicId = null;

    public ?int $editingTopicId = null;

    public string $editName = '';

    public string $editDescription = '';

    #[Validate('required|file|max:20480|mimes:txt,md,html,htm,pdf,csv,json,xml,docx,xlsx,jpg,jpeg,png,gif,webp')]
    public $uploadedFile = null;

    /** @var string[] */
    public array $availableOcrLanguages = [];

    /** @var string[] */
    public array $selectedOcrLanguages = [];

    public function createTopic(): void
    {
        $this->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string|max:1000']);

        Topic::create([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        $this->reset('name', 'description');
        $this->dispatch('modal-closed');
    }

    public function cancelCreateTopic(): void
    {
        $this->reset('name', 'description');
    }

    public function startEditTopic(int $topicId): void
    {
        $topic = Topic::findOrFail($topicId);
        $this->editingTopicId = $topicId;
        $this->editName = $topic->name;
        $this->editDescription = $topic->description ?? '';
    }

    public function updateTopic(): void
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editDescription' => 'nullable|string|max:1000',
        ]);

        Topic::findOrFail($this->editingTopicId)->update([
            'name' => $this->editName,
            'description' => $this->editDescription,
        ]);

        $this->reset('editingTopicId', 'editName', 'editDescription');
        $this->dispatch('modal-closed');
    }

    public function cancelEditTopic(): void
    {
        $this->reset('editingTopicId', 'editName', 'editDescription');
    }

    public function openTopicChat(int $topicId): void
    {
        $this->expandedTopicId = $topicId;
        $this->redirect(route('topics.show', $topicId), navigate: true);
    }

    public function toggleTopic(int $topicId): void
    {
        $this->expandedTopicId = $this->expandedTopicId === $topicId ? null : $topicId;
    }

    public function updatedUploadedFile(): void
    {
        $this->reset('selectedOcrLanguages');
    }

    public function uploadDocument(): void
    {
        $this->validate([
            'uploadedFile' => 'required|file|max:20480|mimes:txt,md,html,htm,pdf,csv,json,xml,docx,xlsx,jpg,jpeg,png,gif,webp',
            'selectedOcrLanguages' => 'array|max:3',
            'selectedOcrLanguages.*' => 'string',
        ]);

        $filename = $this->uploadedFile->getClientOriginalName();
        $requiresOcrLanguages = app(ParserManager::class)->requiresOcrLanguages($filename);

        if ($requiresOcrLanguages && empty($this->selectedOcrLanguages)) {
            $this->addError('selectedOcrLanguages', 'Select at least one OCR language for PDF files.');

            return;
        }

        $topic = Topic::findOrFail($this->expandedTopicId);
        $path = $this->uploadedFile->store('documents', 'public');

        $metadata = $requiresOcrLanguages
            ? ['tesseract_ocr_languages' => array_values($this->selectedOcrLanguages)]
            : null;

        $document = TopicDocument::create([
            'topic_id' => $topic->id,
            'filename' => $filename,
            'path' => $path,
            'disk' => 'public',
            'status' => DocumentStatus::Pending,
            'metadata' => $metadata,
        ]);

        ProcessTopicDocument::dispatch($document);
        $this->reset('uploadedFile', 'selectedOcrLanguages');
        $this->dispatch('modal-closed');
    }

    public function cancelUploadDocument(): void
    {
        $this->reset('uploadedFile', 'selectedOcrLanguages');
    }

    public function retryDocument(int $documentId): void
    {
        $document = TopicDocument::findOrFail($documentId);
        $document->chunks()->delete();
        $document->update([
            'status' => DocumentStatus::Pending,
            'error_message' => null,
            'chunk_count' => 0,
        ]);
        ProcessTopicDocument::dispatch($document);
    }

    public function deleteDocument(int $documentId): void
    {
        TopicDocument::findOrFail($documentId)->delete();
    }

    public function deleteTopic(int $topicId): void
    {
        Topic::findOrFail($topicId)->delete();

        if ($this->expandedTopicId === $topicId) {
            $this->expandedTopicId = null;
        }

        $currentTopicId = optional(request()->route('topic'))->id;

        if ($currentTopicId === $topicId) {
            $this->redirect(route('home'), navigate: true);
        }
    }

    public function mount(): void
    {
        $currentTopicId = optional(request()->route('topic'))->id;

        if ($currentTopicId && $this->expandedTopicId === null) {
            $this->expandedTopicId = $currentTopicId;
        }

        try {
            $this->availableOcrLanguages = (new TesseractOCR)
                ->executable(config('services.tesseract_binary'))
                ->availableLanguages();
        } catch (\Throwable) {
            $this->availableOcrLanguages = [];
        }
    }

    public function render(): View
    {
        $currentTopicId = optional(request()->route('topic'))->id;

        $topics = Topic::withCount([
            'documents',
            'documents as pending_documents_count' => fn ($q) => $q->where('status', DocumentStatus::Pending),
            'documents as processing_documents_count' => fn ($q) => $q->where('status', DocumentStatus::Processing),
            'documents as ready_documents_count' => fn ($q) => $q->where('status', DocumentStatus::Ready),
            'documents as failed_documents_count' => fn ($q) => $q->where('status', DocumentStatus::Failed),
        ])->latest()->get();

        $expandedDocuments = $this->expandedTopicId
            ? TopicDocument::where('topic_id', $this->expandedTopicId)->latest()->get()
            : collect();

        $hasProcessing = $this->expandedTopicId && $expandedDocuments->whereIn('status', [DocumentStatus::Pending, DocumentStatus::Processing])->isNotEmpty();

        $requiresOcrLanguages = $this->uploadedFile
            ? app(ParserManager::class)->requiresOcrLanguages($this->uploadedFile->getClientOriginalName())
            : false;

        return view('livewire.topics-sidebar', [
            'topics' => $topics,
            'expandedDocuments' => $expandedDocuments,
            'currentTopicId' => $currentTopicId,
            'hasProcessing' => $hasProcessing,
            'requiresOcrLanguages' => $requiresOcrLanguages,
        ]);
    }
}
