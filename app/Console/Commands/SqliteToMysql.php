<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SqliteToMysql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:sqlite-to-mysql';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer data from SQLite to MySQL database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting data transfer from SQLite to MySQL...');
        
        // Get the SQLite database path
        $sqlitePath = env('DB_DATABASE_SQLITE', database_path('database.sqlite'));
        
        // Check if the SQLite file exists
        if (!file_exists($sqlitePath)) {
            $this->error("SQLite database not found at: {$sqlitePath}");
            return 1;
        }
        
        // Connect to SQLite database
        config([
            'database.connections.sqlite_source' => [
                'driver' => 'sqlite',
                'database' => $sqlitePath,
                'prefix' => '',
                'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            ],
        ]);
        
        // Get all tables from SQLite
        $tables = DB::connection('sqlite_source')
            ->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';");
        
        $this->info('Found ' . count($tables) . ' tables in SQLite database.');
        
        $bar = $this->output->createProgressBar(count($tables));
        $bar->start();
        
        $totalTransferred = 0;
        
        foreach ($tables as $table) {
            $tableName = $table->name;
            
            // Skip migrations table
            if ($tableName === 'migrations') {
                $bar->advance();
                continue;
            }
            
            // Get all data from the table
            $records = DB::connection('sqlite_source')->table($tableName)->get();
            
            $this->transferTableData($tableName, $records);
            $totalTransferred += count($records);
            
            $bar->advance();
        }
        
        $bar->finish();
        
        $this->newLine(2);
        $this->info("Data transfer completed: {$totalTransferred} total records transferred");
        
        return 0;
    }
    
    /**
     * Transfer data from SQLite table to MySQL table
     */
    protected function transferTableData(string $tableName, $records): void
    {
        if (count($records) === 0) {
            return;
        }
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Disable foreign key checks temporarily
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Insert in batches of 100
            $chunks = array_chunk($records->toArray(), 100);
            
            foreach ($chunks as $chunk) {
                DB::table($tableName)->insert($chunk);
            }
            
            // Enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            // Commit transaction
            DB::commit();
            
            $this->line(" - Transferred " . count($records) . " records to {$tableName}");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->warn(" - Error transferring data to {$tableName}: " . $e->getMessage());
        }
    }
}