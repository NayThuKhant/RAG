<?php

namespace App\Parsers;

use App\Ai\Agents\ParseDocumentAgent;
use App\DTOs\ProcessedDocument;
use App\Models\TopicDocument;
use Laravel\Ai\Files\Document;

class AiParser implements DocumentParserInterface
{
    public function parse(TopicDocument $document, string $absolutePath): ProcessedDocument
    {
        $response = (new ParseDocumentAgent)->prompt(
            'Extract all text from this document accurately, preserving lists and sections.',
            attachments: [
                Document::fromStorage($document->path, disk: $document->disk),
            ]
        );

        return new ProcessedDocument(
            markdown: trim($response->text),
            metadata: ['parser' => 'ai'],
        );
    }

    public function supportedMimeTypes(): array
    {
        return [];
    }

    public function requiresOcrLanguages(): bool
    {
        return false;
    }

    public function parsedByMetadata(): array
    {
        return [];
    }
}
