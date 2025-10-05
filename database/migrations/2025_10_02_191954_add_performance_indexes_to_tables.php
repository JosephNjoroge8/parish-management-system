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
        // Members table indexes for better performance
        Schema::table('members', function (Blueprint $table) {
            // Search performance indexes (add only if they don't exist)
            if (! $this->indexExists('members', 'idx_members_name_search')) {
                $table->index(['first_name', 'last_name'], 'idx_members_name_search');
            }
            if (! $this->indexExists('members', 'idx_members_status')) {
                $table->index('membership_status', 'idx_members_status');
            }
            if (! $this->indexExists('members', 'idx_members_church')) {
                $table->index('local_church', 'idx_members_church');
            }
            if (! $this->indexExists('members', 'idx_members_group')) {
                $table->index('church_group', 'idx_members_group');
            }
            if (! $this->indexExists('members', 'idx_members_created')) {
                $table->index('created_at', 'idx_members_created');
            }
            if (! $this->indexExists('members', 'idx_members_dob')) {
                $table->index('date_of_birth', 'idx_members_dob');
            }
            if (! $this->indexExists('members', 'idx_members_email')) {
                $table->index('email', 'idx_members_email');
            }
            if (! $this->indexExists('members', 'idx_members_phone')) {
                $table->index('phone', 'idx_members_phone');
            }

            // Composite indexes for common filter combinations
            if (! $this->indexExists('members', 'idx_members_status_church')) {
                $table->index(['membership_status', 'local_church'], 'idx_members_status_church');
            }
            if (! $this->indexExists('members', 'idx_members_gender_status')) {
                $table->index(['gender', 'membership_status'], 'idx_members_gender_status');
            }
        });

        // Families table indexes
        Schema::table('families', function (Blueprint $table) {
            if (! $this->indexExists('families', 'idx_families_name')) {
                $table->index('family_name', 'idx_families_name');
            }
            if (! $this->indexExists('families', 'idx_families_section')) {
                $table->index('parish_section', 'idx_families_section');
            }
            if (! $this->indexExists('families', 'idx_families_deanery')) {
                $table->index('deanery', 'idx_families_deanery');
            }
            if (! $this->indexExists('families', 'idx_families_parish')) {
                $table->index('parish', 'idx_families_parish');
            }
            if (! $this->indexExists('families', 'idx_families_created')) {
                $table->index('created_at', 'idx_families_created');
            }
            if (! $this->indexExists('families', 'idx_families_head')) {
                $table->index('head_of_family_id', 'idx_families_head');
            }
        });

        // Add indexes for other tables similarly...
        $this->addTableIndexes('tithes', [
            'member_id' => 'idx_tithes_member',
            'date_given' => 'idx_tithes_date',
            'offering_type' => 'idx_tithes_type',
            'payment_method' => 'idx_tithes_method',
        ]);

        $this->addTableIndexes('sacraments', [
            'member_id' => 'idx_sacraments_member',
            'sacrament_type' => 'idx_sacraments_type',
            'date_administered' => 'idx_sacraments_date',
        ]);

        $this->addTableIndexes('activities', [
            'start_date' => 'idx_activities_start',
            'end_date' => 'idx_activities_end',
            'activity_type' => 'idx_activities_type',
            'created_at' => 'idx_activities_created',
        ]);
    }

    private function indexExists($table, $indexName): bool
    {
        try {
            $indexes = \DB::select("PRAGMA index_list({$table})");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function addTableIndexes($table, $indexes): void
    {
        Schema::table($table, function (Blueprint $table) use ($indexes) {
            foreach ($indexes as $column => $indexName) {
                if (! $this->indexExists($table->getTable(), $indexName)) {
                    $table->index($column, $indexName);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all indexes
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex('idx_members_name_search');
            $table->dropIndex('idx_members_status');
            $table->dropIndex('idx_members_church');
            $table->dropIndex('idx_members_group');
            $table->dropIndex('idx_members_gender');
            $table->dropIndex('idx_members_created');
            $table->dropIndex('idx_members_dob');
            $table->dropIndex('idx_members_email');
            $table->dropIndex('idx_members_phone');
            $table->dropIndex('idx_members_status_church');
            $table->dropIndex('idx_members_gender_status');
        });

        Schema::table('families', function (Blueprint $table) {
            $table->dropIndex('idx_families_name');
            $table->dropIndex('idx_families_section');
            $table->dropIndex('idx_families_deanery');
            $table->dropIndex('idx_families_parish');
            $table->dropIndex('idx_families_created');
            $table->dropIndex('idx_families_head');
        });

        Schema::table('tithes', function (Blueprint $table) {
            $table->dropIndex('idx_tithes_member');
            $table->dropIndex('idx_tithes_date');
            $table->dropIndex('idx_tithes_type');
            $table->dropIndex('idx_tithes_method');
            $table->dropIndex('idx_tithes_date_member');
            $table->dropIndex('idx_tithes_type_date');
        });

        Schema::table('sacraments', function (Blueprint $table) {
            $table->dropIndex('idx_sacraments_member');
            $table->dropIndex('idx_sacraments_type');
            $table->dropIndex('idx_sacraments_date');
            $table->dropIndex('idx_sacraments_member_type');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('idx_activities_start');
            $table->dropIndex('idx_activities_end');
            $table->dropIndex('idx_activities_type');
            $table->dropIndex('idx_activities_created');
        });

        Schema::table('activity_participants', function (Blueprint $table) {
            $table->dropIndex('idx_participants_activity');
            $table->dropIndex('idx_participants_member');
            $table->dropIndex('idx_participants_unique');
        });

        Schema::table('baptism_records', function (Blueprint $table) {
            $table->dropIndex('idx_baptism_member');
            $table->dropIndex('idx_baptism_date');
            $table->dropIndex('idx_baptism_created');
        });

        Schema::table('marriage_records', function (Blueprint $table) {
            $table->dropIndex('idx_marriage_groom');
            $table->dropIndex('idx_marriage_bride');
            $table->dropIndex('idx_marriage_date');
            $table->dropIndex('idx_marriage_created');
        });
    }
};
