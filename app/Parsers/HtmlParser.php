<?php

namespace App\Parsers;

use App\DTOs\ProcessedDocument;
use App\Models\TopicDocument;
use voku\Html2Text\Html2Text;

class HtmlParser implements DocumentParserInterface
{
    public function parse(TopicDocument $document, string $absolutePath): ProcessedDocument
    {
        $html = file_get_contents($absolutePath);

        if ($html === false || trim($html) === '') {
            return new ProcessedDocument(markdown: '', metadata: ['parser' => 'html', 'empty' => true]);
        }

        $html = $this->stripNonContentTags($html);

        $text = (new Html2Text($html))->getText();
        $text = $this->normalizeWhitespace($text);

        return new ProcessedDocument(
            markdown: $text,
            metadata: ['parser' => 'html'],
        );
    }

    public function supportedMimeTypes(): array
    {
        return ['text/html', 'application/xhtml+xml'];
    }

    public function requiresOcrLanguages(): bool
    {
        return false;
    }

    public function parsedByMetadata(): array
    {
        return [];
    }

    private function stripNonContentTags(string $html): string
    {
        return preg_replace(
            '#<(script|style|nav|header|footer|aside)[^>]*>.*?</\1>#si',
            '',
            $html,
        ) ?? $html;
    }

    private function normalizeWhitespace(string $text): string
    {
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
        $text = preg_replace('/[ \t]+$/m', '', $text) ?? $text;

        return trim($text);
    }
}
