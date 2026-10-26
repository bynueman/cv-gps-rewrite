<?php

namespace App\Support;

use App\Services\TranslationService;
use Illuminate\Database\Eloquent\Model;

class LocalizedContent
{
    public static function pair(Model $record, string $field): array
    {
        $unit = CompanyContent::units($record)[$field] ?? ['source' => '', 'target' => ''];
        $state = $record->translationState?->fields[$field] ?? [];
        $stale = (isset($state['translated_source_hash']) && $state['translated_source_hash'] !== TranslationService::hash($unit['source']))
            || in_array($state['status'] ?? '', ['pending', 'processing', 'failed', 'stale'], true)
            || (stripos($unit['source'], 'NO MSG') !== false && stripos($unit['target'], 'NO MSG') === false);

        return ['id' => $unit['source'], 'en' => $unit['target'], 'en_stale' => $stale];
    }

    public static function english(Model $record, string $field, string $fallback = ''): string
    {
        $pair = self::pair($record, $field);

        return ! $pair['en_stale'] && trim($pair['en']) !== '' ? $pair['en'] : ($fallback ?: $pair['id']);
    }
}
