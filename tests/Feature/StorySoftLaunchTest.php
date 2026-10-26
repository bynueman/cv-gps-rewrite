<?php

namespace Tests\Feature;

use App\Models\Story;
use App\Models\User;
use App\Support\StoryDatabase;
use Database\Seeders\BrandSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StorySoftLaunchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BrandSeeder::class);
    }

    public function test_admin_preparation_allows_superadmin_but_blocks_every_public_endpoint(): void
    {
        config(['stories.enabled' => true, 'stories.policies_approved' => false,
            'database.connections.stories' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        $this->artisan('migrate', ['--database' => 'stories',
            '--path' => 'database/migrations/stories/2026_10_25_110000_create_story_tables.php', '--force' => true])->assertSuccessful();

        $this->get('/admin/stories')->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create(['role' => 'editor']))->get('/admin/stories')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'superadmin']))->get('/admin/stories')
            ->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertInertia(fn ($page) => $page->component('Admin/Stories/Index')->where('site.storyEnabled', true));
        $this->get('/kuicip/cerita')->assertOk()->assertInertia(fn ($page) => $page->where('enabled', false));
        $this->getJson('/kuicip/cerita/feed')->assertStatus(503);
        foreach (['/kuicip/cerita/challenge', '/kuicip/cerita', '/kuicip/cerita/removal', '/kuicip/cerita/1/react', '/kuicip/cerita/1/report'] as $uri) {
            $this->postJson($uri, [])->assertStatus(503);
        }
        $this->assertSame(0, Story::count());
        $this->assertFalse(DB::connection('sqlite')->getSchemaBuilder()->hasTable('stories'));
        Http::assertNothingSent();
    }

    public function test_production_landing_and_api_share_the_policy_gate(): void
    {
        config(['stories.enabled' => true, 'stories.policies_approved' => true]);
        app()->detectEnvironment(fn () => 'production');
        try {
            foreach (['draft-v1', ' DRAFT-v2 ', '', str_repeat('x', 81)] as $version) {
                config(['stories.policy_version' => $version]);
                $this->get('/kuicip/cerita')->assertOk()
                    ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
                    ->assertInertia(fn ($page) => $page->where('enabled', false)->where('site.localPreview', false));
                $this->getJson('/kuicip/cerita/feed')->assertStatus(503);
            }
            config(['stories.policy_version' => 'owner-approved-example-v1']);
            $this->get('/kuicip/cerita')->assertOk()->assertInertia(fn ($page) => $page->where('enabled', true));
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
        Http::assertNothingSent();
    }

    public function test_database_guard_allows_the_migrators_temporary_stories_default(): void
    {
        $original = config('database');
        try {
            config(['database.connections.website' => ['driver' => 'mysql', 'host' => 'localhost', 'database' => 'website_test'],
                'database.connections.stories' => ['driver' => 'mysql', 'host' => '127.0.0.1', 'database' => 'stories_test', 'username' => 'stories_only']]);
            app('migrator')->usingConnection('stories', function (): void {
                $this->assertSame('stories', config('database.default'));
                StoryDatabase::ensureConfigured();
            });
            $this->assertSame('sqlite', config('database.default'));
            $migration = require database_path('migrations/stories/2026_10_25_110000_create_story_tables.php');
            $this->assertSame('stories', $migration->getConnection());
        } finally {
            config(['database' => $original]);
        }
    }

    public function test_guard_rejects_main_database_reuse_even_with_host_alias_url_or_migrator_default(): void
    {
        $original = config('database');
        try {
            foreach ([
                ['driver' => 'mysql', 'host' => 'localhost', 'database' => 'website_test'],
                ['driver' => 'mariadb', 'url' => 'mysql://website_user:fixture-only@localhost/website_test', 'database' => 'overridden_by_url'],
            ] as $website) {
                config(['database.default' => 'stories', 'database.connections.website' => $website,
                    'database.connections.stories' => ['driver' => 'mysql', 'host' => '127.0.0.1', 'database' => 'website_test', 'username' => 'stories_only']]);
                try {
                    StoryDatabase::ensureConfigured();
                    $this->fail('Main website database must be rejected.');
                } catch (\RuntimeException $error) {
                    $this->assertStringContainsString('must differ', $error->getMessage());
                }
            }
        } finally {
            config(['database' => $original]);
        }
    }

    public function test_sqlite_memory_and_local_fake_configuration_are_not_production_options(): void
    {
        config(['database.connections.stories' => ['driver' => 'sqlite', 'database' => ':memory:'], 'stories.local_preview' => true]);
        app()->detectEnvironment(fn () => 'production');
        try {
            $this->assertFalse(StoryDatabase::isLocalPreview());
            $this->expectException(\RuntimeException::class);
            StoryDatabase::ensureConfigured();
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_soft_launch_remains_unindexed_and_outside_the_sitemap_in_both_stages(): void
    {
        foreach ([false, true] as $approved) {
            config(['stories.enabled' => true, 'stories.policies_approved' => $approved, 'stories.policy_version' => 'test-v1']);
            $this->get('/sitemap.xml')->assertOk()->assertDontSee('/kuicip/cerita');
            $this->get('/kuicip/cerita')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        }
    }

    public function test_soft_launch_does_not_schedule_automatic_story_deletion(): void
    {
        config(['stories.enabled' => true, 'stories.policies_approved' => true]);
        $commands = array_map(fn ($event) => (string) $event->command, app(Schedule::class)->events());
        $this->assertStringNotContainsString('stories:prune', implode("\n", $commands));
    }
}
