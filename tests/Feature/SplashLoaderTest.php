<?php

namespace Tests\Feature;

use Database\Seeders\BrandSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SplashLoaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BrandSeeder::class);
    }

    public function test_initial_splash_is_outside_react_and_preserves_seo_and_non_js_content(): void
    {
        $response = $this->get('/contact')->assertOk();
        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'id="gpsfood-splash"'));
        $this->assertSame(1, substr_count($html, 'id="gpsfood-splash-controller"'));
        $this->assertSame(1, substr_count($html, 'id="gpsfood-splash-styles"'));
        $this->assertLessThan(strpos($html, '<div id="app"'), strpos($html, '<div id="gpsfood-splash"'));
        $response->assertSee('id="gpsfood-splash" aria-hidden="true" hidden', false)
            ->assertSee('src="'.asset('images/logo/logo-gpsfood.png').'" width="3236" height="1080"', false)
            ->assertSee('background: #FFFFFF;', false)
            ->assertSee('@keyframes gpsfood-splash-safety', false)
            ->assertSee('prefers-reduced-motion: reduce', false)
            ->assertSee('<title inertia>Hubungi Kami — CV Gama Putra Santosa</title>', false)
            ->assertSee('<h1>Hubungi Kami — CV Gama Putra Santosa</h1>', false)
            ->assertSee('<noscript>', false);
    }

    public function test_normal_inertia_navigation_returns_page_data_without_another_splash(): void
    {
        $this->withHeader('X-Inertia', 'true')->get('/contact')->assertOk()
            ->assertHeader('X-Inertia', 'true')->assertJsonPath('component', 'Contact')
            ->assertDontSee('gpsfood-splash');
    }

    public function test_admin_login_gets_one_initial_splash_without_a_public_layout_dependency(): void
    {
        $html = $this->get('/admin/login')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'id="gpsfood-splash"'));
        $this->assertSame(1, substr_count($html, 'id="gpsfood-splash-controller"'));
    }
}
