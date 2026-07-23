<?php

namespace App\Http\Controllers;

use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Contact', [
            'seo' => (new Seo(
                title: 'Hubungi Kami',
                description: 'Untuk pertanyaan produk, distribusi, ekspor, maupun kolaborasi — tim kami siap membantu.',
                canonical: url('/contact'),
            ))->toArray(),
        ]);
    }
}
