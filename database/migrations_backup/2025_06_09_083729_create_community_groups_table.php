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
        Schema::create('community_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('group_type', [
                'choir',
                'youth',
                'women',
                'men',
                'children',
                'ministry',
                'committee',
                'prayer_group',
                'bible_study',
                'other'
            ]);
            $table->foreignId('leader_id')->nullable()->constrained('members')->onDelete('set null');
            $table->enum('meeting_day', [
                'monday',
                'tuesday', 
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday'
            ])->nullable();
            $table->time('meeting_time')->nullable();
            $table->string('meeting_location')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index(['group_type']);
            $table->index(['status']);
            $table->index(['leader_id']);
            $table->index(['name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_groups');
    }
};
