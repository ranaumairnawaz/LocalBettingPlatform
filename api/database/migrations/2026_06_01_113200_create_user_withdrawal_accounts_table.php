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
        Schema::create('user_withdrawal_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('withdrawal_method_id')->constrained('withdrawal_methods')->onDelete('cascade');
            $table->text('account_details'); // JSON key-value map (e.g. {"Account Name": "Jane", "Account Number": "0300123"})
            $table->timestamps();

            // One account per method per user
            $table->unique(['user_id', 'withdrawal_method_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_withdrawal_accounts');
    }
};
