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
        Schema::create('game_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->decimal('bet_amount', 15, 2);
            $table->decimal('payout_amount', 15, 2)->default(0);
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->enum('result', ['win', 'lose', 'draw', 'pending'])->default('pending');
            $table->json('game_data')->nullable(); // Store specific game state if needed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_logs');
    }
};
