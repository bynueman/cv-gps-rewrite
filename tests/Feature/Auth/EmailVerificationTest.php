<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unused_email_verification_routes_cannot_modify_an_account(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get('/verify-email')->assertNotFound();
        $this->get('/verify-email/'.$user->id.'/'.sha1($user->email))->assertNotFound();
        $this->assertNull($user->fresh()->email_verified_at);
    }
}
