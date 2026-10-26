<?php

return [
    'enabled' => (bool) env('TRANSLATIONS_ENABLED', true),
    'provider' => 'mymemory',
    'glossary_version' => 'gpsfood-2026-10-v1',
    'timeout' => 5,
    'requests_per_job' => 4,
    'characters_per_day' => min(4000, max(0, (int) env('TRANSLATIONS_DAILY_CHARACTERS', 4000))),
    'cache_days' => 30,
    'verified_no_msg_variants' => ['original', 'truffle'],
    'protected_terms' => ['GPS FOOD', 'CV Gama Putra Santosa', 'Kuicip', 'Putri Teko', 'Cassa Lite', 'Original', 'Truffle', 'NO MSG'],
    'glossary' => [
        'GPS FOOD' => 'GPS FOOD', 'CV Gama Putra Santosa' => 'CV Gama Putra Santosa',
        'Kuicip' => 'Kuicip', 'Putri Teko' => 'Putri Teko', 'Cassa Lite' => 'Cassa Lite',
        'Original' => 'Original', 'Truffle' => 'Truffle', 'Original (NO MSG)' => 'Original (NO MSG)',
        'Truffle (NO MSG)' => 'Truffle (NO MSG)', 'NO MSG' => 'NO MSG', 'No MSG' => 'No MSG',
        'Not Oily' => 'Not Oily', 'Gluten Free' => 'Gluten Free',
        'Tanpa Pengawet' => 'No preservatives', 'Teman Ceritamu' => 'Your Story Companion',
    ],
];
