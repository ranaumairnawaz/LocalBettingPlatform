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
        Schema::create('ludo_game_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ludo_room_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('bet_amount', 15, 2); // The locked entry fee
            $table->decimal('payout_amount', 15, 2)->default(0);
            $table->decimal('platform_fee_taken', 15, 2)->default(0);
            $table->enum('result', ['pending', 'win', 'lose', 'refund'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ludo_game_logs');
    }
};
