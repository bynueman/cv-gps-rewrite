<?php

namespace App\Services;

use App\Jobs\TranslateCompanyContent;
use App\Models\ContentTranslation;
use App\Models\Product;
use App\Support\CompanyContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContentTranslationWorkflow
{
    public function save(Model $record, array $attributes, array $options = []): Model
    {
        return DB::connection($record->getConnectionName())->transaction(function () use ($record, $attributes, $options) {
            $type = $record->getTable();
            $class = CompanyContent::model($type);
            if ($record->exists) {
                $record = $class::lockForUpdate()->findOrFail($record->getKey());
            }
            $before = CompanyContent::units($record);
            $state = $record->exists ? ContentTranslation::where('content_type', $type)->where('content_id', $record->getKey())->lockForUpdate()->first() : null;
            if (array_key_exists('translation_revision', $options) && $state && $options['translation_revision'] !== $state->revision) {
                throw ValidationException::withMessages(['translation_revision' => 'Konten berubah. Muat ulang sebelum menyimpan agar English manual tidak tertimpa.']);
            }
            $record->fill($attributes);
            if (isset($options['translation_english_edits'])) {
                // A form opened before the worker finished must not resubmit its outdated English.
                foreach (CompanyContent::units($record) as $path => $unit) {
                    if (isset($before[$path]) && ! in_array($path, $options['translation_english_edits'], true)) {
                        CompanyContent::write($record, $path, $before[$path]['target']);
                    }
                }
            }
            $record->save();
            $this->schedule($record, $before, $state, (bool) ($options['translation_refresh'] ?? false), (bool) ($options['translation_overwrite_manual'] ?? false));

            return $record;
        });
    }

    private function schedule(Model $record, array $before, ?ContentTranslation $state, bool $refresh, bool $overwrite): void
    {
        $fields = [];
        foreach (CompanyContent::units($record) as $path => $unit) {
            if (trim($unit['source']) === '' && trim($unit['target']) === '') {
                continue;
            }
            $old = $state?->fields[$path] ?? [];
            $previous = $before[$path] ?? ['source' => '', 'target' => ''];
            $sourceHash = TranslationService::hash($unit['source']);
            $target = $unit['target'];
            $wasManual = ($old['mode'] ?? null) === 'manual' || (! $old && trim($previous['target']) !== '');
            if ($wasManual && trim($target) === '' && ! ($refresh && $overwrite)) {
                $target = $previous['target'];
                CompanyContent::write($record, $path, $target);
            }
            $manualEdit = trim($target) !== '' && $target !== $previous['target'];
            $manual = $manualEdit || ($wasManual && ! ($refresh && $overwrite));
            $changed = ($old['source_hash'] ?? TranslationService::hash($previous['source'])) !== $sourceHash;
            $status = 'pending';
            $translatedHash = $old['translated_source_hash'] ?? null;
            if (trim($unit['source']) === '') {
                $manual = true;
                $status = 'needs_review';
            } elseif ($manual) {
                $status = $manualEdit ? 'manual' : ($changed ? 'stale' : ($old['status'] ?? 'needs_review'));
                if ($manualEdit) {
                    $translatedHash = $sourceHash;
                }
            } elseif (trim($target) !== '' && ! $changed && ! $refresh && ($old['status'] ?? '') === 'ready') {
                $status = 'ready';
            } elseif (trim($target) !== '' && ! $changed && ! $refresh && ($old['status'] ?? '') === 'needs_review') {
                $status = 'needs_review';
            }
            $fields[$path] = [
                'source_hash' => $sourceHash, 'target_hash' => TranslationService::hash($target),
                'translated_source_hash' => $translatedHash, 'mode' => $manual ? 'manual' : 'generated', 'status' => $status,
                'reason' => $manual && ! $translatedHash ? 'legacy_unverified' : null,
            ];
            if ($refresh && $overwrite && $wasManual) {
                $fields[$path]['previous_manual'] = $previous['target'];
            } elseif (isset($old['previous_manual'])) {
                $fields[$path]['previous_manual'] = $old['previous_manual'];
            }
        }
        $record->save();
        $state ??= new ContentTranslation(['content_type' => $record->getTable(), 'content_id' => $record->getKey()]);
        $state->fill(['revision' => (string) Str::uuid(), 'fields' => $fields, 'status' => self::status($fields)])->save();
        if ($state->status === 'pending') {
            $queueConnection = config('queue.connections.database.connection') ?: config('database.default');
            if (config('queue.connections.database.driver') !== 'database' || $queueConnection !== config('database.default')
                || ! Schema::hasTable(config('queue.connections.database.table', 'jobs'))) {
                $fields = $state->fields;
                foreach ($fields as &$field) {
                    if ($field['status'] === 'pending') {
                        $field['status'] = 'failed';
                        $field['reason'] = 'queue_configuration';
                    }
                }
                unset($field);
                $state->update(['fields' => $fields, 'status' => self::status($fields)]);

                return;
            }
            // The queue uses the same DB transaction: jobs become visible only when the source commits.
            TranslateCompanyContent::dispatch($record->getTable(), $record->getKey(), $state->revision)
                ->onConnection('database')->onQueue('translations')->beforeCommit();
        }
    }

    public static function status(array $fields): string
    {
        $statuses = array_column($fields, 'status');
        foreach (['processing', 'pending', 'failed', 'stale', 'needs_review'] as $status) {
            if (in_array($status, $statuses, true)) {
                return $status;
            }
        }

        return $statuses && ! in_array('ready', $statuses, true) ? 'manual' : 'ready';
    }

    public static function sensitive(Model $record, string $source): bool
    {
        if (! $record instanceof Product) {
            return false;
        }
        if (preg_match('/no\s*msg/i', $source) && $record->brand?->key === 'kuicip'
            && in_array($record->slug, config('translations.verified_no_msg_variants'), true)) {
            $source = preg_replace('/no\s*msg/i', '', $source);
        }

        return (bool) preg_match('/msg|gluten|pengawet|preservative|oily|berminyak|halal|organic|organik|menyembuhkan|rendah gula/i', $source);
    }

    public static function view(Model $record): array
    {
        $state = ContentTranslation::where('content_type', $record->getTable())->where('content_id', $record->getKey())->first();
        $fields = $state?->fields ?? [];
        $positions = ['highlights' => 0, 'notes' => 0];
        $labels = ['name' => 'Nama produk', 'short' => 'Tagline', 'personality' => 'Kepribadian rasa', 'serving' => 'Penyajian', 'description' => 'Deskripsi', 'seo_title' => 'Judul SEO', 'meta_description' => 'Meta description', 'image_alt' => 'Deskripsi gambar', 'title' => 'Judul artikel', 'excerpt' => 'Ringkasan / SEO', 'body' => 'Isi artikel', 'tags' => 'Tags', 'value' => 'Teks pengaturan'];
        foreach ($fields as $path => &$field) {
            $parts = explode('.', $path, 2);
            $field['label'] = isset($positions[$parts[0]]) ? ($parts[0] === 'notes' ? 'Catatan ' : 'Keunggulan ').(++$positions[$parts[0]]) : ($labels[$path] ?? $path);
            unset($field['previous_manual'], $field['target_hash']);
        }
        unset($field);

        return ['type' => $record->getTable(), 'id' => $record->getKey(), 'revision' => $state?->revision,
            'status' => $state?->status ?? 'needs_review', 'fields' => $fields];
    }
}
