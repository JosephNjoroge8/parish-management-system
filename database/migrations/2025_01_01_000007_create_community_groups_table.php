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
            $table->string('group_name', 100)->unique()->index();
            $table->text('description')->nullable();
            $table->string('group_leader', 100)->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('contact_email', 100)->nullable();
            $table->date('meeting_day')->nullable(); // Use date for specific meeting schedule
            $table->time('meeting_time')->nullable();
            $table->string('meeting_location', 100)->nullable();
            $table->enum('group_status', ['active', 'inactive', 'suspended'])->default('active')->index();
            $table->integer('max_members')->nullable();
            $table->text('requirements')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['group_status', 'group_name']);
            $table->index(['group_leader']);
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
