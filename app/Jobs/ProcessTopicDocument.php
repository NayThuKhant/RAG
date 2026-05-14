<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Models\TopicDocument;
use App\Models\TopicDocumentChunk;
use App\Parsers\ParserManager;
use App\Services\TextTransformationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Exceptions\FailoverableException;
use Throwable;

class ProcessTopicDocument implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public TopicDocument $document) {}

    /**
     * @throws Throwable
     * @throws FailoverableException
     */
    public function handle(): void
    {
        $this->document->update(['status' => DocumentStatus::Processing]);
        $this->document->chunks()->delete();

        /** @var ParserManager $parserManager */
        $parserManager = app(ParserManager::class);
        /** @var TextTransformationService $transformer */
        $transformer = app(TextTransformationService::class);

        $processed = $parserManager->parse($this->document);

        $this->document->update(['extracted_text' => $processed->markdown]);

        $chunks = $transformer->chunk($processed->markdown);
        $stored = 0;

        foreach (array_chunk($chunks, 5) as $batch) {
            $embeddingResponse = Embeddings::for($batch)->generate();

            foreach ($embeddingResponse->embeddings as $index => $embedding) {
                TopicDocumentChunk::create([
                    'topic_document_id' => $this->document->id,
                    'content' => $batch[$index],
                    'embedding' => $embedding,
                ]);
                $stored++;
            }
        }

        $this->document->update([
            'status' => DocumentStatus::Ready,
            'chunk_count' => $stored,
            'metadata' => array_merge($this->document->metadata ?? [], $processed->metadata),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $this->document->chunks()->delete();
        $this->document->update([
            'status' => DocumentStatus::Failed,
            'error_message' => $exception->getMessage(),
        ]);
    }
}
