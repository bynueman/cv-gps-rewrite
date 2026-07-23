<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = json_decode(
            file_get_contents(database_path('seeders/data/products.json')),
            true
        );

        $brandIds = Brand::pluck('id', 'key');

        foreach ($products as $product) {
            $brandKey = $product['brand'];
            unset($product['brand']);
            $product['brand_id'] = $brandIds[$brandKey];

            Product::updateOrCreate(
                ['brand_id' => $product['brand_id'], 'slug' => $product['slug']],
                $product
            );
        }
    }
}
