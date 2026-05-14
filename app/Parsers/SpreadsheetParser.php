<?php

namespace App\Parsers;

use App\DTOs\ProcessedDocument;
use App\Models\TopicDocument;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SpreadsheetParser implements DocumentParserInterface
{
    public function parse(TopicDocument $document, string $absolutePath): ProcessedDocument
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $sections = [];
        $totalRows = 0;

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            /** @var Worksheet $sheet */
            $table = $this->sheetToMarkdownTable($sheet);

            if ($table !== '') {
                $sections[] = '## '.$sheet->getTitle()."\n\n".$table;
                $totalRows += $sheet->getHighestDataRow();
            }
        }

        return new ProcessedDocument(
            markdown: implode("\n\n", $sections),
            metadata: [
                'parser' => 'spreadsheet',
                'sheet_count' => $spreadsheet->getSheetCount(),
                'total_rows' => $totalRows,
            ],
        );
    }

    public function supportedMimeTypes(): array
    {
        return [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
        ];
    }

    public function requiresOcrLanguages(): bool
    {
        return false;
    }

    public function parsedByMetadata(): array
    {
        return [];
    }

    private function sheetToMarkdownTable(Worksheet $sheet): string
    {
        $highestRow = $sheet->getHighestDataRow();
        $highestCol = $sheet->getHighestDataColumn();

        if ($highestRow < 1) {
            return '';
        }

        $data = $sheet->rangeToArray('A1:'.$highestCol.$highestRow, null, true, true, false);

        $headers = array_map('strval', $data[0] ?? []);
        $rows = array_slice($data, 1);

        $escape = static fn (mixed $cell): string => str_replace('|', '\\|', trim((string) $cell));

        $headerRow = '| '.implode(' | ', array_map($escape, $headers)).' |';
        $separator = '| '.implode(' | ', array_fill(0, count($headers), '---')).' |';

        $dataRows = array_map(
            static fn (array $row): string => '| '.implode(' | ', array_map($escape, $row)).' |',
            $rows,
        );

        return implode("\n", array_filter([$headerRow, $separator, ...$dataRows]));
    }
}
