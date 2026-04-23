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
            if (!Schema::hasColumn('profile_details', 'level_id')) {
                $table->unsignedBigInteger('level_id')->nullable()->after('institution_name');
            }
            if (!Schema::hasColumn('profile_details', 'school_class_id')) {
                $table->unsignedBigInteger('school_class_id')->nullable()->after('level_id');
            }
        });

        Schema::table('profile_details', function (Blueprint $table) {
            // Add foreign keys if they don't exist (checking for column existence is safer here)
            // Note: level_id check again to handle potential partial commit
            if (Schema::hasColumn('profile_details', 'level_id')) {
                try {
                    $table->foreign('level_id')->references('id')->on('levels');
                } catch (\Exception $e) {}
            }
            if (Schema::hasColumn('profile_details', 'school_class_id')) {
                try {
                    $table->foreign('school_class_id')->references('id')->on('school_classes');
                } catch (\Exception $e) {}
            }
            
            // Add indexes if they don't exist
            try {
                $table->index('institution_level');
            } catch (\Exception $e) {}
            
            try {
                $table->index('class');
            } catch (\Exception $e) {}
            
            // consumer_id is already indexed, so we skip it.
            // fee_fund_category_ids is JSON and cannot be indexed directly.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_details', function (Blueprint $table) {
            $table->dropForeign(['level_id']);
            $table->dropForeign(['school_class_id']);
            $table->dropIndex(['institution_level']);
            $table->dropIndex(['class']);
            $table->dropIndex(['consumer_id']);
            $table->dropColumn(['level_id', 'school_class_id']);
        });
    }
};
