<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function tearDown(): void
    {
        foreach (['products', 'articles', 'partners', 'settings'] as $context) {
            File::deleteDirectory(public_path("uploads/{$context}"));
        }

        parent::tearDown();
    }

    public function test_upload_generates_three_webp_variants_for_standard_context(): void
    {
        $admin = User::factory()->create();
        $file = UploadedFile::fake()->image('product.jpg', 1600, 900);

        $response = $this->actingAs($admin)
            ->post(route('admin.upload'), ['file' => $file, 'context' => 'products', 'variant' => 'standard'])
            ->assertOk()
            ->json();

        $this->assertStringStartsWith('/uploads/products/', $response['image']);
        $this->assertStringEndsWith('-content.webp', $response['image']);
        $this->assertStringEndsWith('-thumb.webp', $response['imageThumb']);
        $this->assertStringEndsWith('-og.webp', $response['imageOg']);

        $this->assertFileExists(public_path(ltrim($response['image'], '/')));
        $this->assertFileExists(public_path(ltrim($response['imageThumb'], '/')));
        $this->assertFileExists(public_path(ltrim($response['imageOg'], '/')));
    }

    public function test_upload_generates_single_variant_for_logo_context(): void
    {
        $admin = User::factory()->create();
        $file = UploadedFile::fake()->image('logo.png', 800, 800);

        $response = $this->actingAs($admin)
            ->post(route('admin.upload'), ['file' => $file, 'context' => 'partners', 'variant' => 'logo'])
            ->assertOk()
            ->json();

        $this->assertStringStartsWith('/uploads/partners/', $response['image']);
        $this->assertStringEndsWith('-logo.webp', $response['image']);
        $this->assertNull($response['imageThumb']);
        $this->assertNull($response['imageOg']);
        $this->assertFileExists(public_path(ltrim($response['image'], '/')));
    }

    public function test_a_spoofed_non_image_file_is_rejected(): void
    {
        $admin = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('fake.jpg', 'this is not really an image');

        $this->actingAs($admin)
            ->post(route('admin.upload'), ['file' => $file, 'context' => 'products'])
            ->assertStatus(400)
            ->assertJson(['error' => 'File bukan gambar yang valid.']);
    }

    public function test_replacing_a_product_image_deletes_the_old_variants(): void
    {
        $this->seed(\Database\Seeders\BrandSeeder::class);
        $admin = User::factory()->create();
        $brand = \App\Models\Brand::query()->first();

        $firstUpload = $this->actingAs($admin)
            ->post(route('admin.upload'), [
                'file' => UploadedFile::fake()->image('one.jpg', 1200, 800),
                'context' => 'products',
            ])->json();

        $oldContentPath = public_path(ltrim($firstUpload['image'], '/'));
        $this->assertFileExists($oldContentPath);

        $product = \App\Models\Product::create([
            'brand_id' => $brand->id,
            'slug' => 'produk-uji-gambar',
            'name_id' => 'Produk Uji', 'name_en' => 'Test Product',
            'short_id' => 'x', 'short_en' => 'x',
            'personality_id' => 'x', 'personality_en' => 'x',
            'description_id' => 'x', 'description_en' => 'x',
            'highlights' => [], 'notes' => [],
            'color' => '#000', 'color_dark' => '#000',
            'image' => $firstUpload['image'],
            'image_thumb' => $firstUpload['imageThumb'],
            'image_og' => $firstUpload['imageOg'],
        ]);

        $secondUpload = $this->actingAs($admin)
            ->post(route('admin.upload'), [
                'file' => UploadedFile::fake()->image('two.jpg', 1200, 800),
                'context' => 'products',
            ])->json();

        $updateResponse = $this->actingAs($admin)->patch(route('admin.products.update', $product), [
            'brand_id' => $brand->id,
            'slug' => 'produk-uji-gambar',
            'group' => '',
            'packaging' => '',
            'name' => ['id' => 'Produk Uji', 'en' => 'Test Product'],
            'short' => ['id' => 'x', 'en' => 'x'],
            'personality' => ['id' => 'x', 'en' => 'x'],
            'serving' => ['id' => '', 'en' => ''],
            'description' => ['id' => 'x', 'en' => 'x'],
            'highlights' => ['id' => '', 'en' => ''],
            'notes' => ['id' => '', 'en' => ''],
            'color' => '#000', 'color_dark' => '#000',
            'image' => $secondUpload['image'],
            'image_thumb' => $secondUpload['imageThumb'],
            'image_og' => $secondUpload['imageOg'],
            'featured' => false, 'placeholder' => false, 'is_active' => true, 'sort_order' => 0,
        ]);

        $updateResponse->assertRedirect(route('admin.products.index'));
        $this->assertFileDoesNotExist($oldContentPath);
        $this->assertFileExists(public_path(ltrim($secondUpload['image'], '/')));
    }
}
