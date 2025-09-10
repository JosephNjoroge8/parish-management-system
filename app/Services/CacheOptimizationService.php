<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Advanced caching service for parish system performance optimization
 */
class CacheOptimizationService
{
    // Cache durations in seconds
    const CACHE_DURATIONS = [
        'stats' => 300,        // 5 minutes
        'filters' => 3600,     // 1 hour
        'search' => 60,        // 1 minute
        'reports' => 1800,     // 30 minutes
        'dashboard' => 120,    // 2 minutes
    ];

    // Cache tags for organized cache management
    const CACHE_TAGS = [
        'members' => 'members',
        'families' => 'families',
        'sacraments' => 'sacraments',
        'activities' => 'activities',
        'tithes' => 'tithes',
        'stats' => 'stats',
        'dashboard' => 'dashboard',
    ];

    /**
     * Get cached statistics with automatic refresh
     */
    public static function getCachedStats(string $type = 'general'): array
    {
        $cacheKey = "optimized_stats_{$type}";

        try {
            // Try with tags first
            return Cache::tags([self::CACHE_TAGS['stats']])
                ->remember($cacheKey, self::CACHE_DURATIONS['stats'], function() use ($type) {
                    return self::generateOptimizedStats($type);
                });
        } catch (\Exception $e) {
            // Fallback to regular cache if tags are not supported
            return Cache::remember($cacheKey, self::CACHE_DURATIONS['stats'], function() use ($type) {
                return self::generateOptimizedStats($type);
            });
        }
    }

    /**
     * Generate optimized statistics using single queries
     */
    private static function generateOptimizedStats(string $type): array
    {
        try {
            $currentMonth = now()->month;
            $currentYear = now()->year;

            switch ($type) {
                case 'dashboard':
                    return self::getDashboardStats($currentMonth, $currentYear);
                case 'members':
                    return self::getMemberStats($currentMonth, $currentYear);
                case 'financial':
                    return self::getFinancialStats($currentMonth, $currentYear);
                default:
                    return self::getGeneralStats($currentMonth, $currentYear);
            }
        } catch (\Exception $e) {
            Log::error('Cache optimization stats generation failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Dashboard statistics - single optimized query
     */
    private static function getDashboardStats(int $month, int $year): array
    {
        $stats = DB::select("
            SELECT 
                -- Member statistics
                (SELECT COUNT(*) FROM members) as total_members,
                (SELECT COUNT(*) FROM members WHERE membership_status = 'active') as active_members,
                (SELECT COUNT(*) FROM members WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?) as new_members_this_month,
                
                -- Family statistics  
                (SELECT COUNT(*) FROM families) as total_families,
                (SELECT COUNT(*) FROM families WHERE EXISTS(SELECT 1 FROM members WHERE family_id = families.id AND membership_status = 'active')) as active_families,
                
                -- Financial statistics
                (SELECT COALESCE(SUM(amount), 0) FROM tithes WHERE MONTH(date_given) = ? AND YEAR(date_given) = ?) as tithes_this_month,
                (SELECT COALESCE(SUM(amount), 0) FROM tithes WHERE YEAR(date_given) = ?) as tithes_this_year,
                
                -- Activity statistics
                (SELECT COUNT(*) FROM activities WHERE status IN ('planned', 'active')) as upcoming_activities,
                (SELECT COUNT(*) FROM sacraments WHERE MONTH(sacrament_date) = ? AND YEAR(sacrament_date) = ?) as sacraments_this_month
        ", [$month, $year, $month, $year, $year, $month, $year]);

        return (array) $stats[0];
    }

    /**
     * Member-specific statistics
     */
    private static function getMemberStats(int $month, int $year): array
    {
        $result = DB::table('members')
            ->selectRaw('
                COUNT(*) as total_members,
                SUM(CASE WHEN membership_status = "active" THEN 1 ELSE 0 END) as active_members,
                SUM(CASE WHEN membership_status = "inactive" THEN 1 ELSE 0 END) as inactive_members,
                SUM(CASE WHEN membership_status = "transferred" THEN 1 ELSE 0 END) as transferred_members,
                SUM(CASE WHEN membership_status = "deceased" THEN 1 ELSE 0 END) as deceased_members,
                SUM(CASE WHEN MONTH(created_at) = ? AND YEAR(created_at) = ? THEN 1 ELSE 0 END) as new_this_month,
                SUM(CASE WHEN gender IN ("male", "Male") THEN 1 ELSE 0 END) as male_count,
                SUM(CASE WHEN gender IN ("female", "Female") THEN 1 ELSE 0 END) as female_count
            ')
            ->addBinding([$month, $year])
            ->first();

        return $result ? (array) $result : [];
    }

    /**
     * Financial statistics
     */
    private static function getFinancialStats(int $month, int $year): array
    {
        $result = DB::table('tithes')
            ->selectRaw('
                COALESCE(SUM(CASE WHEN MONTH(date_given) = ? AND YEAR(date_given) = ? THEN amount END), 0) as total_this_month,
                COALESCE(SUM(CASE WHEN YEAR(date_given) = ? THEN amount END), 0) as total_this_year,
                COALESCE(AVG(CASE WHEN MONTH(date_given) = ? AND YEAR(date_given) = ? THEN amount END), 0) as avg_this_month,
                COUNT(DISTINCT CASE WHEN MONTH(date_given) = ? AND YEAR(date_given) = ? THEN member_id END) as contributors_this_month
            ')
            ->addBinding([$month, $year, $year, $month, $year, $month, $year])
            ->first();

        return $result ? (array) $result : [];
    }

    /**
     * General system statistics
     */
    private static function getGeneralStats(int $month, int $year): array
    {
        // Use a single complex query to get all basic stats
        $result = DB::selectOne("
            SELECT 
                (SELECT COUNT(*) FROM members) as total_members,
                (SELECT COUNT(*) FROM families) as total_families,
                (SELECT COUNT(*) FROM sacraments) as total_sacraments,
                (SELECT COUNT(*) FROM activities) as total_activities,
                (SELECT COALESCE(SUM(amount), 0) FROM tithes) as total_tithes
        ");

        return (array) $result;
    }

    /**
     * Clear specific cache tags
     */
    public static function clearCache(array $tags = []): void
    {
        if (empty($tags)) {
            Cache::flush();
            return;
        }

        try {
            foreach ($tags as $tag) {
                if (isset(self::CACHE_TAGS[$tag])) {
                    Cache::tags([self::CACHE_TAGS[$tag]])->flush();
                }
            }
        } catch (\Exception $e) {
            // Fallback to flushing all cache if tags not supported
            Log::info('Cache tags not supported, flushing all cache instead');
            Cache::flush();
        }
    }

    /**
     * Cache search results with automatic expiration
     */
    public static function cacheSearchResults(string $query, callable $searchFunction, int $duration = null): mixed
    {
        $cacheKey = 'search_' . md5($query);
        $duration = $duration ?? self::CACHE_DURATIONS['search'];

        return Cache::remember($cacheKey, $duration, $searchFunction);
    }

    /**
     * Preload commonly accessed data into cache
     */
    public static function warmupCache(): void
    {
        try {
            // Warm up stats cache
            self::getCachedStats('dashboard');
            self::getCachedStats('members');
            self::getCachedStats('financial');

            // Warm up filter options
            Cache::remember('filter_options_members', self::CACHE_DURATIONS['filters'], function() {
                return [
                    'churches' => DB::table('members')->distinct()->pluck('local_church')->filter()->values(),
                    'groups' => DB::table('members')->distinct()->pluck('church_group')->filter()->values(),
                    'statuses' => ['active', 'inactive', 'transferred', 'deceased']
                ];
            });

            Log::info('Cache warmup completed successfully');
        } catch (\Exception $e) {
            Log::error('Cache warmup failed: ' . $e->getMessage());
        }
    }

    /**
     * Get cache statistics for monitoring
     */
    public static function getCacheStats(): array
    {
        // This would depend on your cache driver
        // For Redis, you could get memory usage, hit rate, etc.
        return [
            'cache_driver' => config('cache.default'),
            'cache_prefix' => config('cache.prefix'),
            'last_warmup' => Cache::get('last_cache_warmup', 'Never'),
        ];
    }

    /**
     * Schedule automatic cache clearing
     */
    public static function scheduleAutoClear(): void
    {
        // Clear member-related caches when data changes
        Cache::tags([self::CACHE_TAGS['members']])->flush();
        Cache::tags([self::CACHE_TAGS['stats']])->flush();
        Cache::tags([self::CACHE_TAGS['dashboard']])->flush();
    }
}
