<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\BrandSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BrandSeeder::class);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'whatsapp' => '+62 811-1111-1111',
            'email_primary' => 'kontak@gpsfood.id',
            'email_secondary' => 'cadangan@gpsfood.id',
            'address' => 'Jl. Uji Coba No. 1, Sleman',
            'instagram_kuicip' => '@kuicip.test',
            'instagram_putriteko' => '@putriteko.test',
            'operating_hours' => 'Senin–Jumat, 09.00–17.00',
            'site_title' => 'CV Gama Putra Santosa (Uji)',
            'meta_description_default' => 'Deskripsi uji untuk meta default.',
            'og_image_default' => '',
        ], $overrides);
    }

    public function test_superadmin_can_update_settings(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($admin)
            ->patch(route('admin.settings.update'), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'whatsapp', 'value' => '+62 811-1111-1111']);
        $this->assertDatabaseHas('settings', ['key' => 'address', 'value' => 'Jl. Uji Coba No. 1, Sleman']);
    }

    public function test_updated_whatsapp_number_appears_on_public_pages(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($admin)->patch(route('admin.settings.update'), $this->payload([
            'whatsapp' => '+62 899-9999-9999',
        ]));

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('site.company.whatsapp', '+62 899-9999-9999')
                ->where('site.company.whatsapp_href', 'https://wa.me/6289999999999')
            );

        $this->get('/contact')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('site.company.whatsapp', '+62 899-9999-9999'));
    }

    public function test_editor_cannot_update_settings(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $this->actingAs($editor)
            ->patch(route('admin.settings.update'), $this->payload())
            ->assertForbidden();
    }

    public function test_settings_fall_back_to_config_when_unset(): void
    {
        // No Setting rows exist yet — the shared prop should still resolve
        // using config/company.php instead of erroring or returning null.
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('site.company.email', config('company.email')));
    }

    public function test_clearing_secondary_email_removes_it_instead_of_reverting_to_config_default(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);

        // Set it first, then clear it — mirrors an admin blanking out a
        // field that already had a value, not a fresh install.
        $this->actingAs($admin)->patch(route('admin.settings.update'), $this->payload());
        $this->actingAs($admin)->patch(route('admin.settings.update'), $this->payload([
            'email_secondary' => '',
        ]));

        $this->assertDatabaseHas('settings', ['key' => 'email_secondary', 'value' => null]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('site.company.email_alt', null));
    }
}
