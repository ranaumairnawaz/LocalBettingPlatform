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
        Schema::create('ludo_room_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ludo_room_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('color'); // red, blue, green, yellow
            $table->boolean('is_ready')->default(false);
            $table->enum('status', ['active', 'disconnected', 'finished'])->default('active');
            $table->integer('rank')->nullable(); // 1st, 2nd, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ludo_room_players');
    }
};
