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
        Schema::create('super_agent_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('challenge_type', ['team_signups', 'team_deposits', 'team_games_played']);
            $table->integer('target_value');
            $table->decimal('reward_amount', 12, 2);
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('ends_at')->useCurrent();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Indexes
            $table->index(['is_active']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('super_agent_challenges');
    }
};
