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
        Schema::dropIfExists('challan_transaction_ledger_history');
    }

    /** 
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To properly reverse, we'd need the full create definition.
        // For now, we leave it empty or add basic structure if needed.
        // Assuming irreversibility for this specific task context or manual restoration if needed.
        // Or we can copy from the create migration if available.
        // Given user wants to DROP, likely won't revert soon.
         Schema::create('challan_transaction_ledger_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumer_id')->constrained('consumers')->onDelete('cascade');
            $table->unsignedBigInteger('challan_id');
            $table->enum('status', ['U', 'P', 'B'])->default('U');
            $table->foreignId('fee_fund_category_id')->constrained('fee_fund_category')->onDelete('cascade');
            $table->foreignId('fee_fund_structure_id')->constrained('fee_fund_structure')->onDelete('cascade');
            $table->timestamps();
            $table->index('consumer_id');
            $table->index('challan_id');
            $table->index('status');
            $table->index('fee_fund_category_id');
            $table->index('fee_fund_structure_id');
            $table->foreign('challan_id')->references('id')->on('challan_2026')->onDelete('cascade');
        });
    }
};
