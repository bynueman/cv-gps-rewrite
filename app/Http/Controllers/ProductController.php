<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductSummaryResource;
use App\Models\Brand;
use App\Models\Product;
use App\Support\LocalizedContent;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $kuicip = Brand::where('key', 'kuicip')->firstOrFail();
        $teko = Brand::where('key', 'putri-teko')->firstOrFail();

        $featuredKuicip = Product::with('brand')->where('brand_id', $kuicip->id)->where('featured', true)->where('is_active', true)->orderBy('sort_order')->get();
        $featuredTekoRtd = Product::with('brand')->where('brand_id', $teko->id)->where('featured', true)->where('is_active', true)->where('group', 'rtd')->orderBy('sort_order')->get();
        $featuredTekoBrew = Product::with('brand')->where('brand_id', $teko->id)->where('featured', true)->where('is_active', true)->where('group', 'brew')->orderBy('sort_order')->get();

        return Inertia::render('Products/Overview', [
            'featuredKuicip' => ProductSummaryResource::collection($featuredKuicip)->resolve(),
            'featuredTekoRtd' => ProductSummaryResource::collection($featuredTekoRtd)->resolve(),
            'featuredTekoBrew' => ProductSummaryResource::collection($featuredTekoBrew)->resolve(),
            'seo' => (new Seo(
                title: 'Produk Kami',
                description: 'Kuicip dan Putri Teko — dua keluarga produk dari satu dapur produksi di Sleman, Yogyakarta.',
                canonical: url('/products'),
            ))->toArray(),
        ]);
    }

    public function kuicip(): Response
    {
        $brand = Brand::where('key', 'kuicip')->firstOrFail();
        $products = Product::with('brand')->where('brand_id', $brand->id)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();

        return Inertia::render('Products/KuicipCatalog', [
            'products' => ProductSummaryResource::collection($products)->resolve(),
            'seo' => (new Seo(
                title: 'Kuicip — Keripik Singkong Modern',
                description: 'Jelajahi pilihan rasa keripik singkong Kuicip dari GPS FOOD.',
                canonical: url('/products/kuicip'),
            ))->toArray(),
        ]);
    }

    public function kuicipShow(string $slug): Response
    {
        $brand = Brand::where('key', 'kuicip')->firstOrFail();
        $product = Product::with('brand')->where('brand_id', $brand->id)->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $related = Product::query()->relatedTo($product)->get();

        return Inertia::render('Products/ProductDetail', [
            'product' => (new ProductResource($product))->resolve(),
            'related' => ProductSummaryResource::collection($related)->resolve(),
            'seoEnglish' => ['title' => LocalizedContent::english($product, 'seo_title', LocalizedContent::english($product, 'name')),
                'description' => LocalizedContent::english($product, 'meta_description', LocalizedContent::english($product, 'short'))],
            'seo' => (new Seo(
                title: $product->seo_title_id ?: $product->name_id,
                description: $product->meta_description_id ?: $product->short_id,
                canonical: url("/products/kuicip/{$product->slug}"),
                ogImage: ($product->image_og ?: $product->image) ? url($product->image_og ?: $product->image) : null,
                jsonLd: $this->productJsonLd($product, 'Kuicip', "/products/kuicip/{$product->slug}"),
            ))->toArray(),
        ]);
    }

    public function teko(): Response
    {
        $brand = Brand::where('key', 'putri-teko')->firstOrFail();
        $all = Product::with('brand')->where('brand_id', $brand->id)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();

        return Inertia::render('Products/TekoCatalog', [
            'products' => ProductSummaryResource::collection($all)->resolve(),
            'seo' => (new Seo(
                title: 'Putri Teko — Minuman Tradisional & Herbal',
                description: 'Dari jamu botol siap minum hingga racikan wedang uwuh, dalam lima bentuk kemasan.',
                canonical: url('/products/putri-teko'),
            ))->toArray(),
        ]);
    }

    public function tekoShow(string $slug): Response
    {
        $brand = Brand::where('key', 'putri-teko')->firstOrFail();
        $product = Product::with('brand')->where('brand_id', $brand->id)->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $related = Product::query()->relatedTo($product)->get();

        return Inertia::render('Products/ProductDetail', [
            'product' => (new ProductResource($product))->resolve(),
            'related' => ProductSummaryResource::collection($related)->resolve(),
            'seoEnglish' => ['title' => LocalizedContent::english($product, 'seo_title', LocalizedContent::english($product, 'name')),
                'description' => LocalizedContent::english($product, 'meta_description', LocalizedContent::english($product, 'short'))],
            'seo' => (new Seo(
                title: $product->seo_title_id ?: $product->name_id,
                description: $product->meta_description_id ?: $product->short_id,
                canonical: url("/products/putri-teko/{$product->slug}"),
                ogImage: ($product->image_og ?: $product->image) ? url($product->image_og ?: $product->image) : null,
                jsonLd: $this->productJsonLd($product, 'Putri Teko', "/products/putri-teko/{$product->slug}"),
            ))->toArray(),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function productJsonLd(Product $product, string $brandName, string $path): array
    {
        $productUrl = url($path);

        $productJsonLd = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name_id,
            'description' => $product->description_id,
            'image' => $product->image ? [url($product->image)] : null,
            'brand' => ['@type' => 'Brand', 'name' => $brandName],
            'url' => $productUrl,
        ]);

        $breadcrumbJsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Produk Kami', 'item' => url('/products')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $brandName, 'item' => url('/products/'.($brandName === 'Kuicip' ? 'kuicip' : 'putri-teko'))],
                ['@type' => 'ListItem', 'position' => 4, 'name' => $product->name_id, 'item' => $productUrl],
            ],
        ];

        return [$productJsonLd, $breadcrumbJsonLd];
    }
}
