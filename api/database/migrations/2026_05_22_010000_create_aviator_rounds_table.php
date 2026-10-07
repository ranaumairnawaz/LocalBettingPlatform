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
        Schema::create('aviator_rounds', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('round_index')->unique();
            $table->decimal('crash_multiplier', 8, 2)->nullable();
            $table->boolean('settled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aviator_rounds');
    }
};
