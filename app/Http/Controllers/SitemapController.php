<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $now = now();

        $staticRoutes = collect([
            '', 'products', 'products/kuicip', 'products/putri-teko', 'export', 'news', 'contact',
        ])->map(fn (string $path) => [
            'url' => url('/'.$path),
            'lastModified' => $now,
        ]);

        $productRoutes = Product::query()
            ->with('brand')
            ->where('is_active', true)
            ->get()
            ->map(fn (Product $product) => [
                'url' => url("/products/{$product->brand->key}/{$product->slug}"),
                'lastModified' => $product->updated_at,
            ]);

        $articleRoutes = Article::published()->get()->map(fn (Article $article) => [
            'url' => url("/news/{$article->slug}"),
            'lastModified' => $article->updated_at,
        ]);

        $urls = $staticRoutes->concat($productRoutes)->concat($articleRoutes);

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
    }
}
