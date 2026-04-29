<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('country')->nullable()->after('ip_address');
            $table->string('city')->nullable()->after('country');
            $table->string('isp')->nullable()->after('city');
        });

        Schema::table('api_logs', function (Blueprint $table) {
            $table->string('country')->nullable()->after('ip_address');
            $table->string('city')->nullable()->after('country');
            $table->string('isp')->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['country', 'city', 'isp']);
        });

        Schema::table('api_logs', function (Blueprint $table) {
            $table->dropColumn(['country', 'city', 'isp']);
        });
    }
};
