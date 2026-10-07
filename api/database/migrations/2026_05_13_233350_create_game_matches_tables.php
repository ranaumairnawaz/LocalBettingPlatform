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
        Schema::create('game_matches', function (Blueprint $table) {
            $table->id();
            $table->string('room_code')->unique();
            $table->string('game_type'); // cricket, carrom, pool
            $table->decimal('total_pot', 15, 2)->default(0);
            $table->enum('status', ['waiting', 'playing', 'completed', 'cancelled'])->default('waiting');
            $table->json('game_state')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('game_match_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_match_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('bet_amount', 15, 2);
            $table->enum('status', ['active', 'disconnected', 'finished'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_match_players');
        Schema::dropIfExists('game_matches');
    }
};
