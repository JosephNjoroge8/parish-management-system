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
        Schema::create('activity_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id');
            $table->unsignedBigInteger('member_id');
            $table->datetime('registered_at')->nullable();
            $table->boolean('attended')->default(false);
            $table->enum('role', ['participant', 'organizer', 'leader', 'volunteer'])->default('participant');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('registered_by')->references('id')->on('users')->onDelete('set null');

            // Ensure unique participation per activity
            $table->unique(['activity_id', 'member_id']);

            // Indexes for performance
            $table->index(['activity_id', 'attended']);
            $table->index(['member_id', 'role']);
            $table->index('registered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_participants');
    }
};