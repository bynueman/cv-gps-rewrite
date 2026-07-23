<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // "kuicip" | "putri-teko"
            $table->string('name');
            $table->string('tag_id');
            $table->string('tag_en');
            $table->string('weight')->nullable();
            $table->text('story_id');
            $table->text('story_en');
            $table->string('href');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
