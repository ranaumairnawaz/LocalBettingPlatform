<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_prize_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('tournament_registration_id');
            $table->foreign('tournament_registration_id', 'fk_tpd_reg_id')->references('id')->on('tournament_registrations')->onDelete('cascade');
            
            // Prize details
            $table->integer('rank');
            $table->string('rank_label');
            $table->decimal('prize_amount', 10, 2);
            $table->decimal('percentage_of_pool', 5, 2);
            
            // Transaction reference
            $table->foreignId('transaction_id')->nullable()->constrained();
            
            $table->timestamp('distributed_at');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_prize_distributions');
    }
};
