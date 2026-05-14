<?php

namespace App\Parsers;

use App\DTOs\ProcessedDocument;
use App\Models\TopicDocument;
use PhpOffice\PhpWord\Element\AbstractElement;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\IOFactory;

class WordParser implements DocumentParserInterface
{
    public function parse(TopicDocument $document, string $absolutePath): ProcessedDocument
    {
        $phpWord = IOFactory::load($absolutePath);
        $lines = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $rendered = $this->renderElement($element);
                if ($rendered !== '') {
                    $lines[] = $rendered;
                }
            }
        }

        return new ProcessedDocument(
            markdown: implode("\n\n", $lines),
            metadata: ['parser' => 'word'],
        );
    }

    public function supportedMimeTypes(): array
    {
        return ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    }

    public function requiresOcrLanguages(): bool
    {
        return false;
    }

    public function parsedByMetadata(): array
    {
        return [];
    }

    private function renderElement(AbstractElement $element): string
    {
        return match (true) {
            $element instanceof Title => $this->renderTitle($element),
            $element instanceof ListItem => $this->renderListItem($element),
            $element instanceof Table => $this->renderTable($element),
            $element instanceof TextRun => $this->renderTextRun($element),
            $element instanceof Text => (string) $element->getText(),
            default => '',
        };
    }

    private function renderTitle(Title $title): string
    {
        $depth = min($title->getDepth() ?: 1, 6);
        $raw = $title->getText();
        $text = is_string($raw) ? $raw : $this->extractText($raw);

        return $text !== '' ? str_repeat('#', $depth).' '.$text : '';
    }

    private function renderListItem(ListItem $item): string
    {
        $indent = str_repeat('  ', $item->getDepth());
        $text = $this->extractText($item->getTextObject());

        return $text !== '' ? "{$indent}- {$text}" : '';
    }

    private function renderTable(Table $table): string
    {
        $allRows = [];
        $escape = static fn (string $c): string => str_replace('|', '\\|', trim($c));

        foreach ($table->getRows() as $row) {
            $cells = [];
            foreach ($row->getCells() as $cell) {
                $cellText = '';
                foreach ($cell->getElements() as $el) {
                    $cellText .= $this->extractText($el);
                }
                $cells[] = $escape($cellText);
            }
            $allRows[] = '| '.implode(' | ', $cells).' |';
        }

        if (empty($allRows)) {
            return '';
        }

        $header = array_shift($allRows);
        $colCount = substr_count($header, '|') - 1;
        $separator = '| '.implode(' | ', array_fill(0, $colCount, '---')).' |';

        return implode("\n", [$header, $separator, ...$allRows]);
    }

    private function renderTextRun(TextRun $textRun): string
    {
        $parts = [];
        foreach ($textRun->getElements() as $el) {
            $parts[] = $this->extractText($el);
        }

        return implode('', $parts);
    }

    private function extractText(mixed $element): string
    {
        if ($element instanceof Text) {
            return (string) $element->getText();
        }
        if ($element instanceof TextRun) {
            return $this->renderTextRun($element);
        }

        return '';
    }
}
