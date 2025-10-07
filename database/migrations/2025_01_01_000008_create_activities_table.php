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
            $table->string('activity_name', 100)->index();
            $table->text('description')->nullable();
            $table->date('activity_date')->index();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location', 100)->nullable();
            $table->string('organizer', 100)->nullable();
            $table->enum('activity_type', [
                'worship', 'meeting', 'social', 'fundraising', 'education',
                'outreach', 'celebration', 'other',
            ])->default('other')->index();
            $table->enum('activity_status', ['planned', 'ongoing', 'completed', 'cancelled'])->default('planned')->index();
            $table->decimal('budget', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->integer('expected_participants')->nullable();
            $table->integer('actual_participants')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['activity_date', 'activity_type']);
            $table->index(['activity_status', 'activity_date']);
            $table->index(['organizer', 'activity_date']);
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
