<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_translations', function (Blueprint $table) {
            $table->id();
            $table->string('content_type', 30);
            $table->unsignedBigInteger('content_id');
            $table->uuid('revision');
            $table->string('status', 30)->default('pending');
            $table->json('fields');
            $table->timestamps();
            $table->unique(['content_type', 'content_id']);
        });
        Schema::table('settings', fn (Blueprint $table) => $table->text('value_en')->nullable());
    }

    public function down(): void
    {
        Schema::table('settings', fn (Blueprint $table) => $table->dropColumn('value_en'));
        Schema::dropIfExists('content_translations');
    }
};
