<?php

namespace App\DTOs;

final readonly class ProcessedDocument
{
    public function __construct(
        public string $markdown,
        public array $metadata = [],
    ) {}
}
