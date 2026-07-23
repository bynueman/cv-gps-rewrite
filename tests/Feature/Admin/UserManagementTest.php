<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'superadmin'], $overrides));
    }

    private function editor(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'editor'], $overrides));
    }

    public function test_editor_is_blocked_from_users_page_even_via_direct_url(): void
    {
        $this->actingAs($this->editor())
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_editor_is_blocked_from_settings_page(): void
    {
        $this->actingAs($this->editor())
            ->get('/admin/settings')
            ->assertForbidden();
    }

    public function test_superadmin_can_access_users_page(): void
    {
        $this->actingAs($this->superadmin())
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_superadmin_can_create_an_editor(): void
    {
        $this->actingAs($this->superadmin())
            ->post(route('admin.users.store'), [
                'name' => 'Editor Baru',
                'email' => 'editor-baru@example.com',
                'role' => 'editor',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'editor-baru@example.com', 'role' => 'editor']);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->superadmin();
        $this->superadmin(); // a second superadmin, so "last superadmin" isn't the blocker here

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_a_superadmin_can_delete_another_superadmin_when_not_the_last_one(): void
    {
        $actingAdmin = $this->superadmin();
        $targetAdmin = $this->superadmin();

        $this->actingAs($actingAdmin)
            ->delete(route('admin.users.destroy', $targetAdmin))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $targetAdmin->id]);
    }

    public function test_lone_superadmin_cannot_demote_themselves_via_update(): void
    {
        $onlySuperadmin = $this->superadmin();

        $this->actingAs($onlySuperadmin)
            ->patch(route('admin.users.update', $onlySuperadmin), [
                'name' => $onlySuperadmin->name,
                'email' => $onlySuperadmin->email,
                'role' => 'editor',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('users', ['id' => $onlySuperadmin->id, 'role' => 'superadmin']);
    }
}
