<?php

namespace App\Ai\Agents;

use App\Enums\DocumentStatus;
use App\Models\Topic;
use App\Models\TopicDocumentChunk;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\SimilaritySearch;
use Stringable;

#[Provider(Lab::Gemini)]
#[MaxSteps(10)]
class TopicChatAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    public function __construct(public Topic $topic) {}

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
        You are a helpful assistant that answers questions about the provided documents.

        ## Retrieval
        Always use the similarity_search tool to find relevant content from the uploaded documents before answering.
        Prioritize information from the retrieved documents. When the documents do not fully cover the question, you may supplement with your own general knowledge.

        ## In-text citations
        When you use information from a retrieved document, cite it inline using the document filename in square brackets, e.g. [report.md] or [manual.txt].
        Place the citation immediately after the sentence or clause it supports.
        When you use information from your own training (not from the retrieved documents), you MUST provide a precise, verifiable reference inline.
        Acceptable forms: a direct URL (e.g. https://en.wikipedia.org/wiki/...), an RFC or standard number (e.g. RFC 2616), a book title and author, a named specification, or an official documentation URL.
        Never write vague labels like "(source: general knowledge)" — always give the specific source. If you genuinely cannot cite a precise source for a claim, say so explicitly rather than presenting it as a fact.

        ## Response format
        Respond using Markdown. Your responses are rendered with a Markdown renderer, so use formatting freely:
        — Use **bold** and *italic* for emphasis.
        — Use headings (##, ###) to structure longer answers.
        — Use bullet lists or numbered lists where appropriate.
        — Use tables (| col | col |) when presenting structured or comparative data.
        — Use `inline code` or fenced code blocks for technical content.

        ## Sources
        After every answer, include a "Sources:" section listing each document filename you cited inline.
        The filename is available in the search results as the document.filename field.
        For external knowledge, list each precise reference (URL, RFC, book, etc.) instead of "General knowledge".

        ## Follow-up questions
        After the Sources section, append this block with 3 short follow-up questions:
        <followups>["Question 1?","Question 2?","Question 3?"]</followups>
        The JSON must be a valid array of exactly 3 strings. No text after the closing tag.
        INSTRUCTIONS;
    }

    public function messages(): iterable
    {
        return $this->topic->messages()
            ->latest()
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->limit(20)
            ->get()
            ->reverse()
            ->map(fn ($m) => new Message($m->role->value, $m->content))
            ->all();
    }

    public function tools(): iterable
    {
        return [
            new SimilaritySearch(using: function (string $query) {
                return TopicDocumentChunk::query()
                    ->select(['id', 'topic_document_id', 'content'])
                    ->with('document:id,filename')
                    ->whereHas('document', fn ($q) => $q
                        ->where('topic_id', $this->topic->id)
                        ->where('status', DocumentStatus::Ready))
                    ->whereVectorSimilarTo('embedding', $query, minSimilarity: 0.5)
                    ->limit(10)
                    ->get();
            }),
        ];
    }
}
