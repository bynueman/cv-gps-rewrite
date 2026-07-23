<?php

namespace Tests\Feature\Admin;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageManagementTest extends TestCase
{
    use RefreshDatabase;

    private function message(array $overrides = []): ContactMessage
    {
        return ContactMessage::create(array_merge([
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'topic' => 'Umum',
            'message' => 'Halo, saya ingin bertanya.',
            'is_read' => false,
        ], $overrides));
    }

    public function test_viewing_a_message_marks_it_as_read(): void
    {
        $message = $this->message();
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.messages.show', $message))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Messages/Show'));

        $this->assertTrue($message->fresh()->is_read);
    }

    public function test_unread_badge_count_reflects_unread_messages(): void
    {
        $this->message(['is_read' => false]);
        $this->message(['is_read' => false, 'email' => 'other@example.com']);
        $this->message(['is_read' => true, 'email' => 'read@example.com']);

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page->where('unreadMessagesCount', 2));
    }

    public function test_admin_can_delete_a_message(): void
    {
        $message = $this->message();
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.messages.destroy', $message))
            ->assertRedirect(route('admin.messages.index'));

        $this->assertDatabaseMissing('contact_messages', ['id' => $message->id]);
    }

    public function test_status_filter_scopes_the_list(): void
    {
        $this->message(['is_read' => false]);
        $this->message(['is_read' => true, 'email' => 'read@example.com']);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.messages.index', ['status' => 'unread']))
            ->assertInertia(fn ($page) => $page->has('messages', 1));
    }
}
