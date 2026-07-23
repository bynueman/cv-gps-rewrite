<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daily aggregate the dashboard reads from — page_views rows are
     * folded into this table once a day, then pruned. browser_json /
     * device_json hold a { label: count } breakdown for that day+path.
     */
    public function up(): void
    {
        Schema::create('page_view_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('path');
            $table->unsignedInteger('hits')->default(0);
            $table->unsignedInteger('uniques')->default(0);
            $table->json('browser_json')->nullable();
            $table->json('device_json')->nullable();
            $table->timestamps();

            $table->unique(['date', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_view_daily');
    }
};
