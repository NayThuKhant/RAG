<?php

namespace App\Parsers;

use App\DTOs\ProcessedDocument;
use App\Models\TopicDocument;
use Exception;
use Smalot\PdfParser\Parser as SmalotParser;
use Spatie\PdfToImage\Exceptions\PdfDoesNotExist;
use Spatie\PdfToImage\Pdf as PdfToImage;
use thiagoalessio\TesseractOCR\TesseractOCR;
use thiagoalessio\TesseractOCR\TesseractOcrException;

class PdfParser implements DocumentParserInterface
{
    private bool $usedOcr = false;

    /**
     * @throws PdfDoesNotExist
     * @throws TesseractOcrException
     * @throws Exception
     */
    public function parse(TopicDocument $document, string $absolutePath): ProcessedDocument
    {
        $languages = $document->metadata['tesseract_ocr_languages'] ?? ['eng'];
        $ocrText = $this->extractViaOcr($absolutePath, $languages);

        if (trim($ocrText) !== '') {
            $this->usedOcr = true;
            $text = $ocrText;
        } else {
            $this->usedOcr = false;
            $text = $this->extractNativeText($absolutePath);
        }

        return new ProcessedDocument(
            markdown: trim($text),
            metadata: ['parser' => 'pdf', 'languages' => $languages],
        );
    }

    public function supportedMimeTypes(): array
    {
        return ['application/pdf'];
    }

    public function requiresOcrLanguages(): bool
    {
        return true;
    }

    public function parsedByMetadata(): array
    {
        return ['ocr' => $this->usedOcr];
    }

    /**
     * @throws Exception
     */
    private function extractNativeText(string $absolutePath): string
    {
        return (new SmalotParser)->parseFile($absolutePath)->getText();
    }

    /**
     * @throws PdfDoesNotExist
     * @throws TesseractOcrException
     */
    private function extractViaOcr(string $absolutePath, array $languages = []): string
    {
        $pdf = new PdfToImage($absolutePath);

        $pageCount = $pdf->pageCount();
        $parts = [];
        $tempDir = sys_get_temp_dir();

        for ($page = 1; $page <= $pageCount; $page++) {
            $imagePath = $tempDir.'/pdf_ocr_'.uniqid().'_p'.$page.'.jpg';

            try {
                $pdf->selectPage($page)->save($imagePath);

                $ocr = new TesseractOCR($imagePath);
                if ($binary = config('services.tesseract_binary')) {
                    $ocr->executable($binary);
                }
                $ocr->lang(...$languages);
                $parts[] = $ocr->run();
            } finally {
                @unlink($imagePath);
            }
        }

        return implode("\n\n", array_filter($parts));
    }
}
