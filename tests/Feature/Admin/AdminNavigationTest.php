<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Smoke coverage for the Fase 2 admin shell (sidebar/topbar layout,
 * dashboard/profile routes) — guards against the redirect target and
 * route-name renames breaking navigation. Per-module CRUD/access checks
 * live in their own test files (ProductCrudTest, PartnerCrudTest, etc).
 */
class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Dashboard'));
    }

    public function test_admin_can_view_articles_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/articles')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Articles/Index'));
    }

    public function test_admin_can_view_profile_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Profile'));
    }

    public function test_login_redirects_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'smoke-test@example.com',
            'password' => bcrypt('secret-password'),
        ]);

        $this->post('/admin/login', [
            'email' => 'smoke-test@example.com',
            'password' => 'secret-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }
}
