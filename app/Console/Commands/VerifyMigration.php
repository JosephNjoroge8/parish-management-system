<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerifyMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:verify-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify database migration and data integrity';

    /**
     * Key tables to verify with their primary identifier columns
     */
    protected $keyTables = [
        'users' => ['table' => 'users', 'column' => 'email', 'name' => 'Users'],
        'members' => ['table' => 'members', 'column' => 'id', 'name' => 'Members'],
        'activities' => ['table' => 'activities', 'column' => 'id', 'name' => 'Activities'],
        'tithes' => ['table' => 'tithes', 'column' => 'id', 'name' => 'Tithes'],
        'families' => ['table' => 'families', 'column' => 'id', 'name' => 'Families'],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verifying database migration...');
        
        // Check if tables exist
        $this->verifyTablesExist();
        
        // Check record counts
        $this->verifyRecordCounts();
        
        // Verify data integrity
        $this->verifyDataIntegrity();
        
        // Check for MySQL-specific issues
        $this->checkForMySqlIssues();
        
        $this->info('Migration verification completed successfully!');
        
        return 0;
    }
    
    /**
     * Verify that all expected tables exist in the database
     */
    protected function verifyTablesExist(): void
    {
        $this->info('Checking database tables...');
        
        $tables = collect(array_keys($this->keyTables));
        $missingTables = $tables->filter(function($table) {
            return !Schema::hasTable($table);
        });
        
        if ($missingTables->count() > 0) {
            $this->error('Missing tables: ' . $missingTables->implode(', '));
        } else {
            $this->info('✓ All expected tables exist');
        }
    }
    
    /**
     * Verify record counts in key tables
     */
    protected function verifyRecordCounts(): void
    {
        $this->info('Checking record counts...');
        
        $table = $this->components->twoColumnDetail('<fg=gray>Table</>', '<fg=gray>Records</>');
        
        foreach ($this->keyTables as $key => $tableInfo) {
            $count = DB::table($tableInfo['table'])->count();
            $table->addRow($tableInfo['name'], $count);
        }
        
        $this->newLine();
        $table->render();
        $this->newLine();
    }
    
    /**
     * Verify data integrity (relationships, etc.)
     */
    protected function verifyDataIntegrity(): void
    {
        $this->info('Checking data integrity...');
        
        // Check member-family relationships
        if (Schema::hasColumn('members', 'family_id') && Schema::hasTable('families')) {
            $orphanedMembers = DB::table('members')
                ->whereNotNull('family_id')
                ->whereRaw('family_id NOT IN (SELECT id FROM families)')
                ->count();
            
            if ($orphanedMembers > 0) {
                $this->warn("⚠ Found {$orphanedMembers} members with invalid family references");
            } else {
                $this->info('✓ All family relationships are valid');
            }
        }
        
        // Check tithe-member relationships
        if (Schema::hasColumn('tithes', 'member_id') && Schema::hasTable('members')) {
            $orphanedTithes = DB::table('tithes')
                ->whereNotNull('member_id')
                ->whereRaw('member_id NOT IN (SELECT id FROM members)')
                ->count();
            
            if ($orphanedTithes > 0) {
                $this->warn("⚠ Found {$orphanedTithes} tithes with invalid member references");
            } else {
                $this->info('✓ All tithe relationships are valid');
            }
        }
    }
    
    /**
     * Check for MySQL-specific issues
     */
    protected function checkForMySqlIssues(): void
    {
        $this->info('Checking for MySQL-specific issues...');
        
        // Check if any tables use MyISAM engine (should be InnoDB)
        $myisamTables = DB::select("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND engine = 'MyISAM'
        ");
        
        if (count($myisamTables) > 0) {
            $tableList = collect($myisamTables)->pluck('table_name')->implode(', ');
            $this->warn("⚠ The following tables use MyISAM engine (should be InnoDB): {$tableList}");
        } else {
            $this->info('✓ All tables use InnoDB engine');
        }
        
        // Check character set and collation
        $nonUtf8Tables = DB::select("
            SELECT table_name, table_collation
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_collation NOT LIKE 'utf8mb4%'
        ");
        
        if (count($nonUtf8Tables) > 0) {
            $tableList = collect($nonUtf8Tables)->map(function($table) {
                return $table->table_name . ' (' . $table->table_collation . ')';
            })->implode(', ');
            $this->warn("⚠ The following tables don't use utf8mb4 collation: {$tableList}");
        } else {
            $this->info('✓ All tables use utf8mb4 character set and collation');
        }
    }
}