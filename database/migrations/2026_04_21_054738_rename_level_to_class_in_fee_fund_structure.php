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
            $table->dropForeign(['level_id']);
            $table->renameColumn('level_id', 'school_class_id');
        });

        Schema::table('fee_fund_structure', function (Blueprint $table) {
            $table->foreign('school_class_id')->references('id')->on('school_classes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_fund_structure', function (Blueprint $table) {
            $table->dropForeign(['school_class_id']);
            $table->renameColumn('school_class_id', 'level_id');
            $table->foreign('level_id')->references('id')->on('levels');
        });
    }
};
