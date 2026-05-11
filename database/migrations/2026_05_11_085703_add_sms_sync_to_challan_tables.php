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
        Schema::table('active_challans', function (Blueprint $table) {
            $table->tinyInteger('sms_sync')->default(0)->index()->after('sms_update');
        });

        Schema::table('challan_history', function (Blueprint $table) {
            $table->tinyInteger('sms_sync')->default(0)->index()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('active_challans', function (Blueprint $table) {
            $table->dropColumn('sms_sync');
        });

        Schema::table('challan_history', function (Blueprint $table) {
            $table->dropColumn('sms_sync');
        });
    }
};
