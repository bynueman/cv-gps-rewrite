<?php

namespace App\Jobs;

use App\Exceptions\TranslationFailure;
use App\Models\ContentTranslation;
use App\Services\ContentTranslationWorkflow;
use App\Services\TranslationService;
use App\Support\CompanyContent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class TranslateCompanyContent implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 40;

    public function __construct(public string $contentType, public int $contentId, public string $revision) {}

    public function handle(TranslationService $translator): void
    {
        $class = CompanyContent::model($this->contentType);
        $record = $class::find($this->contentId);
        $state = $this->state();
        if (! $record || ! $state) {
            return;
        }
        $translator->resetBudget();
        foreach (CompanyContent::units($record) as $path => $unit) {
            if (($state->fields[$path]['status'] ?? '') !== 'pending') {
                continue;
            }
            $this->setFieldStatus($path, 'processing');
            try {
                $target = $unit['html'] ? $translator->translateHtml($unit['source']) : $translator->translateText($unit['source']);
                if (mb_strlen($target) > $unit['limit'] || trim(strip_tags($target)) === '') {
                    throw new TranslationFailure('target_length');
                }
                DB::transaction(function () use ($class, $path, $target, $unit) {
                    $fresh = $class::lockForUpdate()->find($this->contentId);
                    $currentState = $this->state(true);
                    if (! $fresh || ! $currentState) {
                        return;
                    }
                    $current = CompanyContent::units($fresh)[$path] ?? null;
                    $field = $currentState->fields[$path] ?? null;
                    if (! $current || ! $field || $field['mode'] !== 'generated'
                        || $field['source_hash'] !== TranslationService::hash($current['source'])
                        || $field['target_hash'] !== TranslationService::hash($current['target'])) {
                        if ($field && $current) {
                            $fields = $currentState->fields;
                            $fields[$path] = array_replace($field, ['mode' => 'manual', 'status' => 'stale', 'reason' => 'concurrent_edit']);
                            $currentState->update(['fields' => $fields, 'status' => ContentTranslationWorkflow::status($fields)]);
                        }

                        return;
                    }
                    CompanyContent::write($fresh, $path, $target);
                    $fresh->save();
                    $fields = $currentState->fields;
                    $fields[$path] = array_replace($field, ['status' => ContentTranslationWorkflow::sensitive($fresh, $unit['source']) ? 'needs_review' : 'ready',
                        'translated_source_hash' => $field['source_hash'], 'target_hash' => TranslationService::hash($target), 'reason' => ContentTranslationWorkflow::sensitive($fresh, $unit['source']) ? 'claim_review' : null]);
                    $currentState->update(['fields' => $fields, 'status' => ContentTranslationWorkflow::status($fields)]);
                });
            } catch (TranslationFailure $failure) {
                if (in_array($failure->reason, ['budget', 'rate_limited'], true)) {
                    $this->setFieldStatus($path, 'pending');
                    if ($this->state()) {
                        self::dispatch($this->contentType, $this->contentId, $this->revision)
                            ->onConnection('database')->onQueue('translations')->delay(now()->addSeconds($failure->reason === 'rate_limited' ? 65 : 5));
                    }
                } else {
                    $this->markFailed($failure->reason);
                }

                return;
            } catch (Throwable) {
                $this->markFailed('processing');

                return;
            }
            $state = $this->state();
            if (! $state) {
                return;
            }
        }
    }

    private function setFieldStatus(string $path, string $status): void
    {
        DB::transaction(function () use ($path, $status) {
            $state = $this->state(true);
            if (! $state || ! isset($state->fields[$path])) {
                return;
            }
            $fields = $state->fields;
            $fields[$path]['status'] = $status;
            $state->update(['fields' => $fields, 'status' => ContentTranslationWorkflow::status($fields)]);
        });
    }

    private function state(bool $lock = false): ?ContentTranslation
    {
        return ContentTranslation::where('content_type', $this->contentType)->where('content_id', $this->contentId)
            ->where('revision', $this->revision)->when($lock, fn ($q) => $q->lockForUpdate())->first();
    }

    private function markFailed(string $reason): void
    {
        DB::transaction(function () use ($reason) {
            $state = $this->state(true);
            if (! $state) {
                return;
            }
            $fields = $state->fields;
            foreach ($fields as &$field) {
                if (in_array($field['status'], ['pending', 'processing'], true)) {
                    $field['status'] = 'failed';
                    $field['reason'] = $reason;
                }
            }
            unset($field);
            $state->update(['fields' => $fields, 'status' => ContentTranslationWorkflow::status($fields)]);
        });
    }

    public function failed(?Throwable $exception): void
    {
        $this->markFailed('worker');
    }
}
