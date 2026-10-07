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
        Schema::create('super_agent_challenge_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained('super_agent_challenges')->onDelete('cascade');
            $table->foreignId('super_agent_id')->constrained('users')->onDelete('cascade');
            $table->integer('progress')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();
            
            // Composite unique key - one entry per SA per challenge
            $table->unique(['challenge_id', 'super_agent_id']);
            
            // Indexes
            $table->index(['super_agent_id']);
            $table->index(['completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('super_agent_challenge_entries');
    }
};
