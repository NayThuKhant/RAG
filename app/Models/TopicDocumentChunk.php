<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicDocumentChunk extends Model
{
    protected $fillable = ['topic_document_id', 'content', 'embedding'];

    protected function casts(): array
    {
        return ['embedding' => 'array'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(TopicDocument::class, 'topic_document_id');
    }
}
