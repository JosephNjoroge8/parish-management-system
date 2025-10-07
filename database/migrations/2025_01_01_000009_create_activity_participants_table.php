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
            $table->unsignedBigInteger('activity_id')->index();
            $table->unsignedBigInteger('member_id')->nullable()->index();
            $table->string('participant_name', 100)->index(); // In case member_id is not available
            $table->enum('participation_status', ['registered', 'attended', 'absent', 'cancelled'])->default('registered')->index();
            $table->date('registration_date')->index();
            $table->decimal('contribution', 10, 2)->nullable(); // If activity involves contributions
            $table->text('notes')->nullable();
            $table->timestamps();

            // Ensure unique participation per activity
            $table->unique(['activity_id', 'member_id'], 'unique_activity_member');

            // Indexes for performance
            $table->index(['activity_id', 'participation_status']);
            $table->index(['registration_date', 'participation_status']);
            $table->index(['participant_name', 'participation_status']);
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
