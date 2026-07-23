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
}
