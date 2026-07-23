<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('placeholder');
            $table->boolean('is_active')->default(true)->after('sort_order');
            $table->string('image_thumb')->nullable()->after('image');
            $table->string('image_og')->nullable()->after('image_thumb');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sort_order', 'is_active', 'image_thumb', 'image_og']);
        });
    }
};
