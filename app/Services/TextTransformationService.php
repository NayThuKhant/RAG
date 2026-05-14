<?php

namespace App\Services;

final class TextTransformationService
{
    private const int CHUNK_SIZE = 900;

    private const float OVERLAP_RATIO = 0.10;

    /**
     * Split Markdown into overlapping chunks, each prefixed with its nearest
     * ancestor heading to preserve retrieval context.
     *
     * @return string[]
     */
    public function chunk(string $markdown): array
    {
        $markdown = $this->standardizeGfmTables($markdown);
        $chunks = [];

        foreach ($this->splitByHeadings($markdown) as ['heading' => $heading, 'body' => $body]) {
            $body = trim($body);
            if ($body === '') {
                continue;
            }

            foreach ($this->slidingWindowChunk($body) as $raw) {
                $chunks[] = $heading !== '' ? "{$heading}\n\n{$raw}" : $raw;
            }
        }

        return array_values(
            array_filter($chunks, static fn (string $c): bool => mb_strlen($c) > 50)
        );
    }

    /**
     * Normalise GFM table cell spacing and separator rows.
     */
    public function standardizeGfmTables(string $markdown): string
    {
        $markdown = preg_replace('/\| {2,}/', '| ', $markdown) ?? $markdown;
        $markdown = preg_replace('/ {2,}\|/', ' |', $markdown) ?? $markdown;
        return preg_replace('/\|[\s:]-+[\s:]\|/', '|---|', $markdown) ?? $markdown;
    }

    /**
     * Split on heading lines, returning [{heading, body}] segments.
     *
     * @return array<int, array{heading: string, body: string}>
     */
    private function splitByHeadings(string $markdown): array
    {
        $segments = [];
        $currentHeading = '';
        $currentBody = [];

        foreach (explode("\n", $markdown) as $line) {
            if (preg_match('/^#{1,6}\s+.+$/', $line)) {
                if ($currentBody !== []) {
                    $segments[] = ['heading' => $currentHeading, 'body' => implode("\n", $currentBody)];
                    $currentBody = [];
                }
                $currentHeading = $line;
            } else {
                $currentBody[] = $line;
            }
        }

        if ($currentBody !== []) {
            $segments[] = ['heading' => $currentHeading, 'body' => implode("\n", $currentBody)];
        }

        return $segments;
    }

    /**
     * Apply sliding-window chunking within a single segment body.
     *
     * @return string[]
     */
    private function slidingWindowChunk(string $text): array
    {
        $chunkSize = self::CHUNK_SIZE;
        $overlap = (int) round($chunkSize * self::OVERLAP_RATIO);
        $length = mb_strlen($text);

        if ($length <= $chunkSize) {
            return [trim($text)];
        }

        $chunks = [];
        $start = 0;

        while ($start < $length) {
            $end = min($start + $chunkSize, $length);

            if ($end < $length) {
                $window = mb_substr($text, $start, $end - $start);
                $breakAt = $this->findBestBreak($window, $chunkSize);

                if ($breakAt > (int) ($chunkSize / 2)) {
                    $end = $start + $breakAt + 1;
                }
            }

            $chunk = trim(mb_substr($text, $start, $end - $start));

            if (mb_strlen($chunk) > 50) {
                $chunks[] = $chunk;
            }

            if ($end >= $length) {
                break;
            }

            $start = max($end - $overlap, $start + 1);
        }

        return $chunks;
    }

    /**
     * Find the best character position to break at within $window.
     * Prefers paragraph breaks over line breaks over sentence endings.
     */
    private function findBestBreak(string $window, int $chunkSize): int
    {
        $searchFrom = (int) ($chunkSize / 2);
        $candidates = [];

        $pos = mb_strrpos($window, "\n\n");
        if ($pos !== false && $pos > $searchFrom) {
            $candidates[0] = (int) $pos;
        }

        $pos = mb_strrpos($window, "\n");
        if ($pos !== false && $pos > $searchFrom) {
            $candidates[1] = (int) $pos;
        }

        foreach (['. ', '? ', '! '] as $sentinel) {
            $pos = mb_strrpos($window, $sentinel);
            if ($pos !== false && $pos > $searchFrom) {
                $candidates[2] = (int) $pos + 1;
                break;
            }
        }

        if (empty($candidates)) {
            return 0;
        }

        ksort($candidates);

        return reset($candidates);
    }
}
