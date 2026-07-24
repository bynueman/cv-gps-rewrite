<?php

namespace Tests\Feature;

use App\Models\Partner;
use Database\Seeders\BrandSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnersDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BrandSeeder::class);
    }

    public function test_full_directory_lists_every_active_partner_regardless_of_featured(): void
    {
        Partner::create(['name' => 'Indomaret', 'category' => 'ritel', 'sort_order' => 0, 'is_active' => true, 'featured' => true]);
        Partner::create(['name' => 'Chiffon-Qu', 'category' => 'cakery', 'sort_order' => 0, 'is_active' => true, 'featured' => false]);
        Partner::create(['name' => 'Nonaktif', 'category' => 'ritel', 'sort_order' => 0, 'is_active' => false, 'featured' => true]);

        $this->get('/mitra')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Partners/Index')
                ->has('partners', 2)
            );
    }

    public function test_homepage_shows_only_featured_partners(): void
    {
        Partner::create(['name' => 'Indomaret', 'category' => 'ritel', 'sort_order' => 0, 'is_active' => true, 'featured' => true]);
        Partner::create(['name' => 'Chiffon-Qu', 'category' => 'cakery', 'sort_order' => 0, 'is_active' => true, 'featured' => false]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('partners', 1)
                ->where('partners.0.name', 'Indomaret')
            );
    }

    public function test_institusi_category_is_accepted(): void
    {
        $partner = Partner::create([
            'name' => 'Bank Indonesia KPw DIY',
            'category' => 'institusi',
            'sort_order' => 0,
            'is_active' => true,
            'featured' => false,
        ]);

        $this->assertDatabaseHas('partners', ['id' => $partner->id, 'category' => 'institusi']);
    }

    public function test_admin_can_create_an_institusi_partner(): void
    {
        $admin = \App\Models\User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.partners.store'), [
                'name' => 'Universitas Gadjah Mada',
                'category' => 'institusi',
                'sort_order' => 0,
                'is_active' => true,
                'featured' => false,
            ])
            ->assertRedirect(route('admin.partners.index'));

        $this->assertDatabaseHas('partners', ['name' => 'Universitas Gadjah Mada', 'category' => 'institusi']);
    }
}
