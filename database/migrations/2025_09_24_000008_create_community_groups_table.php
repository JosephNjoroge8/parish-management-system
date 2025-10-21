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
        if (! Schema::hasTable('community_groups')) {
            Schema::create('community_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // E.g., "St. Mary SCC", "Youth Group Zone A"
                $table->enum('group_type', [
                    'small_christian_community', 'church_group', 'ministry', 'committee',
                ]);
                $table->text('description')->nullable();
                $table->unsignedBigInteger('leader_id')->nullable(); // Member who leads the group
                $table->string('meeting_location')->nullable();
                $table->enum('meeting_frequency', [
                    'weekly', 'bi_weekly', 'monthly', 'quarterly', 'as_needed',
                ])->nullable();
                $table->string('meeting_day')->nullable(); // E.g., "Every Sunday"
                $table->time('meeting_time')->nullable();
                $table->boolean('is_active')->default(true);
                $table->date('established_date')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                // Foreign keys and indexes
                $table->foreign('leader_id')->references('id')->on('members')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

                $table->index('group_type');
                $table->index('is_active');
                $table->index('leader_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_groups');
    }
};
