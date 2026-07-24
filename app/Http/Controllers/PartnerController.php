<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class PartnerController extends Controller
{
    public function index(): Response
    {
        $partners = Partner::where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['name', 'category', 'logo']);

        return Inertia::render('Partners/Index', [
            'partners' => $partners,
            'seo' => (new Seo(
                title: 'Mitra Kami',
                description: 'Jaringan mitra CV Gama Putra Santosa — ritel, hotel, restoran, pusat oleh-oleh, cakery, dan institusi di seluruh Indonesia.',
                canonical: url('/mitra'),
            ))->toArray(),
        ]);
    }
}
