<?php

namespace App\Parsers;

use App\DTOs\ProcessedDocument;
use App\Enums\FileViewerMode;
use App\Models\TopicDocument;
use Illuminate\Support\Facades\Storage;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use RuntimeException;

class ParserManager
{
    /** @var array<string, DocumentParserInterface> */
    private array $map = [];

    /**
     * @param  DocumentParserInterface[]  $parsers
     */
    public function __construct(
        private readonly array $parsers,
        private readonly ExtensionMimeTypeDetector $mimeDetector,
        private readonly ?DocumentParserInterface $fallback = null,
    ) {
        foreach ($this->parsers as $parser) {
            foreach ($parser->supportedMimeTypes() as $mimeType) {
                $this->map[$mimeType] = $parser;
            }
        }
    }

    /**
     * Parse the document, routing by MIME type with fallback to the AI parser.
     *
     * @throws RuntimeException When no parser or fallback is available.
     */
    public function parse(TopicDocument $document): ProcessedDocument
    {
        $parser = $this->resolveParser($document->filename);
        $absolutePath = Storage::disk($document->disk)->path($document->path);
        $processed = $parser->parse($document, $absolutePath);

        return new ProcessedDocument(
            markdown: $processed->markdown,
            metadata: array_merge($processed->metadata, [
                'parsed_by' => [
                    'class' => $parser::class,
                    'metadata' => $parser->parsedByMetadata(),
                ],
            ]),
        );
    }

    public function requiresOcrLanguages(string $filename): bool
    {
        return $this->resolveParser($filename)->requiresOcrLanguages();
    }

    public function viewerModeFor(string $filename): FileViewerMode
    {
        return FileViewerMode::fromMimeType($this->resolveMimeType($filename));
    }

    private function resolveParser(string $filename): DocumentParserInterface
    {
        $mimeType = $this->resolveMimeType($filename);

        return $this->map[$mimeType]
            ?? $this->fallback
            ?? throw new RuntimeException(
                "No parser registered for MIME type [{$mimeType}] (file: {$filename})."
            );
    }

    private function resolveMimeType(string $filename): string
    {
        return $this->mimeDetector->detectMimeTypeFromPath($filename) ?? 'application/octet-stream';
    }
}
