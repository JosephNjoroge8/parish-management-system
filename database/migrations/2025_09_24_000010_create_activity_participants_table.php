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
        if (! Schema::hasTable('activity_participants')) {
            Schema::create('activity_participants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('activity_id');
                $table->unsignedBigInteger('member_id');
                $table->enum('participation_status', ['registered', 'attended', 'absent', 'cancelled'])
                    ->default('registered');
                $table->enum('role', ['participant', 'organizer', 'facilitator', 'volunteer'])
                    ->default('participant');
                $table->dateTime('registered_at')->nullable();
                $table->dateTime('attended_at')->nullable();
                $table->text('notes')->nullable();
                $table->decimal('contribution_amount', 8, 2)->nullable(); // If there's a fee or contribution
                $table->boolean('contribution_paid')->default(false);
                $table->timestamps();

                // Foreign keys and indexes
                $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
                $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');

                $table->unique(['activity_id', 'member_id']); // Prevent duplicate registrations
                $table->index('participation_status');
                $table->index('role');
                $table->index('registered_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_participants');
    }
};
