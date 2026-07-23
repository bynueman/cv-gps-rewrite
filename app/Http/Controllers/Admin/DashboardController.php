<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\ContactMessage;
use App\Models\PageViewDaily;
use App\Models\Partner;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'products' => [
                    'active' => Product::where('is_active', true)->count(),
                    'total' => Product::count(),
                ],
                'articles' => [
                    'active' => Article::where('published', true)->count(),
                    'total' => Article::count(),
                ],
                'partners' => [
                    'active' => Partner::where('is_active', true)->count(),
                    'total' => Partner::count(),
                ],
                'messages' => [
                    'active' => ContactMessage::where('is_read', false)->count(),
                    'total' => ContactMessage::count(),
                ],
            ],
            'recentActivity' => ActivityLog::with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc') // tiebreaker — created_at has only second precision
                ->limit(10)
                ->get()
                ->map(fn (ActivityLog $log) => [
                    'id' => $log->id,
                    'user' => $log->user?->name ?? 'Sistem',
                    'action' => $log->action,
                    'module' => $log->module,
                    'item_label' => $log->item_label,
                    'created_at' => $log->created_at->toIso8601String(),
                ]),
            'visitorChart' => $this->visitorSeries(30),
            'browserBreakdown' => $this->breakdown('browser_json', 30),
            'deviceBreakdown' => $this->breakdown('device_json', 30),
            'topPages' => $this->topPages(30),
        ]);
    }

    /** @return array<int, array{date: string, hits: int, uniques: int}> */
    private function visitorSeries(int $days): array
    {
        $since = Carbon::today()->subDays($days - 1);

        $rows = PageViewDaily::where('date', '>=', $since)
            ->selectRaw('date, SUM(hits) as hits, SUM(uniques) as uniques')
            ->groupBy('date')
            ->get()
            ->keyBy(fn ($row) => $row->date->toDateString());

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $since->copy()->addDays($i)->toDateString();
            $row = $rows->get($date);
            $series[] = [
                'date' => $date,
                'hits' => (int) ($row->hits ?? 0),
                'uniques' => (int) ($row->uniques ?? 0),
            ];
        }

        return $series;
    }

    /** @return array<string, int> */
    private function breakdown(string $column, int $days): array
    {
        $since = Carbon::today()->subDays($days - 1);

        $totals = [];
        foreach (PageViewDaily::where('date', '>=', $since)->pluck($column) as $json) {
            foreach (($json ?? []) as $label => $count) {
                $totals[$label] = ($totals[$label] ?? 0) + $count;
            }
        }

        arsort($totals);

        return $totals;
    }

    /** @return array<int, array{path: string, hits: int}> */
    private function topPages(int $days): array
    {
        $since = Carbon::today()->subDays($days - 1);

        return PageViewDaily::where('date', '>=', $since)
            ->selectRaw('path, SUM(hits) as hits')
            ->groupBy('path')
            ->orderByDesc('hits')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['path' => $row->path, 'hits' => (int) $row->hits])
            ->all();
    }
}
