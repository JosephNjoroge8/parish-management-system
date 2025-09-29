<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConfigureProductionDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:configure-production';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure the database for production environment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Configuring database for production environment...');
        
        // Check current connection
        $connection = config('database.default');
        $this->info("Current database connection: {$connection}");
        
        // If .env specifies MySQL but we're using SQLite, update the configuration
        if ($connection === 'sqlite' && env('DB_CONNECTION') === 'mysql') {
            $this->info('Changing configuration to use MySQL...');
            
            // Update the database.php config directly (runtime only)
            config(['database.default' => 'mysql']);
            
            $this->info('Configuration updated to use MySQL');
        }
        
        // Verify connection to the database
        try {
            DB::connection()->getPdo();
            $this->info('Database connection successful: ' . DB::connection()->getDatabaseName());
        } catch (\Exception $e) {
            $this->error('Could not connect to the database: ' . $e->getMessage());
            return 1;
        }
        
        // Check if required tables exist
        $this->info('Checking database tables...');
        
        $requiredTables = ['users', 'members', 'families', 'tithes', 'activities', 'migrations'];
        $missingTables = [];
        
        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $missingTables[] = $table;
            }
        }
        
        if (count($missingTables) > 0) {
            $this->warn('Missing tables: ' . implode(', ', $missingTables));
            $this->info('Running migrations to create missing tables...');
            
            // Run migrations
            $this->call('migrate', [
                '--force' => true,
            ]);
        } else {
            $this->info('All required tables exist.');
        }
        
        // Update .env file to use MySQL if not already set
        $envContent = file_get_contents(base_path('.env'));
        
        if (strpos($envContent, 'DB_CONNECTION=sqlite') !== false) {
            $this->info('Updating .env file to use MySQL as default...');
            $envContent = str_replace('DB_CONNECTION=sqlite', 'DB_CONNECTION=mysql', $envContent);
            file_put_contents(base_path('.env'), $envContent);
        }
        
        $this->info('Database configuration complete!');
        return 0;
    }
}
