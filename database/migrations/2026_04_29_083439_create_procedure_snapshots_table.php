<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedure_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('step_name');
            $table->longText('snapshot_data'); // Stores the state/IDs needed for rollback
            $table->string('batch_id')->nullable();
            $table->boolean('is_rolled_back')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_snapshots');
    }
};
