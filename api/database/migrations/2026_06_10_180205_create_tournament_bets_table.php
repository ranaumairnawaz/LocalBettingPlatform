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
        Schema::create('tournament_bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tournament_match_id')->constrained()->onDelete('cascade');
            $table->foreignId('predicted_winner_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->decimal('odds', 5, 2)->default(2.00);
            $table->enum('status', ['pending', 'won', 'lost', 'refunded'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_bets');
    }
};

