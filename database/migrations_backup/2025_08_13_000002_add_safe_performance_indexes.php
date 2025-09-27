<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add essential performance indexes (compatible with all MySQL versions)
     */
    public function up(): void
    {
        // Add safe indexes to members table
        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                // Check if indexes don't already exist before creating
                if (!$this->indexExists('members', 'idx_members_dob')) {
                    $table->index('date_of_birth', 'idx_members_dob');
                }
                
                if (!$this->indexExists('members', 'idx_members_status')) {
                    $table->index('membership_status', 'idx_members_status');
                }
                
                if (!$this->indexExists('members', 'idx_members_church')) {
                    $table->index('local_church', 'idx_members_church');
                }
                
                if (!$this->indexExists('members', 'idx_members_group')) {
                    $table->index('church_group', 'idx_members_group');
                }
                
                if (!$this->indexExists('members', 'idx_members_updated')) {
                    $table->index('updated_at', 'idx_members_updated');
                }
            });
        }

        // Add safe indexes to sacraments table
        if (Schema::hasTable('sacraments')) {
            Schema::table('sacraments', function (Blueprint $table) {
                if (!$this->indexExists('sacraments', 'idx_sacraments_date')) {
                    $table->index('sacrament_date', 'idx_sacraments_date');
                }
                
                if (!$this->indexExists('sacraments', 'idx_sacraments_type')) {
                    $table->index('sacrament_type', 'idx_sacraments_type');
                }
                
                if (!$this->indexExists('sacraments', 'idx_sacraments_member')) {
                    $table->index('member_id', 'idx_sacraments_member');
                }
            });
        }

        // Add safe indexes to tithes table
        if (Schema::hasTable('tithes')) {
            Schema::table('tithes', function (Blueprint $table) {
                if (!$this->indexExists('tithes', 'idx_tithes_date')) {
                    $table->index('date_given', 'idx_tithes_date');
                }
                
                if (!$this->indexExists('tithes', 'idx_tithes_member')) {
                    $table->index('member_id', 'idx_tithes_member');
                }
                
                if (!$this->indexExists('tithes', 'idx_tithes_type')) {
                    $table->index('tithe_type', 'idx_tithes_type');
                }
            });
        }

        // Add safe indexes to activities table
        if (Schema::hasTable('activities')) {
            Schema::table('activities', function (Blueprint $table) {
                if (!$this->indexExists('activities', 'idx_activities_start_date')) {
                    $table->index('start_date', 'idx_activities_start_date');
                }
                
                if (!$this->indexExists('activities', 'idx_activities_status')) {
                    $table->index('status', 'idx_activities_status');
                }
            });
        }

        // Add safe indexes to families table
        if (Schema::hasTable('families')) {
            Schema::table('families', function (Blueprint $table) {
                if (!$this->indexExists('families', 'idx_families_head')) {
                    $table->index('head_of_family_id', 'idx_families_head');
                }
                
                if (!$this->indexExists('families', 'idx_families_parish')) {
                    $table->index('parish', 'idx_families_parish');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safely drop indexes if they exist
        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_members_dob');
                $this->dropIndexIfExists($table, 'idx_members_status');
                $this->dropIndexIfExists($table, 'idx_members_church');
                $this->dropIndexIfExists($table, 'idx_members_group');
                $this->dropIndexIfExists($table, 'idx_members_updated');
            });
        }

        if (Schema::hasTable('sacraments')) {
            Schema::table('sacraments', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_sacraments_date');
                $this->dropIndexIfExists($table, 'idx_sacraments_type');
                $this->dropIndexIfExists($table, 'idx_sacraments_member');
            });
        }

        if (Schema::hasTable('tithes')) {
            Schema::table('tithes', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_tithes_date');
                $this->dropIndexIfExists($table, 'idx_tithes_member');
                $this->dropIndexIfExists($table, 'idx_tithes_type');
            });
        }

        if (Schema::hasTable('activities')) {
            Schema::table('activities', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_activities_start_date');
                $this->dropIndexIfExists($table, 'idx_activities_status');
            });
        }

        if (Schema::hasTable('families')) {
            Schema::table('families', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_families_head');
                $this->dropIndexIfExists($table, 'idx_families_parish');
            });
        }
    }

    /**
     * Get existing indexes for a table
     */
    private function getTableIndexes(string $tableName): array
    {
        try {
            $indexes = Schema::getConnection()->select("SHOW INDEX FROM `{$tableName}`");
            return collect($indexes)->pluck('Key_name')->unique()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $tableName, string $indexName): bool
    {
        try {
            $result = Schema::getConnection()->select(
                "SELECT COUNT(*) as count FROM information_schema.statistics 
                 WHERE table_schema = DATABASE() 
                 AND table_name = ? 
                 AND index_name = ?",
                [$tableName, $indexName]
            );
            return $result[0]->count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Safely drop an index if it exists
     */
    private function dropIndexIfExists(Blueprint $table, string $indexName): void
    {
        try {
            $table->dropIndex($indexName);
        } catch (\Exception $e) {
            // Index doesn't exist, continue
        }
    }
};
