<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\BrandSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BrandSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function payload(int $brandId, array $overrides = []): array
    {
        return array_merge([
            'brand_id' => $brandId,
            'slug' => 'kopi-uji',
            'group' => '',
            'packaging' => '',
            'name' => ['id' => 'Kopi Uji', 'en' => 'Test Coffee'],
            'short' => ['id' => 'Tagline uji', 'en' => 'Test tagline'],
            'personality' => ['id' => 'Berani', 'en' => 'Bold'],
            'serving' => ['id' => '', 'en' => ''],
            'description' => ['id' => 'Deskripsi uji.', 'en' => 'Test description.'],
            'highlights' => ['id' => "Poin satu\nPoin dua", 'en' => "Point one\nPoint two"],
            'notes' => ['id' => '', 'en' => ''],
            'color' => '#E29A12',
            'color_dark' => '#9C6508',
            'featured' => false,
            'placeholder' => false,
            'is_active' => true,
            'sort_order' => 0,
        ], $overrides);
    }

    public function test_admin_can_create_a_product(): void
    {
        $brand = Brand::query()->first();

        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->payload($brand->id))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'slug' => 'kopi-uji',
            'name_id' => 'Kopi Uji',
            'brand_id' => $brand->id,
        ]);

        $product = Product::where('slug', 'kopi-uji')->where('brand_id', $brand->id)->firstOrFail();
        $this->assertCount(2, $product->highlights);
        $this->assertSame('Poin satu', $product->highlights[0]['id']);
        $this->assertSame('Point one', $product->highlights[0]['en']);
    }

    public function test_product_index_lists_created_product(): void
    {
        $brand = Brand::query()->first();
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->payload($brand->id));

        $this->actingAs($this->admin())
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Products/Index')
                ->has('products', 1)
            );
    }

    public function test_admin_can_update_a_product(): void
    {
        $brand = Brand::query()->first();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload($brand->id));
        $product = Product::where('slug', 'kopi-uji')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product), $this->payload($brand->id, [
                'name' => ['id' => 'Kopi Uji Baru', 'en' => 'New Test Coffee'],
                'is_active' => false,
            ]))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name_id' => 'Kopi Uji Baru',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_a_product(): void
    {
        $brand = Brand::query()->first();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload($brand->id));
        $product = Product::where('slug', 'kopi-uji')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_slug_must_be_unique_within_brand(): void
    {
        $brand = Brand::query()->first();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload($brand->id));

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->payload($brand->id))
            ->assertSessionHasErrors('slug');
    }

    public function test_inactive_product_is_excluded_from_public_catalog(): void
    {
        $brand = Brand::where('key', 'kuicip')->firstOrFail();
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->payload($brand->id, [
            'is_active' => false,
        ]));

        $this->get('/products/kuicip')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('products', fn ($products) => collect($products)->doesntContain(fn ($p) => $p['slug'] === 'kopi-uji'))
            );

        $this->get('/products/kuicip/kopi-uji')->assertNotFound();
    }
}
