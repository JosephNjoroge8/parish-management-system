<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Performance Monitoring Service
 * Tracks database queries, memory usage, and application performance
 */
class PerformanceMonitoringService
{
    private static $queryLog = [];

    private static $memorySnapshots = [];

    private static $startTime;

    public static function startMonitoring(): void
    {
        self::$startTime = microtime(true);

        // Monitor database queries
        DB::listen(function ($query) {
            $duration = $query->time;

            self::$queryLog[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $duration,
                'timestamp' => microtime(true),
            ];

            // Log slow queries
            if ($duration > 100) { // queries taking more than 100ms
                Log::warning('Slow Query Detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'duration' => $duration.'ms',
                    'url' => request()->fullUrl(),
                    'user_id' => auth()->id(),
                ]);
            }

            // Log N+1 query patterns
            self::detectNPlusOneQueries($query);
        });

        // Take initial memory snapshot
        self::$memorySnapshots['start'] = [
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'timestamp' => microtime(true),
        ];
    }

    public static function endMonitoring(): array
    {
        $endTime = microtime(true);
        $totalTime = ($endTime - self::$startTime) * 1000; // Convert to milliseconds

        // Take final memory snapshot
        self::$memorySnapshots['end'] = [
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'timestamp' => microtime(true),
        ];

        $performance = [
            'total_time' => round($totalTime, 2),
            'query_count' => count(self::$queryLog),
            'total_query_time' => round(array_sum(array_column(self::$queryLog, 'time')), 2),
            'slow_queries' => array_filter(self::$queryLog, fn ($q) => $q['time'] > 100),
            'memory_usage' => [
                'start' => self::formatBytes(self::$memorySnapshots['start']['memory_usage']),
                'end' => self::formatBytes(self::$memorySnapshots['end']['memory_usage']),
                'peak' => self::formatBytes(self::$memorySnapshots['end']['peak_memory']),
                'difference' => self::formatBytes(
                    self::$memorySnapshots['end']['memory_usage'] - self::$memorySnapshots['start']['memory_usage']
                ),
            ],
            'queries' => self::$queryLog,
        ];

        // Log performance metrics if performance is poor
        if ($totalTime > 2000 || count(self::$queryLog) > 50) {
            Log::warning('Poor Page Performance', [
                'url' => request()->fullUrl(),
                'total_time' => $totalTime.'ms',
                'query_count' => count(self::$queryLog),
                'user_id' => auth()->id(),
            ]);
        }

        // Store performance metrics for analysis
        self::storePerformanceMetrics($performance);

        return $performance;
    }

    private static function detectNPlusOneQueries($query): void
    {
        static $queryPatterns = [];

        // Simple N+1 detection based on similar query patterns
        $pattern = preg_replace('/\d+/', '?', $query->sql);

        if (! isset($queryPatterns[$pattern])) {
            $queryPatterns[$pattern] = 0;
        }

        $queryPatterns[$pattern]++;

        // If same pattern executed more than 10 times, likely N+1
        if ($queryPatterns[$pattern] > 10) {
            Log::warning('Potential N+1 Query Detected', [
                'pattern' => $pattern,
                'count' => $queryPatterns[$pattern],
                'url' => request()->fullUrl(),
            ]);
        }
    }

    private static function storePerformanceMetrics(array $performance): void
    {
        $date = Carbon::now()->format('Y-m-d');
        $hour = Carbon::now()->format('H');

        // Store hourly performance metrics
        $key = "performance_metrics_{$date}_{$hour}";
        $metrics = Cache::get($key, []);

        $metrics[] = [
            'timestamp' => Carbon::now()->toISOString(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'user_id' => auth()->id(),
            'total_time' => $performance['total_time'],
            'query_count' => $performance['query_count'],
            'total_query_time' => $performance['total_query_time'],
            'memory_peak' => self::$memorySnapshots['end']['peak_memory'],
        ];

        // Keep only last 100 entries per hour to avoid memory issues
        if (count($metrics) > 100) {
            $metrics = array_slice($metrics, -100);
        }

        Cache::put($key, $metrics, 3600); // Cache for 1 hour
    }

    public static function getPerformanceReport(?string $date = null): array
    {
        $date = $date ?: Carbon::now()->format('Y-m-d');
        $report = [
            'date' => $date,
            'total_requests' => 0,
            'avg_response_time' => 0,
            'avg_query_count' => 0,
            'slow_requests' => 0,
            'memory_usage' => [],
            'popular_pages' => [],
            'slow_pages' => [],
        ];

        $allMetrics = [];

        // Collect metrics for all hours of the day
        for ($hour = 0; $hour < 24; $hour++) {
            $key = "performance_metrics_{$date}_".str_pad($hour, 2, '0', STR_PAD_LEFT);
            $hourlyMetrics = Cache::get($key, []);
            $allMetrics = array_merge($allMetrics, $hourlyMetrics);
        }

        if (empty($allMetrics)) {
            return $report;
        }

        $report['total_requests'] = count($allMetrics);
        $report['avg_response_time'] = round(array_sum(array_column($allMetrics, 'total_time')) / count($allMetrics), 2);
        $report['avg_query_count'] = round(array_sum(array_column($allMetrics, 'query_count')) / count($allMetrics), 2);
        $report['slow_requests'] = count(array_filter($allMetrics, fn ($m) => $m['total_time'] > 2000));

        // Popular pages
        $pageViews = [];
        foreach ($allMetrics as $metric) {
            $url = parse_url($metric['url'], PHP_URL_PATH);
            $pageViews[$url] = ($pageViews[$url] ?? 0) + 1;
        }
        arsort($pageViews);
        $report['popular_pages'] = array_slice($pageViews, 0, 10);

        // Slow pages
        $slowPages = array_filter($allMetrics, fn ($m) => $m['total_time'] > 1000);
        usort($slowPages, fn ($a, $b) => $b['total_time'] <=> $a['total_time']);
        $report['slow_pages'] = array_slice($slowPages, 0, 10);

        return $report;
    }

    public static function getDatabaseStats(): array
    {
        return [
            'total_members' => DB::table('members')->count(),
            'total_families' => DB::table('families')->count(),
            'total_sacraments' => DB::table('sacraments')->count(),
            'total_tithes' => DB::table('tithes')->count(),
            'database_size' => self::getDatabaseSize(),
            'largest_tables' => self::getLargestTables(),
            'index_usage' => self::getIndexUsage(),
        ];
    }

    private static function getDatabaseSize(): string
    {
        try {
            // For SQLite
            if (config('database.default') === 'sqlite') {
                $dbPath = database_path('database.sqlite');
                if (file_exists($dbPath)) {
                    return self::formatBytes(filesize($dbPath));
                }
            }

            // For MySQL/PostgreSQL
            $result = DB::select('
                SELECT 
                    SUM(data_length + index_length) as size 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ');

            return self::formatBytes($result[0]->size ?? 0);
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private static function getLargestTables(): array
    {
        try {
            // This is a simplified version - would need database-specific queries
            $tables = ['members', 'families', 'sacraments', 'tithes', 'activities'];
            $sizes = [];

            foreach ($tables as $table) {
                $count = DB::table($table)->count();
                $sizes[$table] = $count;
            }

            arsort($sizes);

            return $sizes;
        } catch (\Exception $e) {
            return [];
        }
    }

    private static function getIndexUsage(): array
    {
        // This would require database-specific queries
        // For now, return a placeholder
        return [
            'total_indexes' => 'N/A (requires MySQL/PostgreSQL)',
            'unused_indexes' => 'N/A',
            'duplicate_indexes' => 'N/A',
        ];
    }

    private static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }

    public static function clearOldMetrics(): void
    {
        // Clear metrics older than 7 days
        $pattern = 'performance_metrics_*';
        $keys = Cache::getStore()->getRedis()->keys($pattern);

        foreach ($keys as $key) {
            $keyParts = explode('_', $key);
            if (count($keyParts) >= 4) {
                $date = $keyParts[2];
                if (Carbon::createFromFormat('Y-m-d', $date)->lt(Carbon::now()->subDays(7))) {
                    Cache::forget($key);
                }
            }
        }
    }
}
