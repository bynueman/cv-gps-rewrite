<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\ContactMessage;
use App\Models\PageViewDaily;
use App\Models\Partner;
use App\Models\User;
use Database\Seeders\BrandSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_stat_cards_reflect_active_and_total_counts(): void
    {
        $this->seed(BrandSeeder::class);
        $this->seed(ProductSeeder::class);

        Partner::create(['name' => 'A', 'category' => 'ritel', 'sort_order' => 0, 'is_active' => true]);
        Partner::create(['name' => 'B', 'category' => 'ritel', 'sort_order' => 0, 'is_active' => false]);

        ContactMessage::create(['name' => 'X', 'email' => 'x@example.com', 'topic' => 'Umum', 'message' => 'Hi', 'is_read' => false]);
        ContactMessage::create(['name' => 'Y', 'email' => 'y@example.com', 'topic' => 'Umum', 'message' => 'Hi', 'is_read' => true]);

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.partners.active', 1)
                ->where('stats.partners.total', 2)
                ->where('stats.messages.active', 1)
                ->where('stats.messages.total', 2)
            );
    }

    public function test_recent_activity_shows_latest_ten_entries_newest_first(): void
    {
        $admin = User::factory()->create();

        foreach (range(1, 12) as $i) {
            ActivityLog::create([
                'user_id' => $admin->id,
                'action' => 'created',
                'module' => 'products',
                'item_label' => "Produk {$i}",
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('recentActivity', 10)
                ->where('recentActivity.0.item_label', 'Produk 12')
            );
    }

    public function test_visitor_chart_reads_from_aggregate_table_only(): void
    {
        PageViewDaily::create([
            'date' => today(), 'path' => '/', 'hits' => 10, 'uniques' => 7,
            'browser_json' => ['Chrome' => 8, 'Firefox' => 2],
            'device_json' => ['desktop' => 6, 'mobile' => 4],
        ]);

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('browserBreakdown.Chrome', 8)
                ->where('deviceBreakdown.desktop', 6)
                ->where('topPages.0.path', '/')
                ->where('topPages.0.hits', 10)
            );
    }
}
