<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Stat cards, activity feed, and visitor charts land here in Fase 6
     * (Dashboard & Statistik Pengunjung) — this is the navigable shell
     * for now so login/sidebar have a real landing page.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard');
    }
}
