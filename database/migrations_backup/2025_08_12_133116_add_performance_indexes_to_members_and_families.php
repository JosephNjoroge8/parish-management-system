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
        // Add indexes to members table for better query performance
        Schema::table('members', function (Blueprint $table) {
            // Composite indexes for common queries
            $table->index(['membership_status', 'created_at'], 'idx_members_status_created');
            $table->index(['local_church', 'membership_status'], 'idx_members_church_status');
            $table->index(['church_group', 'membership_status'], 'idx_members_group_status');
            $table->index(['gender', 'membership_status'], 'idx_members_gender_status');
            
            // Individual indexes for search fields
            $table->index('phone', 'idx_members_phone');
            $table->index('email', 'idx_members_email');
            $table->index('id_number', 'idx_members_id_number');
            $table->index('family_id', 'idx_members_family_id');
            
            // Full-text search indexes for names (if supported)
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->index(['first_name', 'last_name'], 'idx_members_names');
            }
        });

        // Add indexes to families table for better query performance
        Schema::table('families', function (Blueprint $table) {
            $table->index(['parish', 'deanery'], 'idx_families_parish_deanery');
            $table->index('parish_section', 'idx_families_section');
            $table->index('created_by', 'idx_families_created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Drop the indexes
            $table->dropIndex('idx_members_status_created');
            $table->dropIndex('idx_members_church_status');
            $table->dropIndex('idx_members_group_status');
            $table->dropIndex('idx_members_gender_status');
            $table->dropIndex('idx_members_phone');
            $table->dropIndex('idx_members_email');
            $table->dropIndex('idx_members_id_number');
            $table->dropIndex('idx_members_family_id');
            
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->dropIndex('idx_members_names');
            }
        });

        Schema::table('families', function (Blueprint $table) {
            $table->dropIndex('idx_families_parish_deanery');
            $table->dropIndex('idx_families_section');
            $table->dropIndex('idx_families_created_by');
        });
    }
};
