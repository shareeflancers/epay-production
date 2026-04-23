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
            $table->foreignId('fee_fund_head_id')->nullable()->constrained('fee_fund_heads')->onDelete('set null');
            $table->json('fee_head_amounts')->nullable();

            $table->dropColumn([
                'admission_fee',
                'slc',
                'tution_fee',
                'idf',
                'exam_fee',
                'it_fee',
                'csf',
                'rdf',
                'cdf',
                'security_fund',
                'bs_fund',
                'prep_fund',
                'donation_fund'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_fund_structure', function (Blueprint $table) {
            //
        });
    }
};
