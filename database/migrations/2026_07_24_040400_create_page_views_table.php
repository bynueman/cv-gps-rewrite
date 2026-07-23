<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Raw daily hit log — deliberately no cookie and no raw IP (see
     * visitor_hash). Rows older than 30 days are pruned by the daily
     * aggregation job once folded into page_view_daily.
     */
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->date('viewed_on');
            $table->string('browser')->nullable();
            $table->string('device')->nullable(); // mobile|desktop|tablet
            $table->string('referrer_domain')->nullable();
            $table->string('visitor_hash', 64); // hash(ip + user-agent + date), raw IP never stored
            $table->timestamps();

            $table->index(['viewed_on', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
