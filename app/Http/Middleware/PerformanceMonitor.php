<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PerformanceMonitor
{
    private $startTime;
    private $startMemory;
    private $queries = [];
    
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Start performance monitoring
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Listen for database queries
        DB::listen(function ($query) {
            $this->queries[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
                'connection' => $query->connectionName,
            ];
        });
        
        $response = $next($request);
        
        // Calculate metrics
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $totalTime = ($endTime - $this->startTime) * 1000; // Convert to milliseconds
        $memoryUsage = $endMemory - $this->startMemory;
        $peakMemory = memory_get_peak_usage(true);
        
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $totalQueryTime = array_sum(array_column($queries, 'time'));
        
        // Store performance data
        $performanceData = [
            'route' => $request->route() ? $request->route()->getName() : 'unknown',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'total_time' => round($totalTime, 2),
            'memory_usage' => $this->formatBytes($memoryUsage),
            'peak_memory' => $this->formatBytes($peakMemory),
            'query_count' => $queryCount,
            'total_query_time' => round($totalQueryTime, 2),
            'slow_queries' => array_filter($queries, function($query) {
                return isset($query['time']) && $query['time'] > 100; // Queries slower than 100ms
            }),
            'timestamp' => now(),
        ];
        
        // Log slow requests
        if ($totalTime > 2000) { // Requests slower than 2 seconds
            Log::warning('Slow request detected', $performanceData);
        }
        
        // Log requests with too many queries
        if ($queryCount > 20) {
            Log::warning('High query count detected', [
                'query_count' => $queryCount,
                'route' => $performanceData['route'],
                'queries' => array_map(function($query) {
                    return [
                        'sql' => $query['sql'] ?? 'N/A',
                        'time' => $query['time'] ?? 0
                    ];
                }, $queries)
            ]);
        }
        
        // Store in cache for monitoring dashboard
        $this->storePerformanceMetrics($performanceData);
        
        // Add performance headers for debugging
        if (config('app.debug')) {
            $response->headers->set('X-Performance-Time', $totalTime . 'ms');
            $response->headers->set('X-Performance-Memory', $this->formatBytes($memoryUsage));
            $response->headers->set('X-Performance-Queries', $queryCount);
            $response->headers->set('X-Performance-Query-Time', $totalQueryTime . 'ms');
        }
        
        return $response;
    }
    
    /**
     * Store performance metrics for monitoring
     */
    private function storePerformanceMetrics(array $data): void
    {
        try {
            // Store recent metrics (last 100 requests)
            $recentMetrics = Cache::get('performance_metrics_recent', []);
            array_unshift($recentMetrics, $data);
            $recentMetrics = array_slice($recentMetrics, 0, 100);
            Cache::put('performance_metrics_recent', $recentMetrics, 3600); // 1 hour
            
            // Store hourly aggregated data
            $hour = now()->format('Y-m-d-H');
            $hourlyKey = "performance_metrics_hourly_{$hour}";
            $hourlyData = Cache::get($hourlyKey, [
                'hour' => $hour,
                'request_count' => 0,
                'total_time' => 0,
                'total_memory' => 0,
                'total_queries' => 0,
                'slow_requests' => 0,
                'routes' => []
            ]);
            
            $hourlyData['request_count']++;
            $hourlyData['total_time'] += $data['total_time'];
            $hourlyData['total_memory'] += $this->parseBytes($data['memory_usage']);
            $hourlyData['total_queries'] += $data['query_count'];
            
            if ($data['total_time'] > 2000) {
                $hourlyData['slow_requests']++;
            }
            
            // Track route performance
            $route = $data['route'];
            if (!isset($hourlyData['routes'][$route])) {
                $hourlyData['routes'][$route] = [
                    'count' => 0,
                    'total_time' => 0,
                    'avg_time' => 0,
                    'queries' => 0
                ];
            }
            
            $hourlyData['routes'][$route]['count']++;
            $hourlyData['routes'][$route]['total_time'] += $data['total_time'];
            $hourlyData['routes'][$route]['avg_time'] = $hourlyData['routes'][$route]['total_time'] / $hourlyData['routes'][$route]['count'];
            $hourlyData['routes'][$route]['queries'] += $data['query_count'];
            
            Cache::put($hourlyKey, $hourlyData, 86400); // 24 hours
            
        } catch (\Exception $e) {
            Log::error('Failed to store performance metrics', [
                'error' => $e->getMessage()
            ]);
        }
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
     * Parse bytes from formatted string
     */
    private function parseBytes(string $formatted): int
    {
        preg_match('/^([\d.]+)\s*([A-Z]*)B?$/i', $formatted, $matches);
        
        if (count($matches) < 2) {
            return 0;
        }
        
        $value = (float) $matches[1];
        $unit = strtoupper($matches[2] ?? '');
        
        $multipliers = [
            '' => 1,
            'K' => 1024,
            'M' => 1024 * 1024,
            'G' => 1024 * 1024 * 1024,
            'T' => 1024 * 1024 * 1024 * 1024,
        ];
        
        return (int) ($value * ($multipliers[$unit] ?? 1));
    }
    
    /**
     * Get performance summary
     */
    public static function getPerformanceSummary(): array
    {
        try {
            $recentMetrics = Cache::get('performance_metrics_recent', []);
            
            if (empty($recentMetrics)) {
                return [
                    'message' => 'No performance data available',
                    'recent_requests' => 0
                ];
            }
            
            $totalRequests = count($recentMetrics);
            $avgTime = array_sum(array_column($recentMetrics, 'total_time')) / $totalRequests;
            $avgQueries = array_sum(array_column($recentMetrics, 'query_count')) / $totalRequests;
            $slowRequests = count(array_filter($recentMetrics, function($metric) {
                return $metric['total_time'] > 2000;
            }));
            
            // Find slowest routes
            $routeStats = [];
            foreach ($recentMetrics as $metric) {
                $route = $metric['route'];
                if (!isset($routeStats[$route])) {
                    $routeStats[$route] = [
                        'count' => 0,
                        'total_time' => 0,
                        'max_time' => 0
                    ];
                }
                $routeStats[$route]['count']++;
                $routeStats[$route]['total_time'] += $metric['total_time'];
                $routeStats[$route]['max_time'] = max($routeStats[$route]['max_time'], $metric['total_time']);
            }
            
            // Calculate average times and sort by slowest
            foreach ($routeStats as $route => &$stats) {
                $stats['avg_time'] = $stats['total_time'] / $stats['count'];
            }
            uasort($routeStats, function($a, $b) {
                return $b['avg_time'] <=> $a['avg_time'];
            });
            
            return [
                'recent_requests' => $totalRequests,
                'avg_response_time' => round($avgTime, 2),
                'avg_queries_per_request' => round($avgQueries, 2),
                'slow_requests' => $slowRequests,
                'slow_request_percentage' => round(($slowRequests / $totalRequests) * 100, 2),
                'slowest_routes' => array_slice($routeStats, 0, 5, true),
                'last_updated' => now()->toDateTimeString()
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to get performance summary', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => 'Failed to retrieve performance data',
                'message' => $e->getMessage()
            ];
        }
    }
}