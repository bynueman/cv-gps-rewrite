<?php

namespace App\Http\Controllers;

use App\Models\Certification;
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
        $certifications = Certification::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'issuer', 'category', 'logo', 'pdf_url', 'valid_until']);

        return Inertia::render('ExportLegality', [
            'certifications' => $certifications,
            'seo'            => (new Seo(
                title: 'Legalitas & Sertifikasi',
                description: 'Dokumen legalitas usaha dan sertifikasi produk CV Gama Putra Santosa.',
                canonical: url('/export/legalitas-sertifikasi'),
            ))->toArray(),
        ]);
    }
}

