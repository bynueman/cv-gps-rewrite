<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('group')->nullable(); // "rtd" | "brew" — Putri Teko only
            $table->string('packaging')->nullable(); // botol|kotak|toples|kemasan|besek — Putri Teko only
            $table->string('name_id');
            $table->string('name_en');
            $table->string('short_id');
            $table->string('short_en');
            $table->string('personality_id');
            $table->string('personality_en');
            $table->string('serving_id')->nullable();
            $table->string('serving_en')->nullable();
            $table->text('description_id');
            $table->text('description_en');
            $table->json('highlights'); // [{id, en}, ...]
            $table->json('notes'); // [{id, en}, ...]
            $table->string('color');
            $table->string('color_dark');
            $table->string('image')->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('placeholder')->default(false);
            $table->timestamps();

            // Slugs repeat across packaging forms within a brand (e.g.
            // jahe-merah-kotak vs jahe-merah-toples) — unique per brand only.
            $table->unique(['brand_id', 'slug']);
            $table->index(['brand_id', 'featured', 'group']);
            $table->index(['brand_id', 'packaging']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
