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
        Schema::create('super_agent_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('super_agent_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('source_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('commission_type', ['deposit_commission', 'game_commission']);
            $table->integer('referral_level'); // 1-5
            $table->decimal('commission_amount', 12, 2);
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
            
            // Indexes for queries
            $table->index(['super_agent_id', 'created_at']);
            $table->index(['source_user_id']);
            $table->index(['is_paid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('super_agent_commissions');
    }
};
