<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCreateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_superadmin_requires_an_explicit_option(): void
    {
        $this->artisan('admin:create', [
            'email' => 'owner@example.com', 'password' => 'initial-password', '--superadmin' => true,
        ])->assertSuccessful();

        $user = User::where('email', 'owner@example.com')->firstOrFail();
        $this->assertTrue($user->isSuperadmin());
        $this->assertTrue(Hash::check('initial-password', $user->password));

        $this->artisan('admin:create', ['email' => 'editor@example.com', 'password' => 'editor-password'])
            ->assertSuccessful();
        $this->assertTrue(User::where('email', 'editor@example.com')->firstOrFail()->isEditor());
    }

    public function test_the_first_account_without_the_option_is_still_an_editor(): void
    {
        $this->artisan('admin:create', ['email' => 'editor@example.com', 'password' => 'editor-password'])
            ->assertSuccessful();

        $this->assertTrue(User::firstOrFail()->isEditor());
    }

    public function test_password_resets_preserve_existing_roles(): void
    {
        foreach (['editor', 'superadmin'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->artisan('admin:create', ['email' => $user->email, 'password' => 'new-password'])
                ->assertSuccessful();

            $this->assertSame($role, $user->fresh()->role);
            $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        }
    }

    public function test_the_superadmin_option_cannot_promote_an_existing_editor(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $password = $user->password;

        $this->artisan('admin:create', [
            'email' => $user->email, 'password' => 'new-password', '--superadmin' => true,
        ])->assertFailed();

        $this->assertTrue($user->fresh()->isEditor());
        $this->assertSame($password, $user->fresh()->password);
    }

    public function test_password_can_be_entered_using_a_hidden_interactive_prompt(): void
    {
        $password = bin2hex(random_bytes(16));
        $this->artisan('admin:create', ['email' => 'prompt@example.com', '--superadmin' => true])
            ->expectsQuestion('Password (minimal 8 karakter)', $password)->assertSuccessful();
        $user = User::where('email', 'prompt@example.com')->firstOrFail();
        $this->assertTrue($user->isSuperadmin());
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_invalid_credentials_do_not_create_an_account(): void
    {
        $this->artisan('admin:create', ['email' => 'not-an-email', 'password' => 'short'])->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
