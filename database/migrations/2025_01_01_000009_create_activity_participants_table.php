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
                $table->unsignedBigInteger('member_id')->nullable();
                $table->string('participant_name', 100)->nullable(); // In case member_id is not available
                $table->enum('participation_status', ['registered', 'attended', 'absent', 'cancelled'])->default('registered');
                $table->date('registration_date');
                $table->decimal('contribution', 10, 2)->nullable(); // If activity involves contributions
                $table->text('notes')->nullable();
                $table->timestamps();

                // Primary indexes for foreign keys
                $table->index('activity_id', 'idx_act_activity');
                $table->index('member_id', 'idx_act_member');

                // Performance indexes with shortened names
                $table->index('participation_status', 'idx_act_status');
                $table->index('registration_date', 'idx_act_regdate');
                $table->index('participant_name', 'idx_act_name');

                // Composite indexes for common queries
                $table->index(['activity_id', 'participation_status'], 'idx_act_id_status');
                $table->index(['registration_date', 'participation_status'], 'idx_regdate_status');

                // Ensure unique participation per activity (only if member_id is not null)
                $table->unique(['activity_id', 'member_id'], 'unq_activity_member');
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
