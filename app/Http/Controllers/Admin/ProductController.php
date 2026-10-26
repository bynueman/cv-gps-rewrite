<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Product;
use App\Services\ContentTranslationWorkflow;
use App\Services\ImageProcessor;
use App\Support\BilingualLists;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ImageProcessor $imageProcessor) {}

    public function index(Request $request): Response
    {
        $query = Product::query()->with('brand')->orderBy('brand_id')->orderBy('sort_order')->orderBy('name_id');

        if ($request->filled('brand')) {
            $query->whereHas('brand', fn ($q) => $q->where('key', $request->string('brand')));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(fn ($q) => $q
                ->where('name_id', 'like', "%{$search}%")
                ->orWhere('name_en', 'like', "%{$search}%"));
        }

        $products = $query->get()->map(fn (Product $p) => [
            'id' => $p->id,
            'slug' => $p->slug,
            'brand_key' => $p->brand->key,
            'brand_name' => $p->brand->name,
            'name_id' => $p->name_id,
            'name_en' => $p->name_en,
            'image' => $p->image_thumb ?? $p->image,
            'featured' => $p->featured,
            'is_active' => $p->is_active,
            'sort_order' => $p->sort_order,
        ]);

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'brands' => Brand::query()->orderBy('id')->get(['id', 'key', 'name']),
            'filters' => $request->only(['brand', 'status', 'q']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'mode' => 'create',
            'brands' => Brand::query()->orderBy('id')->get(['id', 'key', 'name']),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $this->prepareData($request->validated());

        $product = app(ContentTranslationWorkflow::class)->save(new Product, $data);

        ActivityLog::record('created', 'products', $product->name_id);

        return redirect()->route('admin.products.index')->with('status', "Produk \"{$product->name_id}\" berhasil dibuat. English diproses di antrean; edit manual tetap dilindungi.");
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'mode' => 'edit',
            'translation' => ContentTranslationWorkflow::view($product),
            'brands' => Brand::query()->orderBy('id')->get(['id', 'key', 'name']),
            'product' => [
                'id' => $product->id,
                'brand_id' => $product->brand_id,
                'slug' => $product->slug,
                ...$product->only(['shopee_url', 'net_weight', 'seo_title_id', 'seo_title_en', 'meta_description_id', 'meta_description_en', 'image_alt_id', 'image_alt_en']),
                'group' => $product->group,
                'packaging' => $product->packaging,
                'name' => ['id' => $product->name_id, 'en' => $product->name_en],
                'short' => ['id' => $product->short_id, 'en' => $product->short_en],
                'personality' => ['id' => $product->personality_id, 'en' => $product->personality_en],
                'serving' => ['id' => $product->serving_id ?? '', 'en' => $product->serving_en ?? ''],
                'description' => ['id' => $product->description_id, 'en' => $product->description_en],
                'highlights' => BilingualLists::keyed($product->highlights),
                'notes' => BilingualLists::keyed($product->notes),
                'color' => $product->color,
                'color_dark' => $product->color_dark,
                'image' => $product->image,
                'image_thumb' => $product->image_thumb,
                'image_og' => $product->image_og,
                'featured' => $product->featured,
                'placeholder' => $product->placeholder,
                'is_active' => $product->is_active,
                'sort_order' => $product->sort_order,
            ],
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $this->prepareData($request->validated());

        $oldImage = $product->image;

        $product = app(ContentTranslationWorkflow::class)->save($product, $data, $request->only(['translation_revision', 'translation_english_edits']));
        if ($oldImage && $oldImage !== $product->image) {
            $this->imageProcessor->delete($oldImage);
        }

        ActivityLog::record('updated', 'products', $product->name_id);

        return redirect()->route('admin.products.index')->with('status', "Produk \"{$product->name_id}\" berhasil diperbarui. Status English tersedia pada halaman edit.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $label = $product->name_id;
        $this->imageProcessor->delete($product->image);
        $product->delete();

        ActivityLog::record('deleted', 'products', $label);

        return redirect()->route('admin.products.index')->with('status', "Produk \"{$label}\" berhasil dihapus.");
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareData(array $data): array
    {
        return [
            'brand_id' => $data['brand_id'],
            'slug' => Str::slug($data['slug']),
            'group' => $data['group'] ?? null ?: null,
            'packaging' => $data['packaging'] ?? null ?: null,
            'name_id' => $data['name']['id'],
            'name_en' => ($data['name']['en'] ?? ''),
            'short_id' => $data['short']['id'],
            'short_en' => ($data['short']['en'] ?? ''),
            'personality_id' => $data['personality']['id'],
            'personality_en' => ($data['personality']['en'] ?? ''),
            'serving_id' => ($data['serving']['id'] ?? '') ?: null,
            'serving_en' => ($data['serving']['en'] ?? '') ?: null,
            'description_id' => $data['description']['id'],
            'description_en' => ($data['description']['en'] ?? ''),
            'highlights' => $data['highlights'] ?? [],
            'notes' => $data['notes'] ?? [],
            'color' => $data['color'],
            'color_dark' => $data['color_dark'],
            ...Arr::only($data, ['shopee_url', 'net_weight', 'seo_title_id', 'seo_title_en',
                'meta_description_id', 'meta_description_en', 'image_alt_id', 'image_alt_en']),
            'image' => $data['image'] ?? null,
            'image_thumb' => $data['image_thumb'] ?? null,
            'image_og' => $data['image_og'] ?? null,
            'featured' => (bool) ($data['featured'] ?? false),
            'placeholder' => (bool) ($data['placeholder'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
