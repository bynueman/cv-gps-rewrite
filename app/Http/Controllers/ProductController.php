<?php

namespace App\Http\Controllers;

use App\Enums\TekoPackaging;
use App\Http\Resources\ProductResource;
use App\Models\Brand;
use App\Models\Product;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $kuicip = Brand::where('key', 'kuicip')->firstOrFail();
        $teko = Brand::where('key', 'putri-teko')->firstOrFail();

        $featuredKuicip = Product::where('brand_id', $kuicip->id)->where('featured', true)->get();
        $featuredTekoRtd = Product::where('brand_id', $teko->id)->where('featured', true)->where('group', 'rtd')->get();
        $featuredTekoBrew = Product::where('brand_id', $teko->id)->where('featured', true)->where('group', 'brew')->get();

        return Inertia::render('Products/Overview', [
            'featuredKuicip' => ProductResource::collection($featuredKuicip)->resolve(),
            'featuredTekoRtd' => ProductResource::collection($featuredTekoRtd)->resolve(),
            'featuredTekoBrew' => ProductResource::collection($featuredTekoBrew)->resolve(),
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
        $products = Product::where('brand_id', $brand->id)->orderBy('id')->get();

        return Inertia::render('Products/KuicipCatalog', [
            'products' => ProductResource::collection($products)->resolve(),
            'seo' => (new Seo(
                title: 'Kuicip — Keripik Singkong Modern',
                description: 'Keripik singkong modern dalam kemasan ziplock 70 gr, 8 varian rasa.',
                canonical: url('/products/kuicip'),
            ))->toArray(),
        ]);
    }

    public function kuicipShow(string $slug): Response
    {
        $brand = Brand::where('key', 'kuicip')->firstOrFail();
        $product = Product::where('brand_id', $brand->id)->where('slug', $slug)->firstOrFail();
        $related = Product::query()->relatedTo($product)->get();

        return Inertia::render('Products/ProductDetail', [
            'product' => (new ProductResource($product))->resolve(),
            'related' => ProductResource::collection($related)->resolve(),
            'seo' => (new Seo(
                title: $product->name_id,
                description: $product->short_id,
                canonical: url("/products/kuicip/{$product->slug}"),
                ogImage: $product->image ? url($product->image) : null,
            ))->toArray(),
        ]);
    }

    public function teko(): Response
    {
        $brand = Brand::where('key', 'putri-teko')->firstOrFail();
        $all = Product::where('brand_id', $brand->id)->orderBy('id')->get();

        $byPackaging = [];
        foreach (TekoPackaging::displayOrder() as $packaging) {
            $byPackaging[$packaging] = ProductResource::collection(
                $all->where('packaging', $packaging)->values()
            )->resolve();
        }

        return Inertia::render('Products/TekoCatalog', [
            'packaging' => $byPackaging,
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
        $product = Product::where('brand_id', $brand->id)->where('slug', $slug)->firstOrFail();
        $related = Product::query()->relatedTo($product)->get();

        return Inertia::render('Products/ProductDetail', [
            'product' => (new ProductResource($product))->resolve(),
            'related' => ProductResource::collection($related)->resolve(),
            'seo' => (new Seo(
                title: $product->name_id,
                description: $product->short_id,
                canonical: url("/products/putri-teko/{$product->slug}"),
                ogImage: $product->image ? url($product->image) : null,
            ))->toArray(),
        ]);
    }
}
