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
        Schema::create('challan_2026', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumer_id')->constrained('consumers')->onDelete('cascade');
            $table->string('challan_no', 30);
            $table->enum('status', ['U', 'P', 'B'])->default('U');
            $table->string('tran_auth_id', 6)->nullable();
            $table->string('bank_mnemonic', 8)->nullable();
            $table->date('due_date');
            $table->decimal('amount_base', 12, 2)->default(0);
            $table->decimal('amount_arrears', 12, 2)->default(0);
            $table->decimal('amount_within_dueDate', 12, 2)->default(0);
            $table->decimal('amount_after_dueDate', 12, 2)->default(0);
            $table->date('date_paid')->nullable();
            $table->enum('fee_type', ['fee', 'voucher'])->default('fee');
            $table->string('reserved', 400)->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();

            $table->index('challan_no');
            $table->index('status');
            $table->index('due_date');
            $table->index('fee_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challan_2026');
    }
};
