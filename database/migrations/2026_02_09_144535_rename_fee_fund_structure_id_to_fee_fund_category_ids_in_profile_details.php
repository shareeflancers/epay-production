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
        Schema::table('profile_details', function (Blueprint $table) {
            $table->renameColumn('fee_fund_structure_id', 'fee_fund_category_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_details', function (Blueprint $table) {
            $table->renameColumn('fee_fund_category_ids', 'fee_fund_structure_id');
        });
    }
};
