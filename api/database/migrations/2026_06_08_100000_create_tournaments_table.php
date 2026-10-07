<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('entry_fee', 10, 2);
            $table->integer('min_players')->default(4);
            $table->integer('max_players')->default(64);
            $table->timestamp('registration_opens_at')->nullable();
            $table->timestamp('registration_closes_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            
            // Prize configuration (percentage-based)
            $table->decimal('winner_prize_percentage', 5, 2)->default(50.00);
            $table->decimal('runner_up_prize_percentage', 5, 2)->default(30.00);
            $table->boolean('include_semifinalist_prizes')->default(false);
            $table->decimal('semifinalist_prize_percentage', 5, 2)->nullable();
            
            // Platform commission
            $table->decimal('platform_commission_percentage', 5, 2)->default(10.00);
            
            // Status
            $table->enum('status', ['draft', 'open', 'registration_closed', 'in_progress', 'completed', 'cancelled'])->default('draft');
            
            // Financial tracking
            $table->integer('registered_players_count')->default(0);
            $table->decimal('total_pool', 10, 2)->default(0);
            $table->decimal('total_distributed_prizes', 10, 2)->default(0);
            $table->decimal('house_profit', 10, 2)->default(0);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
