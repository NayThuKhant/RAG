<?php

namespace App\Parsers;

use App\DTOs\ProcessedDocument;
use App\Models\TopicDocument;

interface DocumentParserInterface
{
    public function parse(TopicDocument $document, string $absolutePath): ProcessedDocument;

    /** @return string[] */
    public function supportedMimeTypes(): array;

    public function requiresOcrLanguages(): bool;

    /** @return array<string, bool> */
    public function parsedByMetadata(): array;
}
