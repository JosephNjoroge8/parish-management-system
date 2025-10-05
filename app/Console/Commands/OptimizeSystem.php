<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OptimizeSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:optimize {--skip-indexes : Skip database index creation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize the Parish Management System for better performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Parish Management System Optimization...');

        // Clear all caches first
        $this->clearCaches();

        // Add database indexes if not skipped
        if (! $this->option('skip-indexes')) {
            $this->addDatabaseIndexes();
        }

        // Optimize Laravel
        $this->optimizeLaravel();

        // Clean up old data
        $this->cleanupOldData();

        // Verify optimization
        $this->verifyOptimization();

        $this->info('✅ System optimization completed successfully!');

        return 0;
    }

    private function clearCaches(): void
    {
        $this->info('🧹 Clearing caches...');

        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            $this->line('   ✓ All caches cleared');
        } catch (\Exception $e) {
            $this->error('   ✗ Failed to clear caches: '.$e->getMessage());
        }
    }

    private function addDatabaseIndexes(): void
    {
        $this->info('📊 Adding database indexes for performance...');

        $indexes = [
            'members' => [
                'membership_status' => 'idx_members_status_opt',
                'local_church' => 'idx_members_church_opt',
                'church_group' => 'idx_members_group_opt',
                'created_at' => 'idx_members_created_opt',
                'date_of_birth' => 'idx_members_dob_opt',
                'email' => 'idx_members_email_opt',
            ],
            'families' => [
                'family_name' => 'idx_families_name_opt',
                'parish_section' => 'idx_families_section_opt',
                'created_at' => 'idx_families_created_opt',
            ],
            'tithes' => [
                'member_id' => 'idx_tithes_member_opt',
                'date_given' => 'idx_tithes_date_opt',
                'offering_type' => 'idx_tithes_type_opt',
            ],
            'sacraments' => [
                'member_id' => 'idx_sacraments_member_opt',
                'sacrament_type' => 'idx_sacraments_type_opt',
                'date_administered' => 'idx_sacraments_date_opt',
            ],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            if (Schema::hasTable($table)) {
                foreach ($tableIndexes as $column => $indexName) {
                    if (Schema::hasColumn($table, $column) && ! $this->indexExists($table, $indexName)) {
                        try {
                            DB::statement("CREATE INDEX {$indexName} ON {$table} ({$column})");
                            $this->line("   ✓ Added index {$indexName} to {$table}.{$column}");
                        } catch (\Exception $e) {
                            $this->line("   ⚠ Skipped {$indexName}: ".$e->getMessage());
                        }
                    }
                }
            }
        }
    }

    private function indexExists($table, $indexName): bool
    {
        try {
            if (DB::getDriverName() === 'sqlite') {
                $indexes = DB::select("PRAGMA index_list({$table})");
                foreach ($indexes as $index) {
                    if ($index->name === $indexName) {
                        return true;
                    }
                }
            } else {
                // MySQL
                $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);

                return count($result) > 0;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function optimizeLaravel(): void
    {
        $this->info('⚡ Optimizing Laravel...');

        try {
            Artisan::call('config:cache');
            $this->line('   ✓ Configuration cached');

            Artisan::call('route:cache');
            $this->line('   ✓ Routes cached');

            Artisan::call('view:cache');
            $this->line('   ✓ Views cached');

            // Only cache events if the command exists
            if (array_key_exists('event:cache', Artisan::all())) {
                Artisan::call('event:cache');
                $this->line('   ✓ Events cached');
            }

        } catch (\Exception $e) {
            $this->error('   ✗ Optimization failed: '.$e->getMessage());
        }
    }

    private function cleanupOldData(): void
    {
        $this->info('🗑️ Cleaning up old data...');

        try {
            // Clear old cache entries
            Cache::flush();
            $this->line('   ✓ Cache flushed');

            // Clean up old log files (keep last 30 days)
            $logPath = storage_path('logs');
            if (is_dir($logPath)) {
                $files = glob($logPath.'/laravel-*.log');
                $cutoff = now()->subDays(30);

                foreach ($files as $file) {
                    if (filemtime($file) < $cutoff->timestamp) {
                        unlink($file);
                    }
                }
                $this->line('   ✓ Old log files cleaned');
            }

        } catch (\Exception $e) {
            $this->error('   ✗ Cleanup failed: '.$e->getMessage());
        }
    }

    private function verifyOptimization(): void
    {
        $this->info('🔍 Verifying optimization...');

        try {
            // Test database connection
            DB::connection()->getPdo();
            $this->line('   ✓ Database connection verified');

            // Check cache
            Cache::put('optimization_test', 'success', 60);
            if (Cache::get('optimization_test') === 'success') {
                $this->line('   ✓ Cache system working');
                Cache::forget('optimization_test');
            }

            // Check if routes are cached
            if (file_exists(base_path('bootstrap/cache/routes-v7.php'))) {
                $this->line('   ✓ Routes are cached');
            }

            // Check if config is cached
            if (file_exists(base_path('bootstrap/cache/config.php'))) {
                $this->line('   ✓ Configuration is cached');
            }

        } catch (\Exception $e) {
            $this->error('   ✗ Verification failed: '.$e->getMessage());
        }
    }
}
