<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\ImageProcessor;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(private readonly ImageProcessor $imageProcessor)
    {
    }

    private const KEYS = [
        'whatsapp', 'email_primary', 'email_secondary', 'address',
        'instagram_kuicip', 'instagram_putriteko', 'operating_hours',
        'site_title', 'meta_description_default', 'og_image_default',
    ];

    public function edit(): Response
    {
        $values = Setting::whereIn('key', self::KEYS)->pluck('value', 'key');

        return Inertia::render('Admin/Settings/Edit', [
            'settings' => collect(self::KEYS)->mapWithKeys(fn ($key) => [$key => $values[$key] ?? ''])->all(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $oldOgImage = Setting::get('og_image_default');
        if ($oldOgImage && $oldOgImage !== ($data['og_image_default'] ?? null)) {
            $this->imageProcessor->delete($oldOgImage);
        }

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        ActivityLog::record('updated', 'settings', 'Pengaturan situs');

        return back()->with('status', 'Pengaturan berhasil disimpan.');
    }
}
