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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_agent')->default(false)->after('is_admin');
            $table->string('super_agent_team_name', 150)->nullable()->unique()->after('is_super_agent');
            $table->string('super_agent_referral_code', 20)->nullable()->unique()->after('super_agent_team_name');
            $table->enum('super_agent_rank', ['bronze', 'silver', 'gold', 'diamond'])->nullable()->after('super_agent_referral_code');
            $table->timestamp('super_agent_approved_at')->nullable()->after('super_agent_rank');
            $table->enum('super_agent_application_status', ['none', 'pending', 'approved', 'rejected'])->default('none')->after('super_agent_approved_at');
            $table->text('super_agent_application_note')->nullable()->after('super_agent_application_status');
            $table->string('super_agent_group_proof', 255)->nullable()->after('super_agent_application_note');
            $table->timestamp('super_agent_referral_code_updated_at')->nullable()->after('super_agent_group_proof');
            $table->timestamp('super_agent_rejected_at')->nullable()->after('super_agent_referral_code_updated_at');
            
            // Indexes for performance
            $table->index(['is_super_agent', 'super_agent_rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_super_agent', 'super_agent_rank']);
            $table->dropUnique(['super_agent_team_name']);
            $table->dropUnique(['super_agent_referral_code']);
            $table->dropColumn([
                'is_super_agent',
                'super_agent_team_name',
                'super_agent_referral_code',
                'super_agent_rank',
                'super_agent_approved_at',
                'super_agent_application_status',
                'super_agent_application_note',
                'super_agent_group_proof',
                'super_agent_referral_code_updated_at',
                'super_agent_rejected_at',
            ]);
        });
    }
};
