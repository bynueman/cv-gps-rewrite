<?php

namespace Tests\Feature;

use App\Models\PageView;
use App\Models\PageViewDaily;
use App\Models\User;
use Database\Seeders\BrandSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageViewTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BrandSeeder::class);
    }

    public function test_a_public_page_visit_is_recorded(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0 Safari/537.36'])
            ->get('/contact');

        $this->assertDatabaseHas('page_views', ['path' => '/contact', 'browser' => 'Chrome', 'device' => 'desktop']);
    }

    public function test_admin_routes_are_not_tracked(): void
    {
        $this->actingAs(User::factory()->create())
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 Chrome/120.0 Safari/537.36'])
            ->get('/admin');

        $this->assertDatabaseCount('page_views', 0);
    }

    public function test_bot_requests_are_not_tracked(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'])
            ->get('/');

        $this->assertDatabaseCount('page_views', 0);
    }

    public function test_no_raw_ip_is_ever_stored(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 Chrome/120.0 Safari/537.36'])
            ->get('/');

        $view = PageView::first();
        $this->assertNotNull($view);
        $this->assertStringNotContainsString('127.0.0.1', $view->visitor_hash);
        $this->assertSame(64, strlen($view->visitor_hash)); // sha256 hex length, not a raw IP
    }

    public function test_aggregation_command_rolls_up_hits_and_uniques(): void
    {
        PageView::create(['path' => '/', 'viewed_on' => today(), 'browser' => 'Chrome', 'device' => 'desktop', 'visitor_hash' => 'aaa']);
        PageView::create(['path' => '/', 'viewed_on' => today(), 'browser' => 'Chrome', 'device' => 'desktop', 'visitor_hash' => 'aaa']); // same visitor, 2nd hit
        PageView::create(['path' => '/', 'viewed_on' => today(), 'browser' => 'Firefox', 'device' => 'mobile', 'visitor_hash' => 'bbb']);

        $this->artisan('app:aggregate-page-views')->assertSuccessful();

        $daily = PageViewDaily::where('path', '/')->where('date', today())->first();
        $this->assertNotNull($daily);
        $this->assertSame(3, $daily->hits);
        $this->assertSame(2, $daily->uniques);
        $this->assertSame(2, $daily->browser_json['Chrome']);
        $this->assertSame(1, $daily->browser_json['Firefox']);
    }

    public function test_aggregation_prunes_raw_rows_older_than_30_days(): void
    {
        PageView::create([
            'path' => '/', 'viewed_on' => today()->subDays(45),
            'browser' => 'Chrome', 'device' => 'desktop', 'visitor_hash' => 'old',
        ]);
        PageView::create([
            'path' => '/', 'viewed_on' => today(),
            'browser' => 'Chrome', 'device' => 'desktop', 'visitor_hash' => 'new',
        ]);

        $this->artisan('app:aggregate-page-views')->assertSuccessful();

        $this->assertDatabaseCount('page_views', 1);
        $this->assertDatabaseHas('page_views', ['visitor_hash' => 'new']);
        // The old day's numbers survive in the rollup even after the raw row is pruned.
        $this->assertTrue(
            PageViewDaily::where('path', '/')->whereDate('date', today()->subDays(45))->exists()
        );
    }
}
