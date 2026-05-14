<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class TopicDocument extends Model
{
    protected $fillable = [
        'topic_id',
        'filename',
        'path',
        'disk',
        'status',
        'error_message',
        'chunk_count',
        'extracted_text',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (TopicDocument $document) {
            Storage::disk($document->disk)->delete($document->path);
        });
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(TopicDocumentChunk::class);
    }
}
