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
        Schema::table('consumers', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('is_deleted');
        });

        Schema::table('profile_details', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('is_deleted');
            $table->index('consumer_id'); // Foreign key, usually indexed but good to ensure
            // profile_type already indexed in creation migration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumers', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['is_deleted']);
        });

        Schema::table('profile_details', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['is_deleted']);
            $table->dropIndex(['consumer_id']);
        });
    }
};
