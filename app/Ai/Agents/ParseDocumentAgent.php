<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Gemini)]
#[Timeout(120)]
class ParseDocumentAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
        You are a document text extractor. Extract all readable text from the provided document.

        Rules:
        - Return only the extracted text, no commentary or explanations.
        - Preserve logical structure: headings, paragraphs, list items, and table rows.
        - For tables, output each row on its own line with cells separated by " | ".
        - Do not wrap the output in markdown code blocks or add any surrounding markup.
        INSTRUCTIONS;
    }
}
