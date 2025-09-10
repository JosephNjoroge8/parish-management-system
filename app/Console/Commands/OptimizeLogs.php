<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class OptimizeLogs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'logs:optimize 
                           {--days=7 : Number of days to keep logs} 
                           {--size=5 : Maximum log file size in MB}
                           {--archive : Archive old logs instead of deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize and manage log files to improve performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $maxSize = (int) $this->option('size') * 1024 * 1024; // Convert to bytes
        $archive = $this->option('archive');

        $this->info('Starting log optimization...');

        // Get log directory
        $logPath = storage_path('logs');

        if (!File::isDirectory($logPath)) {
            $this->error('Log directory not found');
            return 1;
        }

        $totalCleaned = 0;
        $totalSize = 0;

        // Process log files
        $logFiles = File::glob($logPath . '/*.log');

        foreach ($logFiles as $logFile) {
            $fileName = basename($logFile);
            $fileSize = File::size($logFile);
            $fileAge = now()->diffInDays(File::lastModified($logFile));

            $this->line("Processing: {$fileName} (Size: " . $this->formatBytes($fileSize) . ", Age: {$fileAge} days)");

            // Check if file is too old
            if ($fileAge > $days) {
                if ($archive) {
                    $this->archiveLogFile($logFile);
                    $this->info("  → Archived old log file");
                } else {
                    File::delete($logFile);
                    $this->info("  → Deleted old log file");
                }
                $totalCleaned++;
                $totalSize += $fileSize;
                continue;
            }

            // Check if file is too large
            if ($fileSize > $maxSize) {
                if ($archive) {
                    $this->archiveLogFile($logFile);
                    $this->info("  → Archived large log file");
                } else {
                    $this->truncateLogFile($logFile, $maxSize);
                    $this->info("  → Truncated large log file");
                }
                $totalCleaned++;
                $totalSize += $fileSize;
            }
        }

        // Clear log buffers
        $this->clearLogBuffers();

        $this->info("\nLog optimization completed!");
        $this->info("Files processed: {$totalCleaned}");
        $this->info("Space freed: " . $this->formatBytes($totalSize));

        return 0;
    }

    /**
     * Archive a log file
     */
    private function archiveLogFile(string $logFile): void
    {
        $archiveDir = storage_path('logs/archive');
        
        if (!File::isDirectory($archiveDir)) {
            File::makeDirectory($archiveDir, 0755, true);
        }

        $fileName = basename($logFile, '.log');
        $archiveName = $fileName . '_' . now()->format('Y-m-d_H-i-s') . '.log.gz';
        $archivePath = $archiveDir . '/' . $archiveName;

        // Compress and move
        $handle = gzopen($archivePath, 'wb9');
        gzwrite($handle, File::get($logFile));
        gzclose($handle);

        File::delete($logFile);
    }

    /**
     * Truncate a log file to keep only recent entries
     */
    private function truncateLogFile(string $logFile, int $maxSize): void
    {
        $content = File::get($logFile);
        $lines = explode("\n", $content);
        
        // Keep the last portion of the file
        $keepLines = intval(count($lines) * 0.3); // Keep last 30%
        $truncatedContent = implode("\n", array_slice($lines, -$keepLines));

        File::put($logFile, $truncatedContent);
    }

    /**
     * Clear log buffers and optimize log files
     */
    private function clearLogBuffers(): void
    {
        // Force log rotation
        Log::info('Log optimization completed at ' . now());
        
        // Clear any buffered logs
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor] ?? 'TB');
    }
}
