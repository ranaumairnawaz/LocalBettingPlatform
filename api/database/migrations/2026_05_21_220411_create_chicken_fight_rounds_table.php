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
        Schema::create('chicken_fight_rounds', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('round_index')->unique();
            $table->enum('winner', ['white', 'black'])->nullable();
            $table->enum('winner_hp', ['over', 'under'])->nullable();
            $table->boolean('settled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chicken_fight_rounds');
    }
};
