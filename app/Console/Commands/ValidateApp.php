<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class ValidateApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'validate:app';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validates the application for common deployment issues';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting application validation...');
        
        // Create results array to track validation status
        $results = [
            'passing' => [],
            'warnings' => [],
            'failures' => [],
        ];
        
        // Check 1: Database connection
        $this->info('Checking database connection...');
        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();
            $this->info('✅ Connected successfully to database: ' . $dbName);
            $results['passing'][] = 'Database connection';
            
            // Check required tables
            $requiredTables = ['users', 'members', 'families', 'tithes', 'sacraments', 'marriage_records', 'baptism_records', 'activities'];
            $missingTables = [];
            
            foreach ($requiredTables as $table) {
                if (!Schema::hasTable($table)) {
                    $missingTables[] = $table;
                }
            }
            
            if (count($missingTables) > 0) {
                $this->warn('⚠️ Missing tables: ' . implode(', ', $missingTables));
                $results['warnings'][] = 'Missing database tables: ' . implode(', ', $missingTables);
            } else {
                $this->info('✅ All required database tables are present');
                $results['passing'][] = 'Database tables validation';
            }
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: ' . $e->getMessage());
            $results['failures'][] = 'Database connection: ' . $e->getMessage();
        }
        
        // Check 2: Storage permissions
        $this->info('Checking storage directory permissions...');
        $storagePaths = [
            storage_path('app'),
            storage_path('framework'),
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];
        
        $failedPaths = [];
        foreach ($storagePaths as $path) {
            if (!file_exists($path)) {
                try {
                    File::makeDirectory($path, 0755, true);
                    $this->info("✅ Created directory: $path");
                } catch (\Exception $e) {
                    $failedPaths[] = "$path (doesn't exist and can't be created)";
                }
            } elseif (!is_writable($path)) {
                $failedPaths[] = "$path (not writable)";
            }
        }
        
        if (count($failedPaths) > 0) {
            $this->error('❌ Storage permission issues with: ' . implode(', ', $failedPaths));
            $results['failures'][] = 'Storage permissions: ' . implode(', ', $failedPaths);
        } else {
            $this->info('✅ Storage directory permissions are correct');
            $results['passing'][] = 'Storage permissions';
        }
        
        // Check 3: Bootstrap cache permissions
        $this->info('Checking bootstrap/cache directory permissions...');
        $bootstrapCache = base_path('bootstrap/cache');
        
        if (!file_exists($bootstrapCache)) {
            try {
                File::makeDirectory($bootstrapCache, 0755, true);
                $this->info("✅ Created directory: $bootstrapCache");
            } catch (\Exception $e) {
                $this->error("❌ Bootstrap/cache doesn't exist and can't be created");
                $results['failures'][] = "Bootstrap/cache doesn't exist and can't be created";
            }
        } elseif (!is_writable($bootstrapCache)) {
            $this->error('❌ Bootstrap/cache directory is not writable');
            $results['failures'][] = 'Bootstrap/cache directory is not writable';
        } else {
            $this->info('✅ Bootstrap/cache directory permissions are correct');
            $results['passing'][] = 'Bootstrap cache permissions';
        }
        
        // Check 4: Environment configuration
        $this->info('Checking environment configuration...');
        $envFile = base_path('.env');
        
        if (!file_exists($envFile)) {
            $this->error('❌ .env file is missing');
            $results['failures'][] = '.env file is missing';
        } else {
            $this->info('✅ .env file exists');
            $results['passing'][] = '.env file exists';
            
            // Check required env variables
            $requiredEnvVars = [
                'APP_KEY', 'APP_URL', 'DB_CONNECTION', 
                'DB_HOST', 'DB_PORT', 'DB_DATABASE', 
                'DB_USERNAME', 'DB_PASSWORD'
            ];
            
            $missingEnvVars = [];
            foreach ($requiredEnvVars as $var) {
                if (empty(env($var))) {
                    $missingEnvVars[] = $var;
                }
            }
            
            if (count($missingEnvVars) > 0) {
                $this->warn('⚠️ Missing or empty environment variables: ' . implode(', ', $missingEnvVars));
                $results['warnings'][] = 'Missing environment variables: ' . implode(', ', $missingEnvVars);
            } else {
                $this->info('✅ All required environment variables are set');
                $results['passing'][] = 'Environment variables';
            }
            
            // Check APP_DEBUG
            if (env('APP_DEBUG') === true && env('APP_ENV') === 'production') {
                $this->warn('⚠️ APP_DEBUG is set to true in production environment');
                $results['warnings'][] = 'APP_DEBUG is set to true in production';
            } else {
                $this->info('✅ APP_DEBUG setting is appropriate for environment');
                $results['passing'][] = 'APP_DEBUG configuration';
            }
        }
        
        // Check 5: Cache configuration
        $this->info('Checking cache configuration...');
        try {
            $cacheDriver = config('cache.default');
            $this->info('✅ Cache driver: ' . $cacheDriver);
            $results['passing'][] = 'Cache configuration';
        } catch (\Exception $e) {
            $this->error('❌ Cache configuration issue: ' . $e->getMessage());
            $results['failures'][] = 'Cache configuration: ' . $e->getMessage();
        }
        
        // Check 6: Required PHP extensions
        $this->info('Checking PHP extensions...');
        $requiredExtensions = [
            'BCMath', 'Ctype', 'Fileinfo', 'JSON', 'Mbstring', 
            'OpenSSL', 'PDO', 'SQLite3', 'Tokenizer', 'XML', 'curl', 'zip'
        ];
        
        $missingExtensions = [];
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded(strtolower($extension))) {
                $missingExtensions[] = $extension;
            }
        }
        
        if (count($missingExtensions) > 0) {
            $this->warn('⚠️ Missing PHP extensions: ' . implode(', ', $missingExtensions));
            $results['warnings'][] = 'Missing PHP extensions: ' . implode(', ', $missingExtensions);
        } else {
            $this->info('✅ All required PHP extensions are installed');
            $results['passing'][] = 'PHP extensions';
        }
        
        // Check 7: Public directory symlink (for storage)
        $this->info('Checking storage symlink...');
        if (!file_exists(public_path('storage'))) {
            try {
                Artisan::call('storage:link');
                $this->info('✅ Storage symlink created successfully');
                $results['passing'][] = 'Storage symlink';
            } catch (\Exception $e) {
                $this->error('❌ Unable to create storage symlink: ' . $e->getMessage());
                $results['failures'][] = 'Storage symlink: ' . $e->getMessage();
            }
        } else {
            $this->info('✅ Storage symlink exists');
            $results['passing'][] = 'Storage symlink';
        }
        
        // Check 8: SQLite-specific optimizations if using SQLite
        if (config('database.default') === 'sqlite') {
            $this->info('Checking SQLite optimizations...');
            $sqlitePath = config('database.connections.sqlite.database');
            
            if (!file_exists($sqlitePath)) {
                $this->error('❌ SQLite database file not found: ' . $sqlitePath);
                $results['failures'][] = 'SQLite database file not found';
            } else {
                // Check SQLite version
                try {
                    $version = DB::select('SELECT sqlite_version() as version')[0]->version;
                    $this->info('✅ SQLite version: ' . $version);
                    $results['passing'][] = 'SQLite version: ' . $version;
                    
                    // Check if using WAL mode
                    $journalMode = DB::select('PRAGMA journal_mode;')[0]->journal_mode;
                    if (strtolower($journalMode) !== 'wal') {
                        $this->warn('⚠️ SQLite is not using WAL journal mode for better concurrency');
                        $results['warnings'][] = 'SQLite not using WAL journal mode';
                    } else {
                        $this->info('✅ SQLite using WAL journal mode');
                        $results['passing'][] = 'SQLite journal mode';
                    }
                } catch (\Exception $e) {
                    $this->error('❌ Error checking SQLite configuration: ' . $e->getMessage());
                    $results['warnings'][] = 'SQLite configuration: ' . $e->getMessage();
                }
            }
        }
        
        // Check 9: Required Models
        $this->info('Checking required model files...');
        $requiredModels = [
            'app/Models/User.php',
            'app/Models/Member.php',
            'app/Models/Family.php',
            'app/Models/Tithe.php',
            'app/Models/Sacrament.php',
            'app/Models/BaptismRecord.php',
            'app/Models/MarriageRecord.php'
        ];
        
        $missingModels = [];
        foreach ($requiredModels as $model) {
            if (!file_exists(base_path($model))) {
                $missingModels[] = $model;
            }
        }
        
        if (count($missingModels) > 0) {
            $this->warn('⚠️ Missing model files: ' . implode(', ', $missingModels));
            $results['warnings'][] = 'Missing model files: ' . implode(', ', $missingModels);
        } else {
            $this->info('✅ All required model files exist');
            $results['passing'][] = 'Model files';
        }
        
        // Check 10: Public directory assets
        $this->info('Checking public directory assets...');
        $requiredPublicFiles = [
            'public/index.php',
            'public/favicon.ico',
            'public/.htaccess',
        ];
        
        $missingPublicFiles = [];
        foreach ($requiredPublicFiles as $file) {
            if (!file_exists(base_path($file))) {
                $missingPublicFiles[] = $file;
            }
        }
        
        if (count($missingPublicFiles) > 0) {
            $this->warn('⚠️ Missing public files: ' . implode(', ', $missingPublicFiles));
            $results['warnings'][] = 'Missing public files: ' . implode(', ', $missingPublicFiles);
        } else {
            $this->info('✅ All required public files exist');
            $results['passing'][] = 'Public files';
        }
        
        // Check 11: Verify Controllers
        $this->info('Checking required controllers...');
        $requiredControllers = [
            'app/Http/Controllers/MemberController.php',
            'app/Http/Controllers/FamilyController.php',
            'app/Http/Controllers/TitheController.php'
        ];
        
        $missingControllers = [];
        foreach ($requiredControllers as $controller) {
            if (!file_exists(base_path($controller))) {
                $missingControllers[] = $controller;
            }
        }
        
        if (count($missingControllers) > 0) {
            $this->warn('⚠️ Missing controller files: ' . implode(', ', $missingControllers));
            $results['warnings'][] = 'Missing controller files: ' . implode(', ', $missingControllers);
        } else {
            $this->info('✅ All required controller files exist');
            $results['passing'][] = 'Controller files';
        }
        
        // Check 12: Database Query Performance
        $this->info('Testing database query performance...');
        try {
            $startTime = microtime(true);
            // Simple query to test database performance
            if (Schema::hasTable('members')) {
                $count = DB::table('members')->count();
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                $this->info("✅ Database query completed in {$duration}ms (Members: {$count})");
                
                if ($duration > 1000) {
                    $this->warn("⚠️ Database query time is high: {$duration}ms");
                    $results['warnings'][] = "Slow database query: {$duration}ms";
                } else {
                    $results['passing'][] = "Database query performance: {$duration}ms";
                }
            } else {
                $this->warn("⚠️ Cannot test database performance - members table doesn't exist");
                $results['warnings'][] = "Cannot test database performance";
            }
        } catch (\Exception $e) {
            $this->error('❌ Database query test failed: ' . $e->getMessage());
            $results['failures'][] = 'Database performance test: ' . $e->getMessage();
        }
        
        // Summary
        $this->newLine();
        $this->info('=======================================');
        $this->info('       APPLICATION VALIDATION REPORT       ');
        $this->info('=======================================');
        
        $this->info('✅ PASSING: ' . count($results['passing']) . ' checks');
        foreach ($results['passing'] as $pass) {
            $this->line('  - ' . $pass);
        }
        
        $this->newLine();
        $this->warn('⚠️ WARNINGS: ' . count($results['warnings']) . ' issues');
        foreach ($results['warnings'] as $warning) {
            $this->line('  - ' . $warning);
        }
        
        $this->newLine();
        $this->error('❌ FAILURES: ' . count($results['failures']) . ' critical issues');
        foreach ($results['failures'] as $failure) {
            $this->line('  - ' . $failure);
        }
        
        $this->newLine();
        if (count($results['failures']) > 0) {
            $this->error('❌ VALIDATION FAILED: There are ' . count($results['failures']) . ' critical issues that need to be fixed!');
            return Command::FAILURE;
        } elseif (count($results['warnings']) > 0) {
            $this->warn('⚠️ VALIDATION PASSED WITH WARNINGS: ' . count($results['warnings']) . ' issues should be addressed.');
            return Command::SUCCESS;
        } else {
            $this->info('✅ VALIDATION SUCCESSFUL: All checks passed!');
            return Command::SUCCESS;
        }
    }
}