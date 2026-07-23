<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BrandSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BrandSeeder::class);
    }

    public function test_page_titles_get_the_site_name_suffix_exactly_once(): void
    {
        $response = $this->get('/contact');
        $response->assertOk();
        $response->assertSee('<title inertia>Hubungi Kami — CV Gama Putra Santosa</title>', false);

        // Guard against the double-suffix regression (server template + a
        // second client-side template in app.tsx stacking on each other).
        $response->assertDontSee('CV Gama Putra Santosa — CV Gama Putra Santosa');
    }

    public function test_homepage_title_follows_the_template(): void
    {
        $this->get('/')->assertSee('<title inertia>Kuicip &amp; Putri Teko — CV Gama Putra Santosa</title>', false);
    }

    public function test_homepage_has_organization_json_ld(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('"@type":"Organization"', false);
        $response->assertSee('"name":"CV Gama Putra Santosa"', false);
    }

    public function test_product_page_has_product_and_breadcrumb_json_ld(): void
    {
        $this->seed(ProductSeeder::class);
        $product = \App\Models\Product::where('is_active', true)->first();
        $brandKey = $product->brand->key;

        $response = $this->get("/products/{$brandKey}/{$product->slug}");
        $response->assertOk();
        $response->assertSee('"@type":"Product"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_admin_responses_carry_a_noindex_header(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->get('/admin/login')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_public_pages_do_not_carry_a_noindex_header(): void
    {
        $this->get('/')->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_og_image_falls_back_to_settings_default(): void
    {
        \App\Models\Setting::set('og_image_default', '/images/custom-og.webp');

        $response = $this->get('/contact');
        $response->assertOk();
        $response->assertSee('custom-og.webp', false);
    }

    public function test_non_js_fallback_renders_h1_and_description_inside_the_app_div(): void
    {
        $response = $this->get('/contact');
        $response->assertOk();
        $response->assertSee('<h1>Hubungi Kami — CV Gama Putra Santosa</h1>', false);
    }
}
