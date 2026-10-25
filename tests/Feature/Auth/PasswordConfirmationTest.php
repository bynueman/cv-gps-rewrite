<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unused_password_confirmation_route_is_unavailable(): void
    {
        $this->actingAs(User::factory()->create())->get('/confirm-password')->assertNotFound();
        $this->post('/confirm-password', ['password' => 'password'])->assertNotFound();
        $this->assertNull(session('auth.password_confirmed_at'));
    }
}
