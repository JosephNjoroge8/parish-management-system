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
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('community_group_id');
            $table->unsignedBigInteger('member_id');
            $table->date('joined_date')->default(now());
            $table->enum('role', ['member', 'leader', 'assistant_leader', 'secretary', 'treasurer'])->default('member');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('community_group_id')->references('id')->on('community_groups')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');

            // Ensure unique membership per group
            $table->unique(['community_group_id', 'member_id']);

            // Indexes
            $table->index(['community_group_id', 'status']);
            $table->index(['member_id', 'role']);
            $table->index('joined_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_members');
    }
};