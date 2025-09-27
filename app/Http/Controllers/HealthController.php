<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

class HealthController extends Controller
{
    /**
     * Application health check endpoint
     */
    public function check()
    {
        $checks = [];
        $overall = true;

        // Database check
        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            $checks['database'] = [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
            $overall = false;
        }

        // Cache check
        try {
            Cache::put('health_check', 'ok', 60);
            $value = Cache::get('health_check');
            
            $checks['cache'] = [
                'status' => $value === 'ok' ? 'healthy' : 'error',
                'message' => $value === 'ok' ? 'Cache is working' : 'Cache test failed'
            ];
            
            if ($value !== 'ok') {
                $overall = false;
            }
        } catch (\Exception $e) {
            $checks['cache'] = [
                'status' => 'error',
                'message' => 'Cache error: ' . $e->getMessage()
            ];
            $overall = false;
        }

        // Storage check
        try {
            Storage::put('health_check.txt', 'ok');
            $content = Storage::get('health_check.txt');
            Storage::delete('health_check.txt');
            
            $checks['storage'] = [
                'status' => $content === 'ok' ? 'healthy' : 'error',
                'message' => $content === 'ok' ? 'Storage is working' : 'Storage test failed'
            ];
            
            if ($content !== 'ok') {
                $overall = false;
            }
        } catch (\Exception $e) {
            $checks['storage'] = [
                'status' => 'error',
                'message' => 'Storage error: ' . $e->getMessage()
            ];
            $overall = false;
        }

        // System resources check
        $checks['system'] = $this->checkSystemResources();
        if ($checks['system']['status'] === 'warning') {
            // Don't fail overall health for warnings
        }

        // Application version and environment
        $checks['application'] = [
            'status' => 'healthy',
            'version' => config('app.version', '1.0.0'),
            'environment' => app()->environment(),
            'debug' => config('app.debug'),
            'maintenance' => app()->isDownForMaintenance()
        ];

        $response = [
            'status' => $overall ? 'healthy' : 'error',
            'timestamp' => now()->toISOString(),
            'checks' => $checks
        ];

        return response()->json($response, $overall ? 200 : 503);
    }

    /**
     * Simple health check endpoint (lightweight)
     */
    public function simple()
    {
        return response('healthy', 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Database health check
     */
    public function database()
    {
        try {
            $start = microtime(true);
            $pdo = DB::connection()->getPdo();
            $time = round((microtime(true) - $start) * 1000, 2);

            // Test a simple query
            DB::select('SELECT 1');

            return response()->json([
                'status' => 'healthy',
                'connection_time' => "{$time}ms",
                'driver' => DB::connection()->getDriverName()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 503);
        }
    }

    /**
     * Cache health check
     */
    public function cache()
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_' . random_int(1000, 9999);

            $start = microtime(true);
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            $time = round((microtime(true) - $start) * 1000, 2);

            Cache::forget($testKey);

            if ($retrieved === $testValue) {
                return response()->json([
                    'status' => 'healthy',
                    'response_time' => "{$time}ms",
                    'driver' => config('cache.default')
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cache read/write test failed'
                ], 503);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 503);
        }
    }

    /**
     * Check system resources
     */
    private function checkSystemResources(): array
    {
        $status = 'healthy';
        $warnings = [];

        // Memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        
        if ($memoryLimitBytes > 0) {
            $memoryPercentage = ($memoryUsage / $memoryLimitBytes) * 100;
            if ($memoryPercentage > 85) {
                $status = 'warning';
                $warnings[] = "High memory usage: {$memoryPercentage}%";
            }
        }

        // Disk space (if we can check it)
        if (function_exists('disk_free_space')) {
            $free = disk_free_space('.');
            $total = disk_total_space('.');
            
            if ($total > 0) {
                $used = $total - $free;
                $usagePercentage = ($used / $total) * 100;
                
                if ($usagePercentage > 85) {
                    $status = 'warning';
                    $warnings[] = "High disk usage: {$usagePercentage}%";
                }
            }
        }

        return [
            'status' => $status,
            'memory_usage' => $this->formatBytes($memoryUsage),
            'memory_limit' => $memoryLimit,
            'warnings' => $warnings
        ];
    }

    /**
     * Convert memory limit string to bytes
     */
    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $number = (int) substr($value, 0, -1);

        switch ($unit) {
            case 'g':
                return $number * 1024 * 1024 * 1024;
            case 'm':
                return $number * 1024 * 1024;
            case 'k':
                return $number * 1024;
            default:
                return (int) $value;
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}