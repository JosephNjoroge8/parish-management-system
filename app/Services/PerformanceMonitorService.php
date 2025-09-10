<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

/**
 * Performance monitoring and optimization service
 */
class PerformanceMonitorService
{
    private static $queryCount = 0;
    private static $queryTime = 0;
    private static $slowQueries = [];

    /**
     * Start performance monitoring
     */
    public static function startMonitoring(): void
    {
        // Listen to database queries
        DB::listen(function ($query) {
            self::$queryCount++;
            self::$queryTime += $query->time;

            // Log slow queries (over 100ms)
            if ($query->time > 100) {
                self::$slowQueries[] = [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'connection' => $query->connectionName,
                ];

                // Log critical slow queries (over 500ms)
                if ($query->time > 500) {
                    Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'time' => $query->time . 'ms',
                        'bindings' => $query->bindings,
                    ]);
                }
            }
        });
    }

    /**
     * Get performance metrics
     */
    public static function getMetrics(): array
    {
        return [
            'queries' => [
                'total_count' => self::$queryCount,
                'total_time' => round(self::$queryTime, 2),
                'average_time' => self::$queryCount > 0 ? round(self::$queryTime / self::$queryCount, 2) : 0,
                'slow_queries' => count(self::$slowQueries),
            ],
            'memory' => [
                'current_usage' => self::formatBytes(memory_get_usage()),
                'peak_usage' => self::formatBytes(memory_get_peak_usage()),
                'limit' => ini_get('memory_limit'),
            ],
            'cache' => self::getCacheMetrics(),
            'storage' => self::getStorageMetrics(),
        ];
    }

    /**
     * Get cache performance metrics
     */
    private static function getCacheMetrics(): array
    {
        try {
            $cacheStore = Cache::getStore();
            
            return [
                'driver' => config('cache.default'),
                'prefix' => config('cache.prefix'),
                'hits' => 'N/A', // Would need Redis/Memcached specific implementation
                'misses' => 'N/A',
                'hit_rate' => 'N/A',
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to retrieve cache metrics'];
        }
    }

    /**
     * Get storage metrics
     */
    private static function getStorageMetrics(): array
    {
        $storagePath = storage_path();
        $logSize = File::exists(storage_path('logs/laravel.log')) 
            ? File::size(storage_path('logs/laravel.log')) 
            : 0;

        return [
            'log_file_size' => self::formatBytes($logSize),
            'storage_path' => $storagePath,
            'disk_free_space' => self::formatBytes(disk_free_space($storagePath)),
            'cache_size' => self::getCacheDirectorySize(),
        ];
    }

    /**
     * Get cache directory size
     */
    private static function getCacheDirectorySize(): string
    {
        $cachePath = storage_path('framework/cache');
        if (!File::isDirectory($cachePath)) {
            return '0 B';
        }

        $size = 0;
        $files = File::allFiles($cachePath);
        
        foreach ($files as $file) {
            $size += $file->getSize();
        }

        return self::formatBytes($size);
    }

    /**
     * Analyze slow queries and provide recommendations
     */
    public static function analyzeSlowQueries(): array
    {
        $recommendations = [];

        foreach (self::$slowQueries as $query) {
            $sql = strtolower($query['sql']);
            
            // Analyze query patterns
            if (strpos($sql, 'select') === 0) {
                if (strpos($sql, 'where') === false) {
                    $recommendations[] = [
                        'type' => 'missing_where',
                        'message' => 'Query without WHERE clause detected',
                        'query' => $query['sql'],
                        'time' => $query['time'],
                    ];
                }
                
                if (strpos($sql, 'order by') !== false && strpos($sql, 'limit') === false) {
                    $recommendations[] = [
                        'type' => 'unlimited_order',
                        'message' => 'ORDER BY without LIMIT detected',
                        'query' => $query['sql'],
                        'time' => $query['time'],
                    ];
                }
                
                if (preg_match('/like\s+[\'"]%.*%[\'"]/', $sql)) {
                    $recommendations[] = [
                        'type' => 'inefficient_like',
                        'message' => 'Inefficient LIKE query with leading wildcard',
                        'query' => $query['sql'],
                        'time' => $query['time'],
                        'suggestion' => 'Consider full-text search or restructuring the query',
                    ];
                }
            }
        }

        return $recommendations;
    }

    /**
     * Get database optimization suggestions
     */
    public static function getDatabaseOptimizations(): array
    {
        $suggestions = [];

        try {
            // Check for missing indexes
            $tables = ['members', 'families', 'sacraments', 'tithes', 'activities'];
            
            foreach ($tables as $table) {
                $indexes = collect(DB::select("SHOW INDEX FROM {$table}"))
                    ->pluck('Column_name')
                    ->toArray();

                // Common columns that should be indexed
                $shouldBeIndexed = ['created_at', 'updated_at', 'status', 'type', 'date'];
                
                foreach ($shouldBeIndexed as $column) {
                    if (self::tableHasColumn($table, $column) && !in_array($column, $indexes)) {
                        $suggestions[] = [
                            'type' => 'missing_index',
                            'table' => $table,
                            'column' => $column,
                            'suggestion' => "Consider adding an index on {$table}.{$column}",
                        ];
                    }
                }
            }

            // Check table sizes
            $tableSizes = DB::select("
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                ORDER BY size_mb DESC
            ");

            foreach ($tableSizes as $tableSize) {
                if ($tableSize->size_mb > 100) { // Tables over 100MB
                    $suggestions[] = [
                        'type' => 'large_table',
                        'table' => $tableSize->table_name,
                        'size' => $tableSize->size_mb . ' MB',
                        'suggestion' => 'Consider partitioning or archiving old data',
                    ];
                }
            }

        } catch (\Exception $e) {
            Log::error('Database optimization analysis failed: ' . $e->getMessage());
        }

        return $suggestions;
    }

    /**
     * Check if table has a specific column
     */
    private static function tableHasColumn(string $table, string $column): bool
    {
        try {
            return collect(DB::getSchemaBuilder()->getColumnListing($table))
                ->contains($column);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate performance report
     */
    public static function generateReport(): array
    {
        return [
            'timestamp' => now(),
            'metrics' => self::getMetrics(),
            'slow_queries' => self::$slowQueries,
            'recommendations' => self::analyzeSlowQueries(),
            'database_optimizations' => self::getDatabaseOptimizations(),
            'system_info' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor] ?? 'TB');
    }

    /**
     * Reset monitoring data
     */
    public static function reset(): void
    {
        self::$queryCount = 0;
        self::$queryTime = 0;
        self::$slowQueries = [];
    }
}
