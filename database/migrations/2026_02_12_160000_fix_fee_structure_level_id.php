<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fee_fund_structure', function (Blueprint $table) {
            // Ensure index exists
            $table->index('level_id');
            // Ensure foreign key exists
            $table->foreign('level_id')->references('id')->on('levels')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_fund_structure', function (Blueprint $table) {
            $table->dropForeign(['level_id']);
            $table->dropIndex(['level_id']);
        });
    }
};
