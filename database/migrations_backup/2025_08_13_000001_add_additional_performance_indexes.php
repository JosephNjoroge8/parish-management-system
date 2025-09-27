<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Add additional performance indexes for common query patterns
     */
    public function up(): void
    {
        // Additional composite indexes for complex queries
        Schema::table('members', function (Blueprint $table) {
            // Date-based indexes for age calculations
            $table->index('date_of_birth', 'idx_members_dob');
            $table->index(['date_of_birth', 'membership_status'], 'idx_members_dob_status');
            
            // Name search indexes (without full-text for compatibility)
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->index(['first_name', 'middle_name', 'last_name'], 'idx_members_full_name');
                // Simple full-text index without ngram parser for broader compatibility
                try {
                    DB::statement('ALTER TABLE members ADD FULLTEXT(first_name, middle_name, last_name)');
                } catch (\Exception $e) {
                    // Skip if full-text is not supported
                    Log::info('Full-text search not available, using regular indexes');
                }
            }
            
            // Activity tracking indexes
            $table->index('updated_at', 'idx_members_updated');
            $table->index(['created_at', 'membership_status'], 'idx_members_created_status');
        });

        Schema::table('sacraments', function (Blueprint $table) {
            // Sacrament query optimization
            $table->index(['sacrament_type', 'sacrament_date'], 'idx_sacraments_type_date');
            $table->index(['member_id', 'sacrament_type'], 'idx_sacraments_member_type');
            $table->index('sacrament_date', 'idx_sacraments_date');
        });

        Schema::table('tithes', function (Blueprint $table) {
            // Financial reporting optimization
            $table->index(['date_given', 'amount'], 'idx_tithes_date_amount');
            $table->index(['member_id', 'date_given'], 'idx_tithes_member_date');
            $table->index(['tithe_type', 'date_given'], 'idx_tithes_type_date');
        });

        Schema::table('activities', function (Blueprint $table) {
            // Activity queries optimization
            $table->index(['status', 'start_date'], 'idx_activities_status_date');
            $table->index('start_date', 'idx_activities_start_date');
        });

        Schema::table('families', function (Blueprint $table) {
            // Family relationship optimization
            $table->index('head_of_family_id', 'idx_families_head');
            $table->index(['parish', 'created_at'], 'idx_families_parish_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex('idx_members_dob');
            $table->dropIndex('idx_members_dob_status');
            $table->dropIndex('idx_members_full_name');
            $table->dropIndex('idx_members_updated');
            $table->dropIndex('idx_members_created_status');
            
            // Drop full-text index if it exists
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                try {
                    DB::statement('ALTER TABLE members DROP INDEX first_name');
                } catch (\Exception $e) {
                    // Index might not exist, continue
                }
            }
        });

        Schema::table('sacraments', function (Blueprint $table) {
            $table->dropIndex('idx_sacraments_type_date');
            $table->dropIndex('idx_sacraments_member_type');
            $table->dropIndex('idx_sacraments_date');
        });

        Schema::table('tithes', function (Blueprint $table) {
            $table->dropIndex('idx_tithes_date_amount');
            $table->dropIndex('idx_tithes_member_date');
            $table->dropIndex('idx_tithes_type_date');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('idx_activities_status_date');
            $table->dropIndex('idx_activities_start_date');
        });

        Schema::table('families', function (Blueprint $table) {
            $table->dropIndex('idx_families_head');
            $table->dropIndex('idx_families_parish_created');
        });
    }
};
