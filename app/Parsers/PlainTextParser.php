<?php

namespace App\Parsers;

use App\DTOs\ProcessedDocument;
use App\Models\TopicDocument;

class PlainTextParser implements DocumentParserInterface
{
    public function parse(TopicDocument $document, string $absolutePath): ProcessedDocument
    {
        $raw = file_get_contents($absolutePath);

        return new ProcessedDocument(
            markdown: $this->normalizeWhitespace($raw !== false ? $raw : ''),
            metadata: ['parser' => 'plain_text'],
        );
    }

    public function supportedMimeTypes(): array
    {
        return ['text/plain', 'text/markdown', 'text/x-markdown', 'application/json', 'text/json'];
    }

    public function requiresOcrLanguages(): bool
    {
        return false;
    }

    public function parsedByMetadata(): array
    {
        return [];
    }

    private function normalizeWhitespace(string $text): string
    {
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
        $text = preg_replace('/[ \t]+$/m', '', $text) ?? $text;

        return trim($text);
    }
}
