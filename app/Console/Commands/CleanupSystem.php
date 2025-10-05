<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CleanupSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:cleanup {--dry-run : Show what would be cleaned without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up redundant files and optimize the Parish Management System';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Starting Parish Management System Cleanup...');

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No files will be deleted');
        }

        // Clean up redundant files
        $this->cleanupRedundantFiles($dryRun);

        // Clean up unused routes
        $this->cleanupUnusedRoutes($dryRun);

        // Clean up old temporary files
        $this->cleanupTemporaryFiles($dryRun);

        // Clean up old logs
        $this->cleanupOldLogs($dryRun);

        // Clean up unused frontend assets
        $this->cleanupUnusedAssets($dryRun);

        // Optimize database
        $this->optimizeDatabase($dryRun);

        $this->info('✅ System cleanup completed!');

        return 0;
    }

    private function cleanupRedundantFiles($dryRun = false): void
    {
        $this->info('📁 Cleaning up redundant files...');

        $redundantFiles = [
            'routes/web_broken.php',
            'routes/web.php.backup',
            'routes/web.php.broken',
            'public/index-cpanel.php', // Duplicate of index.php
        ];

        foreach ($redundantFiles as $file) {
            $fullPath = base_path($file);
            if (File::exists($fullPath)) {
                if ($dryRun) {
                    $this->line("   🔍 Would delete: {$file}");
                } else {
                    File::delete($fullPath);
                    $this->line("   ✓ Deleted: {$file}");
                }
            }
        }

        // Clean up documentation files that are not needed in production
        $docFiles = [
            'DEPLOYMENT_GUIDE.md',
            'MYSQL_MIGRATION_GUIDE.md',
            'MEMBER_REGISTRATION_ANALYSIS.md',
            'MEMBER_REGISTRATION_RESOLUTION.md',
            'DATABASE_ENHANCEMENT_REPORT.md',
        ];

        foreach ($docFiles as $file) {
            $fullPath = base_path($file);
            if (File::exists($fullPath)) {
                if ($dryRun) {
                    $this->line("   🔍 Would move to archive: {$file}");
                } else {
                    // Move to docs archive instead of deleting
                    $archiveDir = base_path('docs/archive');
                    if (! File::exists($archiveDir)) {
                        File::makeDirectory($archiveDir, 0755, true);
                    }
                    File::move($fullPath, $archiveDir.'/'.$file);
                    $this->line("   ✓ Archived: {$file}");
                }
            }
        }
    }

    private function cleanupUnusedRoutes($dryRun = false): void
    {
        $this->info('🛣️ Analyzing routes...');

        // Check for unused route files
        $routeFiles = [
            'routes/api.php' => 'API routes (check if needed)',
            'routes/channels.php' => 'Broadcasting routes (check if needed)',
        ];

        foreach ($routeFiles as $file => $description) {
            $fullPath = base_path($file);
            if (File::exists($fullPath)) {
                $content = File::get($fullPath);
                if (trim(str_replace(['<?php', 'use ', '//'], '', $content)) === '') {
                    if ($dryRun) {
                        $this->line("   🔍 Would check: {$file} - {$description}");
                    } else {
                        $this->line("   ℹ Empty route file: {$file}");
                    }
                }
            }
        }
    }

    private function cleanupTemporaryFiles($dryRun = false): void
    {
        $this->info('🗂️ Cleaning up temporary files...');

        $tempDirs = [
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ];

        foreach ($tempDirs as $dir) {
            if (File::exists($dir)) {
                $files = File::files($dir);
                $cleaned = 0;

                foreach ($files as $file) {
                    // Delete files older than 7 days
                    if (time() - $file->getMTime() > 604800) { // 7 days in seconds
                        if ($dryRun) {
                            $cleaned++;
                        } else {
                            File::delete($file);
                            $cleaned++;
                        }
                    }
                }

                if ($cleaned > 0) {
                    $action = $dryRun ? 'Would clean' : 'Cleaned';
                    $this->line("   ✓ {$action} {$cleaned} old files from ".basename($dir));
                }
            }
        }
    }

    private function cleanupOldLogs($dryRun = false): void
    {
        $this->info('📜 Cleaning up old logs...');

        $logPath = storage_path('logs');
        $deploymentLogsPath = base_path('logs');

        // Clean Laravel logs older than 30 days
        if (File::exists($logPath)) {
            $logFiles = File::glob($logPath.'/laravel-*.log');
            $cleaned = 0;

            foreach ($logFiles as $logFile) {
                if (time() - filemtime($logFile) > 2592000) { // 30 days
                    if ($dryRun) {
                        $cleaned++;
                    } else {
                        File::delete($logFile);
                        $cleaned++;
                    }
                }
            }

            if ($cleaned > 0) {
                $action = $dryRun ? 'Would clean' : 'Cleaned';
                $this->line("   ✓ {$action} {$cleaned} old Laravel log files");
            }
        }

        // Clean deployment logs older than 60 days
        if (File::exists($deploymentLogsPath)) {
            $deploymentLogs = File::glob($deploymentLogsPath.'/deployment_*.log');
            $cleaned = 0;

            foreach ($deploymentLogs as $logFile) {
                if (time() - filemtime($logFile) > 5184000) { // 60 days
                    if ($dryRun) {
                        $cleaned++;
                    } else {
                        File::delete($logFile);
                        $cleaned++;
                    }
                }
            }

            if ($cleaned > 0) {
                $action = $dryRun ? 'Would clean' : 'Cleaned';
                $this->line("   ✓ {$action} {$cleaned} old deployment log files");
            }
        }
    }

    private function cleanupUnusedAssets($dryRun = false): void
    {
        $this->info('🎨 Analyzing frontend assets...');

        // Check for unused build files
        $buildPath = public_path('build');

        if (File::exists($buildPath)) {
            $this->line('   ℹ Build directory exists with '.count(File::allFiles($buildPath)).' files');

            // Check for very old build files (older than 30 days)
            $oldFiles = 0;
            foreach (File::allFiles($buildPath) as $file) {
                if (time() - $file->getMTime() > 2592000) { // 30 days
                    $oldFiles++;
                }
            }

            if ($oldFiles > 0) {
                $this->line("   ⚠ Found {$oldFiles} old build files (consider rebuilding assets)");
            }
        }

        // Check for node_modules in production (should not exist)
        $nodeModules = base_path('node_modules');
        if (File::exists($nodeModules)) {
            $this->warn('   ⚠ node_modules directory exists (consider removing in production)');
        }
    }

    private function optimizeDatabase($dryRun = false): void
    {
        $this->info('🗄️ Optimizing database...');

        try {
            if (DB::getDriverName() === 'sqlite') {
                if (! $dryRun) {
                    DB::statement('VACUUM');
                    $this->line('   ✓ SQLite database vacuumed');
                } else {
                    $this->line('   🔍 Would vacuum SQLite database');
                }
            } else {
                // MySQL optimization
                $tables = DB::select('SHOW TABLES');
                $tableColumn = 'Tables_in_'.config('database.connections.mysql.database');

                foreach ($tables as $table) {
                    $tableName = $table->$tableColumn;
                    if (! $dryRun) {
                        DB::statement("OPTIMIZE TABLE {$tableName}");
                    }
                }

                $action = $dryRun ? 'Would optimize' : 'Optimized';
                $this->line("   ✓ {$action} ".count($tables).' MySQL tables');
            }
        } catch (\Exception $e) {
            $this->error('   ✗ Database optimization failed: '.$e->getMessage());
        }
    }
}
