<?php

namespace App\Models\Concerns;

use App\Models\ContentTranslation;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasContentTranslation
{
    protected static function bootHasContentTranslation(): void
    {
        static::deleted(fn ($record) => $record->translationState()->delete());
    }

    public function translationState(): HasOne
    {
        return $this->hasOne(ContentTranslation::class, 'content_id')->where('content_type', $this->getTable());
    }
}
