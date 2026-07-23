<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // globally unique, unlike products
            $table->date('date');
            $table->string('category_slug'); // validated against App\Support\ArticleCategory
            $table->string('title_id');
            $table->string('title_en');
            $table->string('excerpt_id'); // doubles as meta description
            $table->string('excerpt_en');
            $table->longText('body_id'); // sanitized rich-text HTML
            $table->longText('body_en');
            $table->string('tags_id')->default(''); // comma-separated
            $table->string('tags_en')->default('');
            $table->string('image')->nullable();
            $table->string('image_thumb')->nullable();
            $table->string('image_og')->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('published')->default(false);
            $table->timestamps();

            $table->index(['published', 'featured', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
