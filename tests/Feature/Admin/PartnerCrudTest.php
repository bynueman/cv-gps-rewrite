<?php

namespace Tests\Feature\Admin;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_partner(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.partners.store'), [
                'name' => 'Toko Uji',
                'category' => 'ritel',
                'logo' => null,
                'sort_order' => 0,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.partners.index'));

        $this->assertDatabaseHas('partners', ['name' => 'Toko Uji', 'category' => 'ritel']);
    }

    public function test_admin_can_update_and_deactivate_a_partner(): void
    {
        $admin = User::factory()->create();
        $partner = Partner::create(['name' => 'Toko Uji', 'category' => 'ritel', 'sort_order' => 0, 'is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.partners.update', $partner), [
                'name' => 'Toko Uji Baru',
                'category' => 'hotel',
                'logo' => null,
                'sort_order' => 5,
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.partners.index'));

        $this->assertDatabaseHas('partners', [
            'id' => $partner->id,
            'name' => 'Toko Uji Baru',
            'category' => 'hotel',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_a_partner(): void
    {
        $admin = User::factory()->create();
        $partner = Partner::create(['name' => 'Toko Uji', 'category' => 'ritel', 'sort_order' => 0, 'is_active' => true]);

        $this->actingAs($admin)
            ->delete(route('admin.partners.destroy', $partner))
            ->assertRedirect(route('admin.partners.index'));

        $this->assertDatabaseMissing('partners', ['id' => $partner->id]);
    }

    public function test_category_must_be_a_known_value(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.partners.store'), [
                'name' => 'Toko Uji',
                'category' => 'not-a-real-category',
                'sort_order' => 0,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('category');
    }
}
