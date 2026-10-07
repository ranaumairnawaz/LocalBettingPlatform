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
        Schema::create('deposit_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Easypaisa, JazzCash, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('method_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('method_id')->constrained('deposit_methods')->onDelete('cascade');
            $table->decimal('receiving_limit', 15, 2); // Current available limit
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('account_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('method_accounts')->onDelete('cascade');
            $table->string('key'); // Account Title, Account Number, etc.
            $table->string('value');
            $table->enum('type', ['string', 'image'])->default('string');
            $table->timestamps();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('method_account_id')->nullable()->after('method')->constrained('method_accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposit_management_tables');
    }
};
