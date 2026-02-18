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
        Schema::create('fee_fund_structure', function (Blueprint $table) {
            $table->id();
            $table->string('region');
            $table->string('institution_level');
            $table->foreignId('fee_fund_category_id')->constrained('fee_fund_category')->onDelete('cascade');
            $table->decimal('admission_fee', 12, 2)->default(0);
            $table->decimal('slc', 12, 2)->default(0);
            $table->decimal('tution_fee', 12, 2)->default(0);
            $table->decimal('idf', 12, 2)->default(0);
            $table->decimal('exam_fee', 12, 2)->default(0);
            $table->decimal('it_fee', 12, 2)->default(0);
            $table->decimal('csf', 12, 2)->default(0);
            $table->decimal('rdf', 12, 2)->default(0);
            $table->decimal('cdf', 12, 2)->default(0);
            $table->decimal('security_fund', 12, 2)->default(0);
            $table->decimal('bs_fund', 12, 2)->default(0);
            $table->decimal('prep_fund', 12, 2)->default(0);
            $table->decimal('donation_fund', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_fund_structure');
    }
};
