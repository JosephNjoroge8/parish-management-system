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
        // Only add the most critical indexes to avoid MySQL 64 index limit
        Schema::table('members', function (Blueprint $table) {
            // Only add essential search indexes if they don't exist
            if (! $this->indexExists('members', 'idx_members_name_search')) {
                $table->index(['first_name', 'last_name'], 'idx_members_name_search');
            }
            if (! $this->indexExists('members', 'idx_members_status')) {
                $table->index('membership_status', 'idx_members_status');
            }
            if (! $this->indexExists('members', 'idx_members_church')) {
                $table->index('local_church', 'idx_members_church');
            }
            // Skip other indexes to avoid hitting MySQL limit
        });

        // Add only essential indexes for families table
        Schema::table('families', function (Blueprint $table) {
            if (! $this->indexExists('families', 'idx_families_name')) {
                $table->index('family_name', 'idx_families_name');
            }
            if (! $this->indexExists('families', 'idx_families_section')) {
                $table->index('parish_section', 'idx_families_section');
            }
        });

        // Skip other table indexes for now to avoid MySQL limits
        // These can be added manually if needed based on performance analysis
    }

    private function indexExists($table, $indexName): bool
    {
        try {
            // Check database type and use appropriate query
            $driver = config('database.default');
            $connection = config("database.connections.{$driver}");
            
            if ($connection['driver'] === 'mysql') {
                $indexes = \DB::select("SHOW INDEX FROM {$table}");
                foreach ($indexes as $index) {
                    if ($index->Key_name === $indexName) {
                        return true;
                    }
                }
            } else {
                // SQLite
                $indexes = \DB::select("PRAGMA index_list({$table})");
                foreach ($indexes as $index) {
                    if ($index->name === $indexName) {
                        return true;
                    }
                }
            }

            return false;
        } catch (\Exception $e) {
            // If we can't check, assume it doesn't exist
            return false;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop only the indexes we actually created
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex('idx_members_name_search');
            $table->dropIndex('idx_members_status');
            $table->dropIndex('idx_members_church');
        });

        Schema::table('families', function (Blueprint $table) {
            $table->dropIndex('idx_families_name');
            $table->dropIndex('idx_families_section');
        });
    }
};
