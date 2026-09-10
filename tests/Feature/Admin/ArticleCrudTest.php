<?php

namespace Tests\Feature\Admin;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_admin_saving_article_with_empty_english_auto_translates(): void
    {
        $payload = [
            'slug' => 'artikel-otomatis',
            'date' => '2026-09-10',
            'category_slug' => 'edukasi-bahan',
            'title' => [
                'id' => 'Manfaat Singkong untuk Kesehatan',
                'en' => '',
            ],
            'excerpt' => [
                'id' => 'Singkong kaya akan serat dan nutrisi penting bagi tubuh.',
                'en' => '',
            ],
            'body' => [
                'id' => '<p>Singkong merupakan makanan tradisional yang sangat bernutrisi.</p>',
                'en' => '',
            ],
            'tags' => [
                'id' => 'singkong, kesehatan, nutrisi',
                'en' => '',
            ],
            'published' => true,
        ];

        $this->actingAs($this->admin())
            ->post(route('admin.articles.store'), $payload)
            ->assertRedirect(route('admin.articles.index'));

        $article = Article::where('slug', 'artikel-otomatis')->firstOrFail();
        $this->assertSame('Manfaat Singkong untuk Kesehatan', $article->title_id);
        $this->assertNotEmpty($article->title_en);
        $this->assertNotEmpty($article->excerpt_en);
        $this->assertNotEmpty($article->body_en);
    }
}
