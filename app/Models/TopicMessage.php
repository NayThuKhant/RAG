<?php

namespace App\Models;

use App\Enums\MessageRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicMessage extends Model
{
    protected $fillable = ['topic_id', 'role', 'content', 'input_tokens', 'output_tokens'];

    const string ASSISTANT_ERROR = '__ASSISTANT_ERROR__';

    protected function casts(): array
    {
        return ['role' => MessageRole::class];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
