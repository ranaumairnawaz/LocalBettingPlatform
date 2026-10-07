<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('entry_fee_paid', 10, 2);
            $table->timestamp('registered_at');
            
            // Bracket position
            $table->integer('bracket_position')->nullable();
            $table->boolean('received_bye')->default(false);
            
            // Performance tracking
            $table->integer('final_rank')->nullable();
            $table->decimal('prize_won', 10, 2)->default(0);
            
            // Status
            $table->enum('status', ['registered', 'eliminated', 'winner', 'runner_up', 'semifinalist', 'forfeited', 'disqualified'])->default('registered');
            
            $table->timestamps();
            
            $table->unique(['tournament_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_registrations');
    }
};
