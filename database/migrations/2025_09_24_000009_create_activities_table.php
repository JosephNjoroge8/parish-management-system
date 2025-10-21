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
        if (! Schema::hasTable('activities')) {
            Schema::create('activities', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->enum('activity_type', [
                    'mass', 'prayer_service', 'meeting', 'training', 'social_event',
                    'fundraising', 'pilgrimage', 'retreat', 'community_service', 'other',
                ]);
                $table->dateTime('start_date');
                $table->dateTime('end_date')->nullable();
                $table->string('location');
                $table->unsignedBigInteger('organizer_id')->nullable(); // User organizing
                $table->unsignedBigInteger('community_group_id')->nullable(); // If organized by a group
                $table->enum('status', ['planned', 'ongoing', 'completed', 'cancelled'])
                    ->default('planned');
                $table->integer('expected_attendance')->nullable();
                $table->integer('actual_attendance')->nullable();
                $table->decimal('budget', 10, 2)->nullable();
                $table->decimal('actual_cost', 10, 2)->nullable();
                $table->text('requirements')->nullable(); // What's needed for the activity
                $table->text('notes')->nullable();
                $table->boolean('is_recurring')->default(false);
                $table->string('recurrence_pattern')->nullable(); // E.g., "weekly", "monthly"
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                // Foreign keys and indexes
                $table->foreign('organizer_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('community_group_id')->references('id')->on('community_groups')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

                $table->index('activity_type');
                $table->index('status');
                $table->index('start_date');
                $table->index('community_group_id');
                $table->index(['start_date', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
