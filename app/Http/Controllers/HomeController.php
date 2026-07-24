<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleResource;
use App\Http\Resources\ProductResource;
use App\Models\Article;
use App\Models\Brand;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Setting;
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
            // Curated subset for the homepage trust wall — the full
            // directory (every active partner) lives at /mitra.
            'partners' => Partner::where('is_active', true)->where('featured', true)
                ->orderBy('category')->orderBy('sort_order')->orderBy('name')
                ->get(['name', 'category', 'logo']),
            'seo' => (new Seo(
                // No "— CV Gama Putra Santosa" suffix here on purpose: the
                // Seo class skips appending it when the title already ends
                // with the site name, so passing the bare name keeps the
                // homepage tab title exactly "CV Gama Putra Santosa".
                title: config('company.name'),
                description: 'CV Gama Putra Santosa (GPS Group) adalah perusahaan makanan & minuman Yogyakarta sejak 2011. Rumah bagi Kuicip, Putri Teko, dan Ngayogyakarya.',
                canonical: url('/'),
                jsonLd: [$this->organizationJsonLd()],
            ))->toArray(),
        ]);
    }

    /** @return array<string, mixed> */
    private function organizationJsonLd(): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('company.name'),
            'url' => url('/'),
            'logo' => url('/images/logo/gps.webp'),
            'foundingDate' => '2011',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => Setting::get('address', config('company.address')),
                'addressLocality' => 'Sleman',
                'addressRegion' => 'DI Yogyakarta',
                'addressCountry' => 'ID',
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => Setting::get('whatsapp', config('company.whatsapp')),
                'email' => Setting::get('email_primary', config('company.email')),
                'contactType' => 'customer service',
            ],
        ]);
    }
}
