<?php

namespace App\Console\Commands;

use App\Services\ContentTranslationWorkflow;
use App\Services\TranslationService;
use App\Support\BilingualLists;
use App\Support\CompanyContent;
use Illuminate\Console\Command;

class RepairTranslations extends Command
{
    protected $signature = 'translations:repair {--type=*} {--id=} {--after=0} {--batch=10} {--dry-run} {--apply} {--backup-confirmed} {--review=}';

    protected $description = 'Inspect company English translations; dry-run unless explicitly applied after review and backup.';

    public function handle(ContentTranslationWorkflow $workflow): int
    {
        $types = $this->option('type') ?: CompanyContent::TYPES;
        $batch = filter_var($this->option('batch'), FILTER_VALIDATE_INT);
        $after = filter_var($this->option('after'), FILTER_VALIDATE_INT);
        $id = $this->option('id');
        if (array_diff($types, CompanyContent::TYPES) || ! $batch || $batch < 1 || $batch > 100 || $after === false || $after < 0
            || ($id !== null && (! ctype_digit((string) $id) || (int) $id < 1 || count($types) !== 1))
            || ($this->option('apply') && $this->option('dry-run'))) {
            $this->error('Invalid scope/batch. Supported types: products, articles, brands, settings. UGC/official identities are excluded.');

            return self::FAILURE;
        }
        $records = [];
        $issues = [];
        $fingerprints = [];
        foreach (array_unique($types) as $type) {
            $query = CompanyContent::model($type)::query()->where('id', '>', $after)->when($id, fn ($q) => $q->whereKey($id));
            if ($type === 'settings') {
                $query->whereIn('key', CompanyContent::SETTINGS);
            }
            $last = $after;
            foreach ($query->orderBy('id')->limit($batch)->get() as $record) {
                $last = $record->getKey();
                $unsupported = false;
                if ($type === 'products') {
                    foreach (['highlights', 'notes'] as $list) {
                        $raw = $record->$list;
                        if ($raw !== null && (! is_array($raw) || array_filter($raw, fn ($item) => ! is_array($item)
                            || ! is_string($item['id'] ?? null) || ! is_string($item['en'] ?? '')))) {
                            $issues[] = [$type, $last, $list, 'malformed_or_unsupported', 'manual_review'];
                            $unsupported = true;

                            continue;
                        }
                        $items = BilingualLists::keyed($raw);
                        $idCount = count(array_filter($items, fn ($item) => trim($item['id']) !== ''));
                        $enCount = count(array_filter($items, fn ($item) => trim($item['en']) !== ''));
                        if ($idCount !== $enCount) {
                            $issues[] = [$type, $last, $list, "length_mismatch:$idCount/$enCount", 'fill_missing_only'];
                        }
                        if (count(array_unique(array_column($items, 'key'))) !== count($items)
                            || count(array_unique(array_column($items, 'id'))) !== count($items)) {
                            $issues[] = [$type, $last, $list, 'duplicate_item_or_source', 'manual_review'];
                            $unsupported = true;
                        }
                    }
                }
                if ($unsupported) {
                    $fingerprints[] = [$type, $last, $record->getRawOriginal()];

                    continue;
                }
                $units = CompanyContent::units($record);
                $state = $record->translationState;
                $actionable = false;
                foreach ($units as $field => $unit) {
                    $source = trim($unit['source']);
                    $target = trim($unit['target']);
                    $meta = $state?->fields[$field] ?? [];
                    $fingerprints[] = [$type, $last, $field, TranslationService::hash($source), TranslationService::hash($target), $meta];
                    if ($source === '' && $target === '') {
                        continue;
                    }
                    $issue = null;
                    $canGenerate = ($meta['mode'] ?? null) !== 'manual';
                    $queued = in_array($meta['status'] ?? '', ['pending', 'processing'], true) && $state->updated_at->gt(now()->subMinutes(5));
                    if ($source === '') {
                        $issue = 'source_missing';
                        $canGenerate = false;
                    } elseif ($target === '') {
                        $issue = str_starts_with($field, 'seo_') || str_starts_with($field, 'meta_') ? 'missing_en_metadata' : 'missing_en';
                    } elseif (! $meta) {
                        $issue = 'legacy_source_version_unknown';
                        $canGenerate = false;
                    } elseif (($meta['source_hash'] ?? '') !== TranslationService::hash($source)
                        || (($meta['translated_source_hash'] ?? null) && $meta['translated_source_hash'] !== TranslationService::hash($source))
                        || in_array($meta['status'] ?? '', ['stale', 'failed', 'pending', 'processing'], true)) {
                        $issue = $queued ? 'queued' : ($meta['status'] ?? 'stale');
                    } elseif (($meta['status'] ?? '') === 'needs_review') {
                        $issue = $meta['reason'] ?? 'needs_review';
                        $canGenerate = false;
                    }
                    if (stripos($source, 'NO MSG') !== false && stripos($target, 'NO MSG') === false && $target !== '') {
                        $issue = 'protected_term_mismatch';
                        $canGenerate = false;
                    }
                    if ($issue) {
                        $action = $canGenerate && ! $queued ? 'queue' : ($queued ? 'already_queued' : 'manual_review');
                        $issues[] = [$type, $last, $field, $issue, $action];
                        $actionable = $actionable || $action === 'queue';
                    }
                }
                if ($actionable) {
                    $records[] = $record;
                }
            }
            $this->line($type.' next --after='.$last);
        }
        $review = hash('sha256', json_encode($fingerprints, JSON_THROW_ON_ERROR));
        if ($issues) {
            $this->table(['type', 'id', 'field', 'issue', 'action'], $issues);
        }
        $this->line('Review token: '.$review);
        $this->info('Issues: '.count($issues).'; records eligible for generation: '.count($records).'. Legacy/manual English is preserved.');
        if (! $this->option('apply')) {
            $this->info('DRY-RUN: no database/cache/provider/queue writes.');

            return self::SUCCESS;
        }
        if (! $this->option('backup-confirmed') || ! hash_equals($review, (string) $this->option('review'))) {
            $this->error('APPLY requires --backup-confirmed and --review=<current dry-run token>. Nothing changed.');

            return self::FAILURE;
        }
        foreach ($records as $record) {
            $workflow->save($record, []);
        }
        $this->info('Queued '.count($records).' records. Run the translations worker; failures are retained for explicit retry.');

        return self::SUCCESS;
    }
}
