<?php

namespace App\Console\Commands;

use App\Models\PageView;
use App\Models\PageViewDaily;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Folds raw page_views hits into the page_view_daily rollup the
 * dashboard actually reads, then prunes raw rows older than 30 days —
 * keeps the hot table small and the aggregate table cheap to query.
 * Upsert-based, so re-running for a date that's already aggregated just
 * refreshes the counts instead of double-counting.
 */
class AggregatePageViews extends Command
{
    protected $signature = 'app:aggregate-page-views';

    protected $description = 'Aggregate raw page_views into page_view_daily and prune raw rows older than 30 days';

    public function handle(): int
    {
        $groups = PageView::query()
            ->select('viewed_on', 'path')
            ->selectRaw('COUNT(*) as hits')
            ->selectRaw('COUNT(DISTINCT visitor_hash) as uniques')
            ->groupBy('viewed_on', 'path')
            ->get();

        foreach ($groups as $group) {
            $browserCounts = PageView::where('viewed_on', $group->viewed_on)
                ->where('path', $group->path)
                ->select('browser')->selectRaw('COUNT(*) as total')
                ->groupBy('browser')->pluck('total', 'browser');

            $deviceCounts = PageView::where('viewed_on', $group->viewed_on)
                ->where('path', $group->path)
                ->select('device')->selectRaw('COUNT(*) as total')
                ->groupBy('device')->pluck('total', 'device');

            PageViewDaily::updateOrCreate(
                ['date' => $group->viewed_on, 'path' => $group->path],
                [
                    'hits' => $group->hits,
                    'uniques' => $group->uniques,
                    'browser_json' => $browserCounts,
                    'device_json' => $deviceCounts,
                ]
            );
        }

        $this->info("Aggregated {$groups->count()} date/path group(s).");

        $cutoff = now()->subDays(30)->toDateString();
        $deleted = PageView::where('viewed_on', '<', $cutoff)->delete();
        $this->info("Pruned {$deleted} raw page_view row(s) older than {$cutoff}.");

        return self::SUCCESS;
    }
}
