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

    public function test_admin_can_update_an_article(): void
    {
        $admin = $this->admin();
        $article = Article::create([
            'slug' => 'artikel-lama',
            'date' => '2026-09-01',
            'category_slug' => 'produk-resep',
            'title_id' => 'Judul Lama',
            'title_en' => 'Old Title',
            'excerpt_id' => 'Ringkasan lama yang cukup panjang untuk testing cuplikan artikel.',
            'excerpt_en' => 'Old excerpt that is long enough for testing article excerpt.',
            'body_id' => '<p>Isi artikel lama.</p>',
            'body_en' => '<p>Old article body.</p>',
            'tags_id' => 'lama',
            'tags_en' => 'old',
            'published' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.articles.update', $article), [
                'slug' => 'artikel-lama',
                'date' => '2026-09-02',
                'category_slug' => 'edukasi-bahan',
                'title' => ['id' => 'Judul Baru Diperbarui', 'en' => 'New Updated Title'],
                'excerpt' => [
                    'id' => 'Ringkasan baru yang cukup panjang untuk testing cuplikan artikel.',
                    'en' => 'New excerpt that is long enough for testing article excerpt.',
                ],
                'body' => ['id' => '<p>Isi baru.</p>', 'en' => '<p>New body.</p>'],
                'tags' => ['id' => 'baru', 'en' => 'new'],
                'published' => true,
            ])
            ->assertRedirect(route('admin.articles.index'));

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'title_id' => 'Judul Baru Diperbarui',
            'category_slug' => 'edukasi-bahan',
        ]);
    }

    public function test_admin_can_delete_an_article(): void
    {
        $admin = $this->admin();
        $article = Article::create([
            'slug' => 'artikel-hapus',
            'date' => '2026-09-01',
            'category_slug' => 'produk-resep',
            'title_id' => 'Artikel Hapus',
            'title_en' => 'Article to Delete',
            'excerpt_id' => 'Ringkasan artikel yang akan dihapus dari sistem.',
            'excerpt_en' => 'Excerpt of article to delete from the system.',
            'body_id' => '<p>Isi artikel.</p>',
            'body_en' => '<p>Body content.</p>',
            'tags_id' => 'hapus',
            'tags_en' => 'delete',
            'published' => false,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.articles.destroy', $article))
            ->assertRedirect(route('admin.articles.index'));

        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }
}
