<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['https://api.mymemory.translated.net/*' => Http::response([
            'responseData' => ['translatedText' => 'Translated test text'],
        ])]);
    }

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_guest_cannot_access_translation_endpoint(): void
    {
        $response = $this->postJson(route('admin.translate'), [
            'from' => 'id',
            'to' => 'en',
            'fields' => ['title' => 'Halo Dunia'],
        ]);

        $response->assertUnauthorized();
    }

    public function test_admin_can_translate_fields(): void
    {
        $response = $this->actingAs($this->admin())->postJson(route('admin.translate'), [
            'from' => 'id',
            'to' => 'en',
            'fields' => [
                'name' => 'Gula Batu',
                'description' => 'Manis dan alami',
            ],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'translations' => [
                    'name',
                    'description',
                ],
            ]);
    }

    public function test_translation_validates_languages(): void
    {
        $response = $this->actingAs($this->admin())->postJson(route('admin.translate'), [
            'from' => 'invalid',
            'to' => 'en',
            'fields' => ['name' => 'Test'],
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_can_translate_html_body(): void
    {
        $response = $this->actingAs($this->admin())->postJson(route('admin.translate'), [
            'from' => 'id',
            'to' => 'en',
            'fields' => [
                'body' => '<p>Kuicip adalah camilan lezat.</p><p>Sangat cocok dinikmati bersama teman.</p>',
            ],
        ]);

        $response->assertOk();
        $this->assertStringContainsString('<p>', $response->json('translations.body'));
    }
}
