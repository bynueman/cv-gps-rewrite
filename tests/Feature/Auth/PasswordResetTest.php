<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_password_reset_is_unavailable(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $original = $user->password;
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', ['email' => $user->email])->assertNotFound();
        $this->get('/reset-password/synthetic-token')->assertNotFound();
        $this->post('/reset-password', ['email' => $user->email, 'password' => 'password123',
            'password_confirmation' => 'password123', 'token' => 'synthetic-token'])->assertNotFound();
        Notification::assertNothingSent();
        $this->assertSame($original, $user->fresh()->password);
    }
}
