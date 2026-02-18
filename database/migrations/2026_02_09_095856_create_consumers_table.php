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
        Schema::create('consumers', function (Blueprint $table) {
            $table->id();
            $table->enum('consumer_type', ['student', 'institution', 'region', 'directorate', 'inductee']);
            $table->string('identification_number', 13);
            // === Required for 1LINK Inquiry/Payment ===
            $table->string('consumer_number', 12);
            $table->string('institution_id', 3);
            $table->string('region_id', 3);
            $table->boolean('is_active')->default(1);
            $table->timestamps();

            $table->index('consumer_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumers');
    }
};
