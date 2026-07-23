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
];
