<?php

namespace Tests\Feature;

use App\Models\Partner;
use Database\Seeders\BrandSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnersLogoWallTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BrandSeeder::class);
    }

    public function test_homepage_lists_only_active_featured_partners_in_order(): void
    {
        Partner::create(['name' => 'Aktif Z', 'category' => 'ritel', 'sort_order' => 1, 'is_active' => true, 'featured' => true]);
        Partner::create(['name' => 'Aktif A', 'category' => 'ritel', 'sort_order' => 0, 'is_active' => true, 'featured' => true]);
        Partner::create(['name' => 'Nonaktif', 'category' => 'ritel', 'sort_order' => 0, 'is_active' => false, 'featured' => true]);
        Partner::create(['name' => 'Tidak Featured', 'category' => 'ritel', 'sort_order' => 0, 'is_active' => true, 'featured' => false]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('partners', 2)
                ->where('partners.0.name', 'Aktif A')
                ->where('partners.1.name', 'Aktif Z')
            );
    }

    public function test_homepage_partners_prop_is_empty_when_none_active(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('partners', 0));
    }
}
