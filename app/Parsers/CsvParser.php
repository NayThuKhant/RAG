<?php

namespace App\Parsers;

use App\DTOs\ProcessedDocument;
use App\Models\TopicDocument;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\SyntaxError;
use League\Csv\UnavailableStream;

class CsvParser implements DocumentParserInterface
{
    /**
     * @throws UnavailableStream
     * @throws SyntaxError
     * @throws Exception
     */
    public function parse(TopicDocument $document, string $absolutePath): ProcessedDocument
    {
        $csv = Reader::createFromPath($absolutePath, 'r');
        $csv->setHeaderOffset(0);

        $headers = $csv->getHeader();
        $records = iterator_to_array($csv->getRecords());

        return new ProcessedDocument(
            markdown: $this->buildMarkdownTable($headers, $records),
            metadata: [
                'parser' => 'csv',
                'row_count' => count($records),
                'column_count' => count($headers),
            ],
        );
    }

    public function supportedMimeTypes(): array
    {
        return ['text/csv', 'text/comma-separated-values'];
    }

    public function requiresOcrLanguages(): bool
    {
        return false;
    }

    public function parsedByMetadata(): array
    {
        return [];
    }

    /**
     * @param  string[]  $headers
     * @param  array<int, array<string, string>>  $records
     */
    private function buildMarkdownTable(array $headers, array $records): string
    {
        if (empty($headers)) {
            return '';
        }

        $escape = static fn (string $cell): string => str_replace('|', '\\|', trim($cell));

        $headerRow = '| '.implode(' | ', array_map($escape, $headers)).' |';
        $separator = '| '.implode(' | ', array_fill(0, count($headers), '---')).' |';

        $rows = array_map(
            static fn (array $record): string => '| '.implode(' | ', array_map(
                static fn (string $h): string => $escape((string) ($record[$h] ?? '')),
                $headers,
            )).' |',
            $records,
        );

        return implode("\n", [$headerRow, $separator, ...$rows]);
    }
}
