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
        if (Schema::hasColumn('fee_fund_structure', 'region')) {
            Schema::table('fee_fund_structure', function (Blueprint $table) {
                $table->dropColumn(['region', 'institution_level']);
            });
        }

        Schema::table('fee_fund_structure', function (Blueprint $table) {
            if (!Schema::hasColumn('fee_fund_structure', 'region_id')) {
                $table->foreignId('region_id')->nullable()->constrained('regions')->index();
            }
            if (!Schema::hasColumn('fee_fund_structure', 'level_id')) {
                $table->foreignId('level_id')->nullable()->constrained('levels')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_fund_structure', function (Blueprint $table) {
            if (Schema::hasColumn('fee_fund_structure', 'region_id')) {
                $table->dropForeign(['region_id']);
                $table->dropColumn('region_id');
            }
            if (Schema::hasColumn('fee_fund_structure', 'level_id')) {
                $table->dropForeign(['level_id']);
                $table->dropColumn('level_id');
            }
            if (!Schema::hasColumn('fee_fund_structure', 'region')) {
                $table->string('region')->nullable();
                $table->string('institution_level')->nullable();
            }
        });
    }
};
