<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use App\Rules\ShopeeUrl;
use Database\Seeders\BrandSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProductExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BrandSeeder::class);
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge(['brand_id' => Brand::where('key', 'kuicip')->firstOrFail()->id,
            'slug' => 'synthetic-product', 'name_id' => 'Produk Sintetis', 'name_en' => 'Synthetic Product',
            'short_id' => 'Ringkasan produk sintetis.', 'short_en' => 'Synthetic summary.',
            'personality_id' => 'Uji', 'personality_en' => 'Test', 'description_id' => str_repeat('Long description. ', 80),
            'description_en' => 'Test body.', 'highlights' => [], 'notes' => [], 'color' => '#E29A12', 'color_dark' => '#9C6508',
            'is_active' => true], $overrides));
    }

    public function test_catalogs_and_details_preserve_urls_and_use_lightweight_cards(): void
    {
        $product = $this->product();
        $this->get('/products/kuicip')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Products/KuicipCatalog')->has('products', 1)->missing('products.0.description')
            ->missing('products.0.highlights')->where('products.0.slug', $product->slug));
        $this->get('/products/putri-teko')->assertOk()->assertInertia(fn ($page) => $page->component('Products/TekoCatalog')->has('products', 0));
        $this->get('/products/kuicip/'.$product->slug)->assertOk()->assertInertia(fn ($page) => $page
            ->where('seo.canonical', url('/products/kuicip/'.$product->slug))->has('product.description'));
    }

    public function test_optional_commerce_and_metadata_render_without_fabricated_offers(): void
    {
        $product = $this->product(['shopee_url' => 'https://shopee.co.id/product/123/456',
            'seo_title_id' => 'Judul Produk Terverifikasi', 'seo_title_en' => 'Verified Product Title', 'meta_description_id' => 'Deskripsi metadata yang diisi pengelola.',
            'image_alt_id' => 'Kemasan produk sintetis', 'net_weight' => '80 g']);
        $response = $this->get('/products/kuicip/'.$product->slug)->assertOk();
        $response->assertSee('Judul Produk Terverifikasi', false)->assertSee('Deskripsi metadata yang diisi pengelola.', false);
        $response->assertInertia(fn ($page) => $page->where('product.shopeeUrl', $product->shopee_url)->where('product.netWeight', '80 g')->where('seoEnglish.title', 'Verified Product Title')->where('seoEnglish.description', 'Synthetic summary.'));
        $this->assertStringNotContainsString('"@type":"Offer"', $response->getContent());
        $product->update(['shopee_url' => null]);
        $this->get('/products/kuicip/'.$product->slug)->assertInertia(fn ($page) => $page->where('product.shopeeUrl', config('company.shopee_storefront')));
    }

    public function test_both_brands_resolve_safe_shopee_destinations_without_changing_product_data(): void
    {
        $fallback = 'https://shopee.co.id/gpsfood?categoryId=100629&entryPoint=ShopByPDP&itemId=49919300654';
        $this->assertSame($fallback, config('company.shopee_storefront'));
        foreach (['kuicip', 'putri-teko'] as $brand) {
            $product = $this->product(['brand_id' => Brand::where('key', $brand)->value('id'), 'slug' => $brand.'-commerce']);
            foreach ([[null, $fallback], ['https://shopee.co.id/product/12/34', 'https://shopee.co.id/product/12/34'],
                ['https://shopee.co.id.evil.test/item', $fallback], ['javascript:alert(1)', $fallback]] as [$stored, $expected]) {
                $product->update(['shopee_url' => $stored]);
                $before = $product->fresh()->getRawOriginal();
                $this->get('/products/'.$brand)->assertOk()->assertInertia(fn ($page) => $page->where('products.0.shopeeUrl', $expected));
                $this->get('/products/'.$brand.'/'.$product->slug)->assertOk()->assertInertia(fn ($page) => $page->where('product.shopeeUrl', $expected));
                $this->assertSame($before, $product->fresh()->getRawOriginal());
            }
        }
    }

    public function test_invalid_configured_fallback_is_not_exposed_but_valid_override_still_works(): void
    {
        config(['company.shopee_storefront' => 'https://evil.test/redirect']);
        $product = $this->product();
        $this->get('/products/kuicip')->assertInertia(fn ($page) => $page->where('products.0.shopeeUrl', null));
        $product->update(['shopee_url' => 'https://shp.ee/valid']);
        $this->get('/products/kuicip/'.$product->slug)->assertInertia(fn ($page) => $page->where('product.shopeeUrl', 'https://shp.ee/valid'));
    }

    public function test_shopee_rule_rejects_unsafe_and_spoofed_destinations(): void
    {
        foreach (['https://shopee.co.id/product/1/2', 'https://s.shopee.co.id/example', 'https://shp.ee/example'] as $url) {
            $this->assertFalse(Validator::make(['url' => $url], ['url' => [new ShopeeUrl]])->fails());
        }
        foreach (['http://shopee.co.id/item', 'javascript:alert(1)', 'https://shopee.co.id.evil.test/item',
            'https://shopee.co.id@evil.test/item', 'https://user:pass@shopee.co.id/item', 'https://shopee.co.id:8443/item', ['bad']] as $url) {
            $this->assertTrue(Validator::make(['url' => $url], ['url' => [new ShopeeUrl]])->fails());
        }
    }

    public function test_cms_persists_optional_fields_and_rejects_invalid_links(): void
    {
        $product = $this->product();
        $payload = ['brand_id' => $product->brand_id, 'slug' => $product->slug,
            'name' => ['id' => 'Nama Uji', 'en' => 'Test Name'], 'short' => ['id' => 'Ringkas', 'en' => 'Short'],
            'personality' => ['id' => 'Uji', 'en' => 'Test'], 'serving' => ['id' => '', 'en' => ''],
            'description' => ['id' => 'Deskripsi.', 'en' => 'Description.'], 'color' => '#E29A12', 'color_dark' => '#9C6508',
            'shopee_url' => 'https://shopee.co.id/product/1/2', 'seo_title_id' => 'Metadata dari CMS'];
        $this->actingAs(User::factory()->create())->patch(route('admin.products.update', $product), $payload)->assertRedirect();
        $this->assertSame($payload['shopee_url'], $product->fresh()->shopee_url);
        $this->assertSame('Metadata dari CMS', $product->fresh()->seo_title_id);
        $legacyPayload = $payload;
        unset($legacyPayload['shopee_url'], $legacyPayload['seo_title_id']);
        $this->patch(route('admin.products.update', $product), $legacyPayload)->assertRedirect();
        $this->assertSame($payload['shopee_url'], $product->fresh()->shopee_url);
        $this->assertSame('Metadata dari CMS', $product->fresh()->seo_title_id);
        $this->patch(route('admin.products.update', $product), array_merge($payload, ['shopee_url' => null]))->assertRedirect();
        $this->assertNull($product->fresh()->shopee_url);
        $payload['shopee_url'] = 'https://evil.test';
        $this->patch(route('admin.products.update', $product), $payload)->assertSessionHasErrors('shopee_url');
    }

    public function test_catalog_brand_queries_do_not_grow_per_product(): void
    {
        $this->product();
        $this->get('/products/kuicip')->assertOk();
        foreach (range(1, 5) as $number) {
            $this->product(['slug' => 'synthetic-'.$number]);
        }
        DB::enableQueryLog();
        $this->get('/products/kuicip')->assertOk();
        $brandQueries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], '"brands"'));
        DB::disableQueryLog();
        $this->assertLessThanOrEqual(3, $brandQueries->count());
    }
}
