<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleResource;
use App\Http\Resources\ProductResource;
use App\Models\Article;
use App\Models\Brand;
use App\Models\Partner;
use App\Models\Product;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $kuicip = Brand::where('key', 'kuicip')->firstOrFail();
        $teko = Brand::where('key', 'putri-teko')->firstOrFail();

        $find = fn (Brand $brand, string $slug) => Product::where('brand_id', $brand->id)->where('slug', $slug)->where('is_active', true)->first();
        $resolve = fn (?Product $p) => $p ? (new ProductResource($p))->resolve() : null;

        $heroProduct = $find($teko, 'wedang-uwuh-toples');
        $supporting = collect([
            $find($kuicip, 'original'),
            $find($kuicip, 'seaweed'),
            $find($kuicip, 'balado'),
            $find($teko, 'beras-kencur'),
        ])->filter()->values();

        $featuredArticles = Article::published()->featured()->orderBy('date', 'desc')->limit(3)->get();

        return Inertia::render('Home', [
            'kuicipCount' => Product::where('brand_id', $kuicip->id)->where('is_active', true)->count(),
            'tekoCount' => Product::where('brand_id', $teko->id)->where('is_active', true)->count(),
            'featuredHero' => $resolve($heroProduct),
            'featuredSupporting' => ProductResource::collection($supporting)->resolve(),
            'familiesKuicip1' => $resolve($find($kuicip, 'seaweed')),
            'familiesKuicip2' => $resolve($find($kuicip, 'truffle')),
            'familiesTeko1' => $resolve($find($teko, 'kunir-asam')),
            'familiesTeko2' => $resolve($find($teko, 'jahe-serai-toples')),
            'featuredArticles' => ArticleResource::collection($featuredArticles)->resolve(),
            'partners' => Partner::where('is_active', true)
                ->orderBy('category')->orderBy('sort_order')->orderBy('name')
                ->get(['name', 'category', 'logo']),
            'seo' => (new Seo(
                title: 'CV Gama Putra Santosa — Kuicip & Putri Teko',
                description: 'CV Gama Putra Santosa (GPS Group) adalah perusahaan makanan & minuman Yogyakarta sejak 2011. Rumah bagi Kuicip, Putri Teko, dan Ngayogyakarya.',
                canonical: url('/'),
                ogImage: url('/images/og-default.webp'),
                ogImageWidth: 1200,
                ogImageHeight: 630,
            ))->toArray(),
        ]);
    }
}
