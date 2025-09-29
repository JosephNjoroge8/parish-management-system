<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OptimizeMysqlDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:optimize-mysql';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize MySQL database tables and indexes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = config('database.default');
        
        if ($connection !== 'mysql') {
            $this->info('This command is only for MySQL databases. Current connection: ' . $connection);
            return 0;
        }
        
        $this->info('Optimizing MySQL database...');
        
        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $tableColumn = 'Tables_in_' . config('database.connections.mysql.database');
        
        foreach ($tables as $table) {
            $tableName = $table->$tableColumn;
            $this->info("Optimizing table: {$tableName}");
            
            // Check and convert to InnoDB if needed
            $tableStatus = DB::select("SHOW TABLE STATUS WHERE Name = '{$tableName}'");
            if ($tableStatus[0]->Engine !== 'InnoDB') {
                $this->warn("Converting {$tableName} to InnoDB engine...");
                DB::statement("ALTER TABLE {$tableName} ENGINE = InnoDB");
            }
            
            // Check and update character set if needed
            if ($tableStatus[0]->Collation !== 'utf8mb4_unicode_ci') {
                $this->warn("Converting {$tableName} to utf8mb4 character set...");
                DB::statement("ALTER TABLE {$tableName} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
            
            // Optimize table
            DB::statement("OPTIMIZE TABLE {$tableName}");
            
            // Add common indexes if they don't exist
            $this->addCommonIndexes($tableName);
        }
        
        $this->info('Database optimization completed successfully!');
        return 0;
    }
    
    /**
     * Add common indexes to tables if they don't exist
     */
    protected function addCommonIndexes($tableName)
    {
        // Define common columns that should be indexed
        $indexableColumns = [
            'users' => ['email', 'name', 'created_at'],
            'members' => ['email', 'first_name', 'last_name', 'family_id', 'created_at'],
            'families' => ['family_name', 'head_of_family_id', 'created_at'],
            'tithes' => ['member_id', 'date', 'amount', 'created_at'],
            'activities' => ['title', 'start_date', 'created_at'],
            'activity_participants' => ['activity_id', 'member_id'],
            'baptism_records' => ['member_id', 'baptism_date'],
            'marriage_records' => ['groom_id', 'bride_id', 'marriage_date'],
            'sacraments' => ['member_id', 'sacrament_date', 'sacrament_type']
        ];
        
        // Skip if table isn't in our index list
        if (!isset($indexableColumns[$tableName])) {
            return;
        }
        
        // Get existing indexes
        $indexes = [];
        $indexResults = DB::select("SHOW INDEX FROM {$tableName}");
        foreach ($indexResults as $index) {
            $indexes[] = $index->Column_name;
        }
        
        // Add missing indexes
        foreach ($indexableColumns[$tableName] as $column) {
            // Check if column exists
            if (Schema::hasColumn($tableName, $column) && !in_array($column, $indexes)) {
                $this->line("  - Adding index for {$column}");
                try {
                    DB::statement("ALTER TABLE {$tableName} ADD INDEX idx_{$tableName}_{$column} ({$column})");
                } catch (\Exception $e) {
                    $this->warn("  - Could not add index for {$column}: " . $e->getMessage());
                }
            }
        }
        
        // Add composite indexes for common query patterns
        if ($tableName === 'members' && Schema::hasColumn($tableName, 'first_name') && Schema::hasColumn($tableName, 'last_name')) {
            try {
                DB::statement("ALTER TABLE {$tableName} ADD INDEX idx_members_name (first_name, last_name)");
            } catch (\Exception $e) {
                // Index might already exist
            }
        }
        
        if ($tableName === 'tithes' && Schema::hasColumn($tableName, 'date') && Schema::hasColumn($tableName, 'member_id')) {
            try {
                DB::statement("ALTER TABLE {$tableName} ADD INDEX idx_tithes_date_member (date, member_id)");
            } catch (\Exception $e) {
                // Index might already exist
            }
        }
    }
}
