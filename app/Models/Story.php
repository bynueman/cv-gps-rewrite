<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Story extends StoryRecord
{
    use SoftDeletes;

    public const CATEGORIES = ['kehidupan', 'percintaan', 'pertemanan', 'bahagia', 'random'];

    protected $guarded = ['id'];

    protected $hidden = ['deletion_token_hash', 'fingerprint', 'duplicate_hash', 'idempotency_key', 'request_hash'];

    protected $casts = [
        'publication_consent' => 'boolean', 'social_consent' => 'boolean',
        'published_at' => 'datetime', 'supports_count' => 'integer',
    ];

    public function scopePublicFeed(Builder $query): Builder
    {
        return $query->where('visibility', 'public')->where('status', 'approved')
            ->where('publication_consent', true)->whereNotNull('published_at');
    }

    public function displayCode(): string
    {
        return 'KC-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function publicData(): array
    {
        return ['id' => $this->id, 'code' => $this->displayCode(), 'category' => $this->category,
            'body' => $this->body, 'supports' => $this->supports_count,
            'published_at' => $this->published_at?->toIso8601String()];
    }
}
