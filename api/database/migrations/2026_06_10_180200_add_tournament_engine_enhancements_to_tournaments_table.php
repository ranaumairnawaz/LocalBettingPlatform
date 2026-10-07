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
        Schema::table('tournaments', function (Blueprint $table) {
            $table->boolean('is_dynamic_fee')->default(false)->after('entry_fee');
            $table->decimal('base_entry_fee', 10, 2)->default(0)->after('is_dynamic_fee');
            $table->decimal('fee_increment_amount', 10, 2)->default(0)->after('base_entry_fee');
            $table->integer('fee_increment_every_n_players')->default(0)->after('fee_increment_amount');
            $table->enum('bracket_type', ['single_elimination', 'double_elimination'])->default('single_elimination')->after('platform_commission_percentage');
        });

        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->enum('bracket_tier', ['winners_bracket', 'losers_bracket', 'grand_finals'])->default('winners_bracket')->after('match_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            //
        });
    }
};
