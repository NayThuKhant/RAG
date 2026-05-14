<?php

namespace App\Enums;

enum FileViewerMode
{
    case Image;
    case Download;
    case Html;
    case Markdown;
    case PlainText;

    public static function fromMimeType(string $mimeType): self
    {
        if (str_starts_with($mimeType, 'image/')) {
            return self::Image;
        }

        return match ($mimeType) {
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/msword' => self::Download,
            'text/html', 'application/xhtml+xml' => self::Html,
            'text/markdown', 'text/x-markdown' => self::Markdown,
            default => self::PlainText,
        };
    }

    public function isBinary(): bool
    {
        return match ($this) {
            self::Image, self::Download => true,
            default => false,
        };
    }
}
