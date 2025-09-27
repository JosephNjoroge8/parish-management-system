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
            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->timestamp('registered_at')->default(now());
            $table->boolean('attended')->default(false);
            $table->enum('role', ['participant', 'organizer', 'volunteer', 'leader'])->default('participant');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Ensure a member can only register once per activity
            $table->unique(['activity_id', 'member_id'], 'activity_member_unique');
            
            // Indexes for better performance
            $table->index(['activity_id', 'attended']);
            $table->index(['member_id', 'registered_at']);
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
