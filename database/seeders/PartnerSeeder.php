<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Seeder;

/**
 * Real client roster from the company's portfolio deck (Agustus 2025).
 * Logos are static assets (public/images/partners/*.webp, pre-compressed
 * the same way ImageProcessor::processLogo() would) rather than admin
 * uploads — same pattern as Brand/Product seed images — so a fresh
 * deploy has them without a manual re-upload step.
 */
class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        $featured = ['cavinton', 'the-alana', 'indomaret', 'lotte-mart', 'transmart', 'amanda-brownies', 'hamzah-batik'];

        $partners = [
            // Hotel
            ['name' => 'Cavinton', 'category' => 'hotel', 'slug' => 'cavinton'],
            ['name' => 'The Alana', 'category' => 'hotel', 'slug' => 'the-alana'],
            ['name' => 'Marriott', 'category' => 'hotel', 'slug' => 'marriott'],

            // Restoran
            ['name' => 'Vilo Gelato', 'category' => 'restaurant', 'slug' => 'vilo-gelato'],
            ['name' => 'Obelix Village', 'category' => 'restaurant', 'slug' => 'obelix-village'],
            ['name' => 'Kalluna', 'category' => 'restaurant', 'slug' => 'kalluna'],
            ['name' => 'Kebon Ndalem', 'category' => 'restaurant', 'slug' => 'kebon-ndalem'],
            ['name' => 'Omah Cantrik By Raminten', 'category' => 'restaurant', 'slug' => 'omah-cantrik'],
            ['name' => 'La Barka', 'category' => 'restaurant', 'slug' => 'la-barka'],
            ['name' => 'Ka Desa', 'category' => 'restaurant', 'slug' => 'ka-desa'],
            ['name' => 'Dadap Sumilir', 'category' => 'restaurant', 'slug' => 'dadap-sumilir'],

            // Pusat Oleh-Oleh
            ['name' => 'Bakpia Kukus Tugu', 'category' => 'oleh-oleh', 'slug' => 'bakpia-kukus-tugu'],
            ['name' => 'PT. Karya Rasa Indonesia', 'category' => 'oleh-oleh', 'slug' => 'karya-rasa-indonesia'],
            ['name' => 'DJAVA', 'category' => 'oleh-oleh', 'slug' => 'djava'],
            ['name' => 'Pia Juwara Satoe', 'category' => 'oleh-oleh', 'slug' => 'pia-juwara-satoe'],
            ['name' => 'Hamzah Batik', 'category' => 'oleh-oleh', 'slug' => 'hamzah-batik'],
            ['name' => 'Olah Oleh Raminten', 'category' => 'oleh-oleh', 'slug' => 'olah-oleh-raminten'],
            ['name' => 'Jatim Park 3', 'category' => 'oleh-oleh', 'slug' => 'jatim-park-3'],
            ['name' => 'Jogja Pasaraya Group', 'category' => 'oleh-oleh', 'slug' => 'jogja-pasaraya'],

            // Ritel
            ['name' => 'Indomaret', 'category' => 'ritel', 'slug' => 'indomaret'],
            ['name' => 'Lotte Mart', 'category' => 'ritel', 'slug' => 'lotte-mart'],
            ['name' => 'Alfa Midi', 'category' => 'ritel', 'slug' => 'alfa-midi'],
            ['name' => 'Mirota Pasaraya', 'category' => 'ritel', 'slug' => 'mirota-pasaraya'],
            ['name' => 'Mirota Kampus', 'category' => 'ritel', 'slug' => 'mirota-kampus'],
            ['name' => 'Transmart', 'category' => 'ritel', 'slug' => 'transmart'],
            ['name' => 'PT. Hadi Retail Indonesia', 'category' => 'ritel', 'slug' => 'hadi-retail'],
            ['name' => 'Palapa Toserba', 'category' => 'ritel', 'slug' => 'palapa-toserba'],
            ['name' => 'Chicco Swalayan', 'category' => 'ritel', 'slug' => 'chicco-swalayan'],
            ['name' => 'Graha Airi', 'category' => 'ritel', 'slug' => 'graha-airi'],
            ['name' => 'Lawson', 'category' => 'ritel', 'slug' => 'lawson'],

            // Cakery
            ['name' => 'Amanda Brownies', 'category' => 'cakery', 'slug' => 'amanda-brownies'],
            ['name' => 'Papa Cookies', 'category' => 'cakery', 'slug' => 'papa-cookies'],
            ['name' => 'Chiffon-Qu', 'category' => 'cakery', 'slug' => 'chiffon-qu'],
            ['name' => 'Kenes', 'category' => 'cakery', 'slug' => 'kenes'],

            // Institusi
            ['name' => 'Fakultas Hukum UGM', 'category' => 'institusi', 'slug' => 'fh-ugm'],
            ['name' => 'Bank Indonesia KPw DIY', 'category' => 'institusi', 'slug' => 'bank-indonesia-diy'],
            ['name' => 'Dinas Koperasi dan UKM DIY', 'category' => 'institusi', 'slug' => 'dinkop-ukm-diy'],
            ['name' => 'KPU Purworejo', 'category' => 'institusi', 'slug' => 'kpu-purworejo'],
        ];

        foreach ($partners as $index => $partner) {
            Partner::updateOrCreate(
                ['name' => $partner['name'], 'category' => $partner['category']],
                [
                    'logo' => "/images/partners/{$partner['slug']}.webp",
                    'sort_order' => $index,
                    'is_active' => true,
                    'featured' => in_array($partner['slug'], $featured, true),
                ]
            );
        }
    }
}
