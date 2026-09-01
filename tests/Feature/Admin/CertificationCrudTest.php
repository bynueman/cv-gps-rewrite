<?php

namespace Tests\Feature\Admin;

use App\Models\Certification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificationCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_logo_only_certification(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.certifications.store'), [
                'name'       => 'Sertifikat Halal MUI',
                'issuer'     => 'MUI',
                'category'   => 'halal',
                'logo'       => null,
                'pdf_url'    => null,
                'sort_order' => 0,
                'is_active'  => true,
            ])
            ->assertRedirect(route('admin.certifications.index'));

        $this->assertDatabaseHas('certifications', [
            'name'     => 'Sertifikat Halal MUI',
            'category' => 'halal',
            'issuer'   => 'MUI',
        ]);
    }

    public function test_admin_can_create_certification_with_external_pdf(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.certifications.store'), [
                'name'       => 'Izin BPOM',
                'category'   => 'bpom',
                'pdf_url'    => 'https://drive.google.com/file/d/example',
                'sort_order' => 1,
                'is_active'  => true,
            ])
            ->assertRedirect(route('admin.certifications.index'));

        $this->assertDatabaseHas('certifications', [
            'name'    => 'Izin BPOM',
            'pdf_url' => 'https://drive.google.com/file/d/example',
        ]);
    }

    public function test_admin_can_update_and_deactivate_a_certification(): void
    {
        $admin = User::factory()->create();
        $cert  = Certification::create([
            'name'       => 'P-IRT Kuicip',
            'category'   => 'pirt',
            'sort_order' => 0,
            'is_active'  => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.certifications.update', $cert), [
                'name'       => 'P-IRT Kuicip (Diperbarui)',
                'category'   => 'pirt',
                'sort_order' => 2,
                'is_active'  => false,
            ])
            ->assertRedirect(route('admin.certifications.index'));

        $this->assertDatabaseHas('certifications', [
            'id'        => $cert->id,
            'name'      => 'P-IRT Kuicip (Diperbarui)',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_a_certification(): void
    {
        $admin = User::factory()->create();
        $cert  = Certification::create([
            'name'       => 'Sertifikasi Legalitas',
            'category'   => 'legal',
            'sort_order' => 0,
            'is_active'  => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.certifications.destroy', $cert))
            ->assertRedirect(route('admin.certifications.index'));

        $this->assertDatabaseMissing('certifications', ['id' => $cert->id]);
    }

    public function test_category_must_be_a_known_value(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.certifications.store'), [
                'name'       => 'Sertifikasi Palsu',
                'category'   => 'tidak-ada-kategori',
                'sort_order' => 0,
                'is_active'  => true,
            ])
            ->assertSessionHasErrors('category');
    }

    public function test_name_is_required(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.certifications.store'), [
                'name'     => '',
                'category' => 'halal',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_inactive_certifications_are_excluded_from_public_page(): void
    {
        Certification::create([
            'name'       => 'Aktif',
            'category'   => 'halal',
            'sort_order' => 0,
            'is_active'  => true,
        ]);
        Certification::create([
            'name'       => 'Nonaktif',
            'category'   => 'legal',
            'sort_order' => 1,
            'is_active'  => false,
        ]);

        $response = $this->get(route('export.legality'));
        $response->assertStatus(200);

        // The Inertia page prop 'certifications' should only have 1 active item
        $response->assertInertia(fn ($page) =>
            $page->has('certifications', 1)
                 ->where('certifications.0.name', 'Aktif')
        );
    }
}
