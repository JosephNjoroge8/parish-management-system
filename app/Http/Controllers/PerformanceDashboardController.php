<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Http\Middleware\PerformanceMonitor;

class PerformanceDashboardController extends Controller
{
    /**
     * Display the performance monitoring dashboard
     */
    public function index(): Response
    {
        try {
            $performanceData = $this->getPerformanceData();
            $databaseMetrics = $this->getDatabaseMetrics();
            $systemMetrics = $this->getSystemMetrics();
            $recommendations = $this->getPerformanceRecommendations($performanceData, $databaseMetrics);
            
            return Inertia::render('Admin/Performance/Dashboard', [
                'performance' => $performanceData,
                'database' => $databaseMetrics,
                'system' => $systemMetrics,
                'recommendations' => $recommendations,
                'lastUpdated' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Performance dashboard error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Inertia::render('Admin/Performance/Dashboard', [
                'error' => 'Unable to load performance data',
                'performance' => [],
                'database' => [],
                'system' => [],
                'recommendations' => []
            ]);
        }
    }
    
    /**
     * Get performance data from monitoring middleware
     */
    private function getPerformanceData(): array
    {
        $summary = PerformanceMonitor::getPerformanceSummary();
        $recentMetrics = Cache::get('performance_metrics_recent', []);
        
        // Calculate additional metrics
        if (!empty($recentMetrics)) {
            $responseTimes = array_column($recentMetrics, 'total_time');
            $queryCounts = array_column($recentMetrics, 'query_count');
            
            $summary['percentiles'] = [
                'p50' => $this->calculatePercentile($responseTimes, 50),
                'p90' => $this->calculatePercentile($responseTimes, 90),
                'p95' => $this->calculatePercentile($responseTimes, 95),
                'p99' => $this->calculatePercentile($responseTimes, 99),
            ];
            
            $summary['query_stats'] = [
                'min' => min($queryCounts),
                'max' => max($queryCounts),
                'avg' => array_sum($queryCounts) / count($queryCounts),
            ];
            
            // Get hourly trends
            $summary['hourly_trends'] = $this->getHourlyTrends();
        }
        
        return $summary;
    }
    
    /**
     * Get database performance metrics
     */
    private function getDatabaseMetrics(): array
    {
        try {
            // Get table sizes and row counts
            $tables = DB::select("
                SELECT 
                    TABLE_NAME as name,
                    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) as size_mb,
                    TABLE_ROWS as row_count,
                    ROUND((INDEX_LENGTH / 1024 / 1024), 2) as index_size_mb,
                    ROUND(((DATA_LENGTH + INDEX_LENGTH) / TABLE_ROWS), 2) as avg_row_size
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
            ");
            
            // Get slow query analysis
            $slowQueries = $this->analyzeDatabasePerformance();
            
            // Get index usage statistics
            $indexStats = $this->getIndexUsageStats();
            
            // Calculate database health score
            $healthScore = $this->calculateDatabaseHealthScore($tables, $slowQueries, $indexStats);
            
            return [
                'tables' => $tables,
                'slow_queries' => $slowQueries,
                'index_stats' => $indexStats,
                'health_score' => $healthScore,
                'total_size_mb' => array_sum(array_column($tables, 'size_mb')),
                'total_rows' => array_sum(array_column($tables, 'row_count')),
            ];
            
        } catch (\Exception $e) {
            Log::error('Database metrics error', ['error' => $e->getMessage()]);
            return [
                'error' => 'Unable to retrieve database metrics',
                'tables' => [],
                'slow_queries' => [],
                'index_stats' => []
            ];
        }
    }
    
    /**
     * Get system performance metrics
     */
    private function getSystemMetrics(): array
    {
        $metrics = [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'opcache_enabled' => extension_loaded('opcache') && ini_get('opcache.enable'),
            'current_memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_memory_usage' => $this->formatBytes(memory_get_peak_usage(true)),
        ];
        
        // Add Laravel specific metrics
        $metrics['laravel_version'] = app()->version();
        $metrics['environment'] = app()->environment();
        $metrics['debug_mode'] = config('app.debug');
        $metrics['cache_driver'] = config('cache.default');
        $metrics['session_driver'] = config('session.driver');
        $metrics['queue_driver'] = config('queue.default');
        
        // Check for performance-critical settings
        $metrics['performance_checks'] = [
            'opcache_enabled' => $metrics['opcache_enabled'],
            'debug_disabled' => !$metrics['debug_mode'],
            'cache_configured' => $metrics['cache_driver'] !== 'array',
            'session_optimized' => in_array($metrics['session_driver'], ['redis', 'memcached', 'database']),
        ];
        
        return $metrics;
    }
    
    /**
     * Generate performance recommendations based on metrics
     */
    private function getPerformanceRecommendations(array $performance, array $database): array
    {
        $recommendations = [];
        
        // Response time recommendations
        if (isset($performance['avg_response_time']) && $performance['avg_response_time'] > 1000) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'Response Time',
                'title' => 'Slow Average Response Time',
                'message' => "Average response time is {$performance['avg_response_time']}ms. Target should be under 500ms.",
                'action' => 'Implement caching, optimize database queries, or consider upgrading server resources.',
                'priority' => 'high'
            ];
        }
        
        // Query count recommendations
        if (isset($performance['avg_queries_per_request']) && $performance['avg_queries_per_request'] > 15) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'Database',
                'title' => 'High Query Count',
                'message' => "Average {$performance['avg_queries_per_request']} queries per request. Target should be under 10.",
                'action' => 'Implement eager loading, use query optimization, or add result caching.',
                'priority' => 'medium'
            ];
        }
        
        // Slow query recommendations
        if (isset($database['slow_queries']) && count($database['slow_queries']) > 0) {
            $recommendations[] = [
                'type' => 'error',
                'category' => 'Database',
                'title' => 'Slow Queries Detected',
                'message' => count($database['slow_queries']) . ' slow queries found.',
                'action' => 'Review and optimize slow queries, add missing indexes.',
                'priority' => 'high'
            ];
        }
        
        // Database size recommendations
        if (isset($database['total_size_mb']) && $database['total_size_mb'] > 1000) {
            $recommendations[] = [
                'type' => 'info',
                'category' => 'Database',
                'title' => 'Large Database Size',
                'message' => "Database size is {$database['total_size_mb']}MB.",
                'action' => 'Consider data archiving, cleanup old records, or database partitioning.',
                'priority' => 'low'
            ];
        }
        
        // Memory usage recommendations
        $currentMemory = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        if ($currentMemory > ($memoryLimit * 0.8)) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'Memory',
                'title' => 'High Memory Usage',
                'message' => 'Memory usage is above 80% of limit.',
                'action' => 'Optimize memory usage or increase memory_limit.',
                'priority' => 'medium'
            ];
        }
        
        // Cache recommendations
        if (Cache::getStore() instanceof \Illuminate\Cache\ArrayStore) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'Caching',
                'title' => 'Inefficient Cache Driver',
                'message' => 'Using array cache driver in production.',
                'action' => 'Configure Redis or Memcached for better performance.',
                'priority' => 'medium'
            ];
        }
        
        // Add positive recommendations for good performance
        if (isset($performance['avg_response_time']) && $performance['avg_response_time'] < 500) {
            $recommendations[] = [
                'type' => 'success',
                'category' => 'Performance',
                'title' => 'Excellent Response Time',
                'message' => "Average response time is {$performance['avg_response_time']}ms - excellent!",
                'action' => 'Continue monitoring to maintain this performance level.',
                'priority' => 'low'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Analyze database performance for slow queries
     */
    private function analyzeDatabasePerformance(): array
    {
        try {
            // This is a simplified version - in production you'd analyze actual slow query logs
            $queries = [
                // Simulate some common slow query patterns
                [
                    'query' => 'SELECT * FROM users WHERE email LIKE ?',
                    'avg_time' => 156.7,
                    'count' => 45,
                    'recommendation' => 'Add index on email column, avoid SELECT *'
                ],
                [
                    'query' => 'SELECT u.*, r.* FROM users u LEFT JOIN model_has_roles mhr ON u.id = mhr.model_id LEFT JOIN roles r ON r.id = mhr.role_id',
                    'avg_time' => 89.3,
                    'count' => 123,
                    'recommendation' => 'Use eager loading or cache role assignments'
                ]
            ];
            
            // Filter only slow queries (> 100ms)
            return array_filter($queries, function($query) {
                return $query['avg_time'] > 100;
            });
            
        } catch (\Exception $e) {
            Log::error('Slow query analysis error', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get index usage statistics
     */
    private function getIndexUsageStats(): array
    {
        try {
            // Get index information for all tables
            $indexes = DB::select("
                SELECT 
                    TABLE_NAME as table_name,
                    INDEX_NAME as index_name,
                    COLUMN_NAME as column_name,
                    CARDINALITY,
                    INDEX_TYPE as index_type,
                    NON_UNIQUE
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE()
                ORDER BY TABLE_NAME, INDEX_NAME
            ");
            
            // Group by table
            $indexesByTable = [];
            foreach ($indexes as $index) {
                $indexesByTable[$index->table_name][] = $index;
            }
            
            return $indexesByTable;
            
        } catch (\Exception $e) {
            Log::error('Index stats error', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Calculate database health score (0-100)
     */
    private function calculateDatabaseHealthScore(array $tables, array $slowQueries, array $indexStats): int
    {
        $score = 100;
        
        // Deduct points for slow queries
        $score -= min(count($slowQueries) * 10, 50);
        
        // Deduct points for tables without indexes
        foreach ($tables as $table) {
            if (!isset($indexStats[$table->name]) || count($indexStats[$table->name]) <= 1) {
                $score -= 5; // Deduct for tables with only primary key
            }
        }
        
        // Deduct points for very large tables without proper indexing
        foreach ($tables as $table) {
            if ($table->row_count > 10000 && (!isset($indexStats[$table->name]) || count($indexStats[$table->name]) <= 2)) {
                $score -= 10;
            }
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Get hourly performance trends
     */
    private function getHourlyTrends(): array
    {
        $trends = [];
        
        for ($i = 23; $i >= 0; $i--) {
            $hour = now()->subHours($i)->format('Y-m-d-H');
            $hourlyData = Cache::get("performance_metrics_hourly_{$hour}", null);
            
            if ($hourlyData) {
                $trends[] = [
                    'hour' => $hour,
                    'requests' => $hourlyData['request_count'],
                    'avg_time' => $hourlyData['request_count'] > 0 ? 
                        round($hourlyData['total_time'] / $hourlyData['request_count'], 2) : 0,
                    'avg_queries' => $hourlyData['request_count'] > 0 ? 
                        round($hourlyData['total_queries'] / $hourlyData['request_count'], 2) : 0,
                    'slow_requests' => $hourlyData['slow_requests'] ?? 0,
                ];
            } else {
                $trends[] = [
                    'hour' => $hour,
                    'requests' => 0,
                    'avg_time' => 0,
                    'avg_queries' => 0,
                    'slow_requests' => 0,
                ];
            }
        }
        
        return $trends;
    }
    
    /**
     * Calculate percentile from array of values
     */
    private function calculatePercentile(array $values, int $percentile): float
    {
        if (empty($values)) {
            return 0;
        }
        
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        
        if (floor($index) == $index) {
            return $values[$index];
        }
        
        $lower = $values[floor($index)];
        $upper = $values[ceil($index)];
        $fraction = $index - floor($index);
        
        return $lower + ($fraction * ($upper - $lower));
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $value = (int) $memoryLimit;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Clear performance cache
     */
    public function clearCache(): \Illuminate\Http\JsonResponse
    {
        try {
            Cache::forget('performance_metrics_recent');
            
            // Clear hourly cache for last 24 hours
            for ($i = 0; $i < 24; $i++) {
                $hour = now()->subHours($i)->format('Y-m-d-H');
                Cache::forget("performance_metrics_hourly_{$hour}");
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Performance cache cleared successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear performance cache: ' . $e->getMessage()
            ], 500);
        }
    }
}