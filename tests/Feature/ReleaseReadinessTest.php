<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\BrandSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Database\MariaDbConnection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReleaseReadinessTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_COLUMNS = ['shopee_url', 'net_weight', 'seo_title_id', 'seo_title_en',
        'meta_description_id', 'meta_description_en', 'image_alt_id', 'image_alt_en'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([BrandSeeder::class, ProductSeeder::class]);
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_25_100000_add_commerce_and_seo_to_products_table.php');
    }

    private function formPayload(Product $product): array
    {
        $payload = $product->only(['brand_id', 'slug', 'group', 'packaging', 'color', 'color_dark',
            'image', 'image_thumb', 'image_og', 'featured', 'placeholder', 'is_active', 'sort_order']);
        foreach (['name', 'short', 'personality', 'serving', 'description'] as $field) {
            $payload[$field] = ['id' => $product->{$field.'_id'} ?? '', 'en' => $product->{$field.'_en'} ?? ''];
        }
        foreach (['highlights', 'notes'] as $field) {
            $payload[$field] = ['id' => implode("\n", array_column($product->$field ?? [], 'id')),
                'en' => implode("\n", array_column($product->$field ?? [], 'en'))];
        }

        return $payload;
    }

    public function test_disabled_defaults_allow_the_website_without_a_story_connection(): void
    {
        $this->assertFalse(config('stories.enabled'));
        $this->assertFalse(config('stories.policies_approved'));
        $this->assertNull(config('database.connections.stories'));
        foreach (['/', '/products', '/products/kuicip', '/products/putri-teko', '/products/kuicip/original',
            '/news', '/mitra', '/contact', '/export', '/export/legalitas-sertifikasi'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/kuicip/cerita')->assertOk()->assertInertia(fn ($page) => $page->where('enabled', false));
        $this->getJson('/kuicip/cerita/feed?enabled=1')->assertNotFound();
        foreach (['/kuicip/cerita', '/kuicip/cerita/removal', '/kuicip/cerita/1/react', '/kuicip/cerita/1/report'] as $url) {
            $this->postJson($url, ['enabled' => true, 'STORIES_ENABLED' => true])->assertNotFound();
        }
        $this->actingAs(User::factory()->create(['role' => 'superadmin']));
        $this->get('/admin/stories')->assertNotFound();
        $this->patch('/admin/stories/1', ['status' => 'approved'])->assertNotFound();
        $this->patch('/admin/story-reports/1', ['status' => 'resolved'])->assertNotFound();
        $this->artisan('stories:prune')->assertSuccessful();
        $this->assertSame(['sqlite'], array_keys(DB::getConnections()));
        $this->assertFalse(Schema::hasTable('stories'));
        Http::assertNothingSent();
    }

    public function test_approval_flag_also_blocks_submission_without_opening_story_storage(): void
    {
        config(['stories.enabled' => true]);
        $this->postJson('/kuicip/cerita', [])->assertStatus(503);
        $this->assertSame(['sqlite'], array_keys(DB::getConnections()));
        Http::assertNothingSent();
    }

    public function test_product_migration_preserves_existing_rows_and_supports_legacy_writes(): void
    {
        $migration = $this->migration();
        $migration->down();
        try {
            $columns = Schema::getColumnListing('products');
            $before = DB::table('products')->orderBy('id')->get($columns)->toArray();
            $migration->up();
            $this->assertEquals($before, DB::table('products')->orderBy('id')->get($columns)->toArray());
            foreach (self::NEW_COLUMNS as $column) {
                $this->assertSame(0, DB::table('products')->whereNotNull($column)->count());
            }
            $product = Product::firstOrFail();
            $product->update(['seo_title_id' => 'Metadata sintetis']);
            DB::table('products')->where('id', $product->id)->update(['name_id' => 'Nama dari field lama']);
            $this->assertSame('Metadata sintetis', $product->fresh()->seo_title_id);
            $this->assertSame('Nama dari field lama', $product->fresh()->name_id);
            $legacy = (array) $before[0];
            unset($legacy['id']);
            $legacy['slug'] = 'legacy-insert-synthetic';
            $newId = DB::table('products')->insertGetId($legacy);
            $this->assertNull(DB::table('products')->where('id', $newId)->value('shopee_url'));
        } finally {
            if (! Schema::hasColumn('products', 'shopee_url')) {
                $migration->up();
            }
        }
    }

    public function test_product_migration_compiles_additive_mysql_and_mariadb_sql_without_connecting(): void
    {
        $originalSchema = Schema::getFacadeRoot();
        try {
            foreach ([MySqlConnection::class, MariaDbConnection::class] as $driver) {
                $connection = new $driver(fn () => throw new \RuntimeException('No database connection is allowed in SQL compilation.'), 'synthetic');
                Schema::swap($connection->getSchemaBuilder());
                $queries = $connection->pretend(fn () => $this->migration()->up());
                $this->assertCount(8, $queries);
                foreach ($queries as $query) {
                    $this->assertStringStartsWith('alter table `products` add ', $query['query']);
                    $this->assertStringEndsWith(' null', $query['query']);
                }
            }
        } finally {
            Schema::swap($originalSchema);
        }
    }

    public function test_public_reads_and_cms_edit_fall_back_safely_before_product_migration(): void
    {
        $migration = $this->migration();
        $migration->down();
        try {
            foreach (['/', '/products', '/products/kuicip', '/products/putri-teko'] as $url) {
                $this->get($url)->assertOk();
            }
            foreach (['kuicip', 'putri-teko'] as $brand) {
                $product = Product::where('brand_id', Brand::where('key', $brand)->value('id'))->firstOrFail();
                $this->get('/products/'.$brand.'/'.$product->slug)->assertOk()->assertInertia(fn ($page) => $page
                    ->where('product.shopeeUrl', config('company.shopee_storefront'))->where('product.netWeight', null));
                $this->actingAs(User::factory()->create())->get(route('admin.products.edit', $product))
                    ->assertOk()->assertInertia(fn ($page) => $page->where('product.seo_title_id', null));
            }
        } finally {
            $migration->up();
        }
    }

    public function test_new_cms_fields_require_migration_before_saving(): void
    {
        $product = Product::firstOrFail();
        $payload = array_merge($this->formPayload($product), ['seo_title_id' => 'Uji sebelum migration']);
        $this->migration()->down();
        try {
            $this->withoutExceptionHandling()->actingAs(User::factory()->create())
                ->patch(route('admin.products.update', $product), $payload);
            $this->fail('New CMS writes must not be treated as compatible with the old schema.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('seo_title_id', $exception->getMessage());
        } finally {
            $this->migration()->up();
        }
    }

    public function test_cms_metadata_save_preserves_other_attributes_and_uploaded_media(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'editor']));
        $media = $this->post(route('admin.upload'), ['file' => UploadedFile::fake()->image('acceptance.png', 100, 100),
            'context' => 'products', 'variant' => 'standard'])->assertOk()->json();
        $product = Product::firstOrFail();
        $product->update(['image' => $media['image'], 'image_thumb' => $media['imageThumb'], 'image_og' => $media['imageOg']]);
        $before = Arr::except($product->getRawOriginal(), ['updated_at', 'seo_title_id', 'meta_description_id', 'image_alt_id']);
        $imagePath = public_path(ltrim($media['image'], '/'));
        $imageHash = hash_file('sha256', $imagePath);
        $payload = array_merge($this->formPayload($product), ['seo_title_id' => 'Judul terverifikasi sintetis',
            'meta_description_id' => 'Metadata fixture acceptance lokal.', 'image_alt_id' => 'Gambar fixture lokal']);
        $this->patch(route('admin.products.update', $product), $payload)
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.products.index'));
        $this->assertSame($before, Arr::except($product->fresh()->getRawOriginal(), ['updated_at', 'seo_title_id', 'meta_description_id', 'image_alt_id']));
        $this->assertSame($imageHash, hash_file('sha256', $imagePath));
        $this->assertSame($payload['seo_title_id'], $product->fresh()->seo_title_id);
        Http::assertNothingSent();
    }

    public function test_routes_can_be_prepared_and_compiled_for_cache_with_stories_disabled(): void
    {
        $routes = $this->app['router']->getRoutes();
        foreach ($routes as $route) {
            $route->prepareForSerialization();
        }
        $routes->refreshNameLookups();
        $compiled = $routes->compile();
        $cached = eval('return '.var_export($compiled, true).';');
        $this->assertSame('kuicip/cerita', $cached['attributes']['stories.store']['uri']);
        $this->assertSame('admin/stories/{story}', $cached['attributes']['admin.stories.update']['uri']);
        $this->assertContains('superadmin', $cached['attributes']['admin.stories.update']['action']['middleware']);
        $this->assertFalse(config('stories.enabled'));
        $this->assertSame(['sqlite'], array_keys(DB::getConnections()));
    }
}
