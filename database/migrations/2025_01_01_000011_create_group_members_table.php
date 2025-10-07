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
        if (!Schema::hasTable('group_members')) {
            Schema::create('group_members', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('group_id')->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->date('join_date')->index();
                $table->date('leave_date')->nullable();
                $table->enum('membership_status', ['active', 'inactive', 'suspended', 'left'])->default('active')->index();
                $table->enum('role', ['member', 'leader', 'secretary', 'treasurer', 'coordinator'])->default('member')->index();
                $table->text('notes')->nullable();
                $table->timestamps();

                // Ensure unique member per group (for active memberships)
                $table->unique(['group_id', 'member_id', 'membership_status'], 'unique_active_group_member');

                // Indexes for performance
                $table->index(['group_id', 'membership_status']);
                $table->index(['member_id', 'membership_status']);
                $table->index(['join_date', 'membership_status']);
                $table->index(['role', 'membership_status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_members');
    }
};
