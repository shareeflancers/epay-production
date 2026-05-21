<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE active_challans MODIFY COLUMN fee_type ENUM('fee', 'voucher', 'sis_voucher', 'induction_fee') NOT NULL DEFAULT 'fee'");
        DB::statement("ALTER TABLE challan_history MODIFY COLUMN fee_type ENUM('fee', 'voucher', 'sis_voucher', 'induction_fee') NOT NULL DEFAULT 'fee'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First rollback any records that use the new enum values to avoid db errors
        DB::table('active_challans')
            ->whereIn('fee_type', ['sis_voucher', 'induction_fee'])
            ->update(['fee_type' => 'voucher']);

        DB::table('challan_history')
            ->whereIn('fee_type', ['sis_voucher', 'induction_fee'])
            ->update(['fee_type' => 'voucher']);

        DB::statement("ALTER TABLE active_challans MODIFY COLUMN fee_type ENUM('fee', 'voucher') NOT NULL DEFAULT 'fee'");
        DB::statement("ALTER TABLE challan_history MODIFY COLUMN fee_type ENUM('fee', 'voucher') NOT NULL DEFAULT 'fee'");
    }
};
