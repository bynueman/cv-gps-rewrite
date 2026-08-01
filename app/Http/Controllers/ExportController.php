<?php

namespace App\Http\Controllers;

use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class ExportController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Export', [
            'seo' => (new Seo(
                title: 'Ekspor & Kemitraan',
                description: 'Kami menyambut percakapan dengan distributor, peritel, dan calon buyer.',
                canonical: url('/export'),
            ))->toArray(),
        ]);
    }

    public function legality(): Response
    {
        return Inertia::render('ExportLegality', [
            'seo' => (new Seo(
                title: 'Legalitas & Sertifikasi',
                description: 'Dokumen legalitas usaha dan sertifikasi produk CV Gama Putra Santosa.',
                canonical: url('/export/legalitas-sertifikasi'),
            ))->toArray(),
        ]);
    }
}
