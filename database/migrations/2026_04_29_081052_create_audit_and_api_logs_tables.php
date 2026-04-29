<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $规划) {
            $规划->id();
            $规划->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $规划->string('action');
            $规划->text('description')->nullable();
            $规划->string('ip_address', 45)->nullable();
            $规划->text('user_agent')->nullable();
            $规划->timestamps();
        });

        Schema::create('api_logs', function (Blueprint $规划) {
            $规划->id();
            $规划->string('endpoint');
            $规划->string('method', 10);
            $规划->json('request_payload')->nullable();
            $规划->json('response_payload')->nullable();
            $规划->integer('status_code');
            $规划->string('ip_address', 45)->nullable();
            $规划->integer('duration_ms')->nullable();
            $规划->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
        Schema::dropIfExists('audit_logs');
    }
};
