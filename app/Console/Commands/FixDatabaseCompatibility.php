<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixDatabaseCompatibility extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:fix-compatibility';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix database compatibility issues for production deployment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing database compatibility issues...');
        
        // Clear all caches to ensure fresh start
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        
        // Run migrations to ensure database is up to date
        $this->call('migrate', ['--force' => true]);
        
        // Configure database connection based on environment
        $this->call('db:configure-production');
        
        // If MySQL, optimize the database
        if (config('database.default') === 'mysql') {
            $this->call('db:optimize-mysql');
        }
        
        // Cache configuration for better performance
        $this->call('config:cache');
        
        $this->info('Database compatibility fixes completed successfully!');
        
        return 0;
    }
}
