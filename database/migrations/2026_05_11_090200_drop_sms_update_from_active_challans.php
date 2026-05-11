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
            $table->dropColumn('sms_update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('active_challans', function (Blueprint $table) {
            $table->boolean('sms_update')->default(false)->after('status');
        });
    }
};
