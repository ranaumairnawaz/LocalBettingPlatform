<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('round_id')->nullable();
            
            // Bracket structure
            $table->integer('round_number');
            $table->integer('match_number');
            
            // Players
            $table->foreignId('player1_id')->nullable()->constrained('users');
            $table->foreignId('player2_id')->nullable()->constrained('users');
            $table->foreignId('winner_id')->nullable()->constrained('users');
            
            // Match status
            $table->enum('status', ['pending', 'scheduled', 'in_progress', 'completed', 'cancelled'])->default('pending');
            
            // Timing
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Ready check
            $table->boolean('player1_ready')->default(false);
            $table->boolean('player2_ready')->default(false);
            $table->timestamp('ready_check_started_at')->nullable();
            
            // Game room
            $table->string('game_room_id')->nullable();
            
            // Performance metrics for tiebreaker
            $table->json('player1_metrics')->nullable();
            $table->json('player2_metrics')->nullable();
            
            // Result
            $table->enum('result_type', ['normal', 'forfeit', 'walkover', 'double_disconnect', 'random'])->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
