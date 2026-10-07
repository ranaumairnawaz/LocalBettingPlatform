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
        Schema::create('content_bonus_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('platform', ['youtube', 'instagram', 'tiktok']);
            $table->string('video_url')->unique();
            $table->integer('view_count');
            $table->integer('auto_detected_views')->nullable();
            $table->string('screenshot_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->decimal('bonus_amount', 12, 2)->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['status']);
            $table->index(['platform']);
            $table->unique(['platform', 'video_url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_bonus_requests');
    }
};
