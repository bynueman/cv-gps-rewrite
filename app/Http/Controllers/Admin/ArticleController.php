<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreArticleRequest;
use App\Http\Requests\Admin\UpdateArticleRequest;
use App\Models\Article;
use App\Support\ArticleCategory;
use App\Support\ArticleHtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(): Response
    {
        $articles = Article::query()->orderBy('date', 'desc')->get();

        return Inertia::render('Admin/Articles/Index', [
            'articles' => $articles->map(fn (Article $a) => [
                'id' => $a->id,
                'slug' => $a->slug,
                'title_id' => $a->title_id,
                'category_label' => ArticleCategory::label($a->category_slug, 'id'),
                'date' => $a->date->toDateString(),
                'image' => $a->image,
                'image_thumb' => $a->image_thumb,
                'featured' => $a->featured,
                'published' => $a->published,
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Articles/Form', [
            'mode' => 'create',
            'categories' => ArticleCategory::CATEGORIES,
        ]);
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Article::create([
            'slug' => Str::slug($data['slug'] ?: $data['title']['id']),
            'date' => $data['date'],
            'category_slug' => $data['category_slug'],
            'title_id' => $data['title']['id'],
            'title_en' => $data['title']['en'],
            'excerpt_id' => $data['excerpt']['id'],
            'excerpt_en' => $data['excerpt']['en'],
            'body_id' => ArticleHtmlSanitizer::clean($data['body']['id']),
            'body_en' => ArticleHtmlSanitizer::clean($data['body']['en']),
            'tags_id' => $data['tags']['id'] ?? '',
            'tags_en' => $data['tags']['en'] ?? '',
            'image' => $data['image'] ?? null,
            'image_thumb' => $data['image_thumb'] ?? null,
            'image_og' => $data['image_og'] ?? null,
            'featured' => (bool) ($data['featured'] ?? false),
            'published' => (bool) ($data['published'] ?? false),
        ]);

        return redirect()->route('admin.dashboard')->with('status', 'Artikel berhasil dibuat.');
    }

    public function edit(Article $article): Response
    {
        return Inertia::render('Admin/Articles/Form', [
            'mode' => 'edit',
            'categories' => ArticleCategory::CATEGORIES,
            'article' => [
                'id' => $article->id,
                'slug' => $article->slug,
                'date' => $article->date->toDateString(),
                'category_slug' => $article->category_slug,
                'title' => ['id' => $article->title_id, 'en' => $article->title_en],
                'excerpt' => ['id' => $article->excerpt_id, 'en' => $article->excerpt_en],
                'body' => ['id' => $article->body_id, 'en' => $article->body_en],
                'tags' => ['id' => $article->tags_id, 'en' => $article->tags_en],
                'image' => $article->image,
                'image_thumb' => $article->image_thumb,
                'image_og' => $article->image_og,
                'featured' => $article->featured,
                'published' => $article->published,
            ],
        ]);
    }

    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        $data = $request->validated();

        $article->update([
            'slug' => Str::slug($data['slug'] ?: $data['title']['id']),
            'date' => $data['date'],
            'category_slug' => $data['category_slug'],
            'title_id' => $data['title']['id'],
            'title_en' => $data['title']['en'],
            'excerpt_id' => $data['excerpt']['id'],
            'excerpt_en' => $data['excerpt']['en'],
            'body_id' => ArticleHtmlSanitizer::clean($data['body']['id']),
            'body_en' => ArticleHtmlSanitizer::clean($data['body']['en']),
            'tags_id' => $data['tags']['id'] ?? '',
            'tags_en' => $data['tags']['en'] ?? '',
            'image' => $data['image'] ?? null,
            'image_thumb' => $data['image_thumb'] ?? null,
            'image_og' => $data['image_og'] ?? null,
            'featured' => (bool) ($data['featured'] ?? false),
            'published' => (bool) ($data['published'] ?? false),
        ]);

        return redirect()->route('admin.dashboard')->with('status', 'Artikel berhasil diperbarui.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()->route('admin.dashboard')->with('status', 'Artikel berhasil dihapus.');
    }
}
