<?php

// Fixed static asset paths (2 brands + 1 corporate mark) — not
// admin-editable, matches the original app's content.ts `logos` /
// `groupShots` lookup maps. Paths are relative to public/.
return [
    'logos' => [
        'gps' => '/images/logo/gps.webp',
        'kuicip' => '/images/logo/kuicip.webp',
        'putri-teko' => '/images/logo/putriteko.webp',
    ],
    'group_shots' => [
        'kuicip' => '/images/kuicip-nobg.webp',
        'putri-teko' => '/images/putriteko-nobg.webp',
    ],

    // Institutions GPS Group is a fostered SME ("binaan") of — a footer
    // credit, not a business relationship, so it's kept separate from the
    // DB-backed Partner model used for retail/hotel/restaurant mitra.
    'fostered_by' => [
        ['name' => 'Bank Indonesia', 'logo' => '/images/binaan/bank-indonesia.webp'],
        ['name' => 'Pertamina Foundation', 'logo' => '/images/binaan/pertamina-foundation.webp'],
    ],
];
