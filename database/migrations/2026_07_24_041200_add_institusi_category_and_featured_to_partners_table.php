<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Converts category from a true SQL ENUM to a plain string — same
     * lesson already learned for TekoPackaging (see its docblock):
     * enum columns make adding a value ("institusi") a schema migration
     * on every driver instead of just widening the app-level Rule::in()
     * list. Validity is enforced in StorePartnerRequest/UpdatePartnerRequest.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite has no ALTER COLUMN; swap the enum-emulating CHECK
            // constraint for a plain column via a rebuilt temp table. The
            // composite index has to go first — SQLite refuses to drop a
            // column that's part of an index.
            Schema::table('partners', function (Blueprint $table) {
                $table->dropIndex('partners_category_is_active_sort_order_index');
                $table->string('category_new', 50)->nullable()->after('category');
            });
            DB::statement('UPDATE partners SET category_new = category');
            Schema::table('partners', function (Blueprint $table) {
                $table->dropColumn('category');
            });
            Schema::table('partners', function (Blueprint $table) {
                $table->renameColumn('category_new', 'category');
            });
            Schema::table('partners', function (Blueprint $table) {
                $table->index(['category', 'is_active', 'sort_order']);
            });
        } else {
            DB::statement('ALTER TABLE partners MODIFY category VARCHAR(50) NOT NULL');
        }

        Schema::table('partners', function (Blueprint $table) {
            // Distinguishes the curated homepage selection from the full
            // /mitra directory (which lists every active partner).
            $table->boolean('featured')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('featured');
        });
    }
};
