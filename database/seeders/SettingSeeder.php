<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeds the settings table from the values currently hardcoded in
 * config/company.php, so Pengaturan (Fase 3) has real starting data
 * instead of blanks. The site itself keeps reading config/company.php
 * until Fase 3 rewires it to read from here.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'whatsapp' => config('company.whatsapp'),
            'email_primary' => config('company.email'),
            'email_secondary' => config('company.email_alt'),
            'address' => config('company.address'),
            'instagram_kuicip' => '',
            'instagram_putriteko' => '',
            'operating_hours' => config('company.hours.id'),
            'site_title' => config('app.name'),
            'meta_description_default' => 'CV Gama Putra Santosa (GPS Group) adalah perusahaan makanan & minuman Yogyakarta sejak 2011. Rumah bagi Kuicip, Putri Teko, dan Ngayogyakarya.',
            'og_image_default' => '/images/og-default.webp',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
