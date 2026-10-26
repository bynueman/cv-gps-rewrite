<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\ContentTranslationWorkflow;
use App\Services\ImageProcessor;
use App\Support\CompanyContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(private readonly ImageProcessor $imageProcessor) {}

    private const KEYS = [
        'whatsapp', 'email_primary', 'email_secondary', 'address',
        'instagram_kuicip', 'instagram_putriteko', 'operating_hours',
        'site_title', 'meta_description_default', 'og_image_default',
    ];

    public function edit(): Response
    {
        $values = Setting::whereIn('key', self::KEYS)->pluck('value', 'key');

        return Inertia::render('Admin/Settings/Edit', [
            'translations' => Setting::whereIn('key', CompanyContent::SETTINGS)->get()->mapWithKeys(fn ($record) => [$record->key => ContentTranslationWorkflow::view($record)])->all(),
            'english' => Setting::whereIn('key', CompanyContent::SETTINGS)->pluck('value_en', 'key')->all(),
            'settings' => collect(self::KEYS)->mapWithKeys(fn ($key) => [$key => $values[$key] ?? ''])->all(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $oldOgImage = Setting::get('og_image_default');
        DB::transaction(function () use ($data) {
            foreach (self::KEYS as $key) {
                $value = $data[$key] ?? null;
                if (in_array($key, CompanyContent::SETTINGS, true)) {
                    $record = Setting::firstOrNew(['key' => $key]);
                    app(ContentTranslationWorkflow::class)->save($record, [
                        'key' => $key, 'value' => $value,
                        'value_en' => $data['english'][$key] ?? $record->value_en,
                    ], [
                        ...(array_key_exists('translation_revisions', $data) ? ['translation_revision' => $data['translation_revisions'][$key] ?? null] : []),
                        ...(isset($data['translation_english_edits']) ? ['translation_english_edits' => in_array($key, $data['translation_english_edits'], true) ? ['value'] : []] : []),
                    ]);
                } else {
                    Setting::set($key, $value);
                }
            }
        });

        if ($oldOgImage && $oldOgImage !== ($data['og_image_default'] ?? null)) {
            $this->imageProcessor->delete($oldOgImage);
        }

        ActivityLog::record('updated', 'settings', 'Pengaturan situs');

        return back()->with('status', 'Pengaturan disimpan. English untuk teks publik diproses di antrean; kontak dan identitas tidak diterjemahkan.');
    }
}
