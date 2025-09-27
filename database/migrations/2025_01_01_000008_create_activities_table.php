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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('activity_type', [
                'mass', 'meeting', 'event', 'workshop', 'retreat', 
                'social', 'fundraising', 'community_service', 'youth', 
                'choir', 'prayer', 'celebration'
            ]);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location')->nullable();
            $table->string('organizer')->nullable();
            $table->unsignedBigInteger('community_group_id')->nullable();
            $table->integer('max_participants')->nullable();
            $table->boolean('registration_required')->default(false);
            $table->datetime('registration_deadline')->nullable();
            $table->enum('status', ['planned', 'active', 'completed', 'cancelled', 'postponed'])->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('community_group_id')->references('id')->on('community_groups')->onDelete('set null');

            // Indexes for performance
            $table->index(['activity_type', 'status']);
            $table->index(['start_date', 'status']);
            $table->index('community_group_id');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};