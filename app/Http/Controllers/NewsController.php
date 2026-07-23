<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Support\ArticleCategory;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class NewsController extends Controller
{
    public function index(): Response
    {
        $articles = Article::published()->orderBy('date', 'desc')->get();

        return Inertia::render('News/Index', [
            'articles' => ArticleResource::collection($articles)->resolve(),
            'seo' => (new Seo(
                title: 'Berita & Kegiatan',
                description: 'Sorotan produk, kegiatan perusahaan, dan edukasi bahan dari CV Gama Putra Santosa.',
                canonical: url('/news'),
            ))->toArray(),
        ]);
    }

    public function show(string $slug): Response
    {
        $article = Article::published()->where('slug', $slug)->first();

        if (! $article) {
            abort(HttpResponse::HTTP_NOT_FOUND);
        }

        $related = Article::published()
            ->where('slug', '!=', $article->slug)
            ->orderBy('date', 'desc')
            ->limit(3)
            ->get();

        $siteUrl = config('app.url');
        $articleUrl = url("/news/{$article->slug}");
        $ogImage = $article->image_og ?: $article->image;
        $tags = array_values(array_filter(array_map('trim', explode(',', $article->tags_id ?? ''))));

        $newsArticleJsonLd = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $article->title_id,
            'description' => $article->excerpt_id,
            'datePublished' => $article->date->toIso8601String(),
            'dateModified' => $article->updated_at->toIso8601String(),
            'image' => $ogImage ? [url($ogImage)] : null,
            'keywords' => $tags ? implode(', ', $tags) : null,
            'articleSection' => ArticleCategory::label($article->category_slug, 'id'),
            'author' => ['@type' => 'Organization', 'name' => config('app.name')],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'logo' => ['@type' => 'ImageObject', 'url' => url('/images/logo/gps.webp')],
            ],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $articleUrl],
        ]);

        $breadcrumbJsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => $siteUrl],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Berita & Kegiatan', 'item' => url('/news')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $article->title_id, 'item' => $articleUrl],
            ],
        ];

        return Inertia::render('News/Show', [
            'article' => (new ArticleResource($article))->resolve(),
            'relatedArticles' => ArticleResource::collection($related)->resolve(),
            'seo' => (new Seo(
                title: $article->title_id,
                description: $article->excerpt_id,
                canonical: $articleUrl,
                ogType: 'article',
                ogImage: $ogImage ? url($ogImage) : null,
                ogImageWidth: $ogImage ? 1200 : null,
                ogImageHeight: $ogImage ? 630 : null,
                publishedTime: $article->date->toIso8601String(),
                tags: $tags,
                jsonLd: [$newsArticleJsonLd, $breadcrumbJsonLd],
            ))->toArray(),
        ]);
    }
}
