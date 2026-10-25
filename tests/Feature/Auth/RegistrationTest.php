<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_unavailable(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['name' => 'Synthetic User', 'email' => 'synthetic@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123'])->assertNotFound();
        $this->assertDatabaseCount('users', 0);
    }
}
