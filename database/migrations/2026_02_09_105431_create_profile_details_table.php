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
        Schema::create('profile_details', function (Blueprint $table) {
            $table->id();
            $table->enum('profile_type', ['student', 'institution', 'region', 'directorate', 'inductee']);
            $table->foreignId('consumer_id')->constrained('consumers')->onDelete('cascade');
            $table->string('name');
            $table->string('father_or_guardian_name')->nullable();
            $table->string('region_name')->nullable();
            $table->string('institution_name')->nullable();
            $table->string('institution_level')->nullable();
            $table->string('class')->nullable();
            $table->string('section')->nullable();
            $table->json('fee_fund_structure_id')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();

            $table->index('profile_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_details');
    }
};
