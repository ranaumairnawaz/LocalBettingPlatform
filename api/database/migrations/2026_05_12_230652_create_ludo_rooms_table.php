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
        Schema::create('ludo_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_code')->unique();
            $table->integer('max_players')->default(4); // 2 or 4
            $table->decimal('entry_fee', 15, 2);
            $table->enum('status', ['waiting', 'playing', 'completed', 'cancelled'])->default('waiting');
            $table->json('game_state')->nullable(); // Stores board coordinates, whose turn, dice value
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ludo_rooms');
    }
};
