<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Increase to 64 chars to safely fit SA-{slug}-{6random} for any team name
            $table->string('super_agent_referral_code', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('super_agent_referral_code', 20)->nullable()->change();
        });
    }
};
