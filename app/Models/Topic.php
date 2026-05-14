<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    protected $fillable = ['name', 'description'];

    protected static function booted(): void
    {
        static::deleting(function (Topic $topic) {
            $topic->documents->each->delete();
        });
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TopicDocument::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TopicMessage::class);
    }
}
