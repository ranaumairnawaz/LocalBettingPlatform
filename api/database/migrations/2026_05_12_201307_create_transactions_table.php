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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['deposit', 'withdrawal', 'bet', 'win', 'commission', 'referral_bonus']);
            $table->string('method')->nullable(); // jazzcash, easypaisa, binance, wallet
            $table->enum('status', ['pending', 'completed', 'rejected'])->default('pending');
            $table->string('reference')->unique()->nullable(); // External TRX ID or Internal Ref
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
