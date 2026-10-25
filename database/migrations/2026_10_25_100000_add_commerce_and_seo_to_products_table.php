<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('shopee_url', 2048)->nullable();
            $table->string('net_weight', 80)->nullable();
            foreach (['seo_title', 'meta_description', 'image_alt'] as $field) {
                foreach (['id', 'en'] as $lang) {
                    $table->string($field.'_'.$lang)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn([
            'shopee_url', 'net_weight', 'seo_title_id', 'seo_title_en',
            'meta_description_id', 'meta_description_en', 'image_alt_id', 'image_alt_en',
        ]));
    }
};
