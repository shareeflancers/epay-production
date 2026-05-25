<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enforce the business rule: one active challan per consumer at a time.
     *
     * MySQL won't let us drop idx_ac_consumer while the FK fk_ac_consumer
     * depends on it. So we:
     *  1. Drop the FK
     *  2. Drop the plain index
     *  3. Add a UNIQUE index (which also acts as the FK backing index)
     *  4. Re-add the FK
     */
    public function up(): void
    {
        Schema::table('active_challans', function (Blueprint $table) {
            // 1. Drop the FK that references the plain index.
            $table->dropForeign('fk_ac_consumer');

            // 2. Drop the now-orphaned plain btree index.
            $table->dropIndex('idx_ac_consumer');

            // 3. Add a UNIQUE index — one row per consumer_id in active_challans.
            $table->unique('consumer_id', 'active_challans_consumer_id_unique');

            // 4. Re-add the FK, now backed by the unique index.
            $table->foreign('consumer_id', 'fk_ac_consumer')
                ->references('id')->on('consumers')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migration: restore the plain (non-unique) index and original FK.
     */
    public function down(): void
    {
        Schema::table('active_challans', function (Blueprint $table) {
            $table->dropForeign('fk_ac_consumer');
            $table->dropUnique('active_challans_consumer_id_unique');

            // Restore the original plain index and FK.
            $table->index('consumer_id', 'idx_ac_consumer');
            $table->foreign('consumer_id', 'fk_ac_consumer')
                ->references('id')->on('consumers')
                ->onDelete('cascade');
        });
    }
};
