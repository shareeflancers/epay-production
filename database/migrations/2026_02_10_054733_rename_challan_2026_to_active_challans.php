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
        Schema::rename('challan_2026', 'active_challans');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('active_challans', 'challan_2026');
    }
};
