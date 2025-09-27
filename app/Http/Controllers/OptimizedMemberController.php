<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Optimized Member Controller with performance improvements
 */
class OptimizedMemberController extends Controller
{
    // Cache durations
    private const STATS_CACHE_DURATION = 300; // 5 minutes
    private const SEARCH_CACHE_DURATION = 60; // 1 minute
    private const FILTER_CACHE_DURATION = 3600; // 1 hour

    /**
     * Optimized index method with single-query aggregations
     */
    public function index(Request $request)
    {
        $cacheKey = 'members_index_' . md5(serialize($request->all()));
        
        // Cache the expensive query results
        $result = Cache::remember($cacheKey, self::SEARCH_CACHE_DURATION, function() use ($request) {
            
            // Build optimized query with proper indexes
            $query = Member::select([
                'id', 'first_name', 'middle_name', 'last_name', 
                'phone', 'email', 'membership_status', 'local_church', 
                'church_group', 'gender', 'date_of_birth', 'family_id',
                'created_at', 'updated_at'
            ]);

            // Apply filters efficiently using indexed columns
            $this->applyOptimizedFilters($query, $request);
            
            // Use indexed sorting
            $sortField = $this->validateSortField($request->get('sort', 'last_name'));
            $sortDirection = $request->get('direction', 'asc');
            $query->orderBy($sortField, $sortDirection);

            // Paginate with proper chunking
            return $query->paginate($request->get('per_page', 15))->withQueryString();
        });

        // Get stats with single optimized query
        $stats = $this->getOptimizedStats();
        
        return inertia('Members/Index', [
            'members' => $result,
            'stats' => $stats,
            'filters' => $request->only(['search', 'local_church', 'church_group', 'membership_status', 'gender']),
            'filterOptions' => $this->getCachedFilterOptions()
        ]);
    }

    /**
     * Single-query statistics with caching
     */
    public function getOptimizedStats(): array
    {
        return Cache::remember('member_stats_optimized', self::STATS_CACHE_DURATION, function() {
            $currentMonth = now()->month;
            $currentYear = now()->year;

            // Single aggregated query for all statistics
            $stats = DB::table('members')
                ->selectRaw('
                    COUNT(*) as total_members,
                    SUM(CASE WHEN membership_status = "active" THEN 1 ELSE 0 END) as active_members,
                    SUM(CASE WHEN membership_status = "inactive" THEN 1 ELSE 0 END) as inactive_members,
                    SUM(CASE WHEN membership_status = "transferred" THEN 1 ELSE 0 END) as transferred_members,
                    SUM(CASE WHEN membership_status = "deceased" THEN 1 ELSE 0 END) as deceased_members,
                    SUM(CASE WHEN CAST(strftime("%m", created_at) AS INTEGER) = ? AND CAST(strftime("%Y", created_at) AS INTEGER) = ? THEN 1 ELSE 0 END) as new_this_month,
                    SUM(CASE WHEN gender IN ("male", "Male") THEN 1 ELSE 0 END) as male_count,
                    SUM(CASE WHEN gender IN ("female", "Female") THEN 1 ELSE 0 END) as female_count
                ')
                ->addBinding([$currentMonth, $currentYear])
                ->first();

            // Church and group distributions in separate optimized queries
            $churchStats = DB::table('members')
                ->select('local_church', DB::raw('COUNT(*) as count'))
                ->whereNotNull('local_church')
                ->groupBy('local_church')
                ->pluck('count', 'local_church')
                ->toArray();

            $groupStats = DB::table('members')
                ->select('church_group', DB::raw('COUNT(*) as count'))
                ->whereNotNull('church_group')
                ->groupBy('church_group')
                ->pluck('count', 'church_group')
                ->toArray();

            return [
                'total_members' => (int) $stats->total_members,
                'active_members' => (int) $stats->active_members,
                'inactive_members' => (int) $stats->inactive_members,
                'transferred_members' => (int) $stats->transferred_members,
                'deceased_members' => (int) $stats->deceased_members,
                'new_this_month' => (int) $stats->new_this_month,
                'by_status' => [
                    'active' => (int) $stats->active_members,
                    'inactive' => (int) $stats->inactive_members,
                    'transferred' => (int) $stats->transferred_members,
                    'deceased' => (int) $stats->deceased_members,
                ],
                'by_gender' => [
                    'male' => (int) $stats->male_count,
                    'female' => (int) $stats->female_count,
                ],
                'by_church' => $churchStats,
                'by_group' => $groupStats,
                'statistics' => [
                    'total_members' => (int) $stats->total_members,
                    'active_members' => (int) $stats->active_members,
                    'inactive_members' => (int) $stats->inactive_members,
                    'transferred_members' => (int) $stats->transferred_members,
                    'deceased_members' => (int) $stats->deceased_members,
                ]
            ];
        });
    }

    /**
     * Optimized filter application using indexed columns
     */
    private function applyOptimizedFilters($query, Request $request): void
    {
        // Search optimization with full-text index when available
        if ($request->filled('search')) {
            $search = $request->get('search');
            
            // Use full-text search for MySQL, fallback to LIKE for others
            if (DB::connection()->getDriverName() === 'mysql') {
                $query->whereRaw('MATCH(first_name, middle_name, last_name) AGAINST(? IN NATURAL LANGUAGE MODE)', [$search])
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('id_number', 'like', "%{$search}%");
            } else {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('middle_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('id_number', 'like', "%{$search}%");
                });
            }
        }

        // Apply indexed filters
        if ($request->filled('local_church')) {
            $query->where('local_church', $request->get('local_church'));
        }

        if ($request->filled('church_group')) {
            $query->where('church_group', $request->get('church_group'));
        }

        if ($request->filled('membership_status')) {
            $query->where('membership_status', $request->get('membership_status'));
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->get('gender'));
        }

        // Optimized age group filtering using indexed date_of_birth
        if ($request->filled('age_group')) {
            $ageGroup = $request->get('age_group');
            $today = now();
            
            match($ageGroup) {
                'children' => $query->where('date_of_birth', '>', $today->copy()->subYears(18)),
                'youth' => $query->whereBetween('date_of_birth', [
                    $today->copy()->subYears(30), 
                    $today->copy()->subYears(18)
                ]),
                'adults' => $query->whereBetween('date_of_birth', [
                    $today->copy()->subYears(60), 
                    $today->copy()->subYears(30)
                ]),
                'seniors' => $query->where('date_of_birth', '<=', $today->copy()->subYears(60)),
                default => null
            };
        }
    }

    /**
     * Cached filter options to reduce repeated queries
     */
    private function getCachedFilterOptions(): array
    {
        return Cache::remember('member_filter_options', self::FILTER_CACHE_DURATION, function() {
            return [
                'local_churches' => Member::distinct('local_church')
                    ->whereNotNull('local_church')
                    ->orderBy('local_church')
                    ->pluck('local_church')
                    ->values()
                    ->toArray(),
                'church_groups' => Member::distinct('church_group')
                    ->whereNotNull('church_group')
                    ->orderBy('church_group')
                    ->pluck('church_group')
                    ->values()
                    ->toArray(),
                'membership_statuses' => ['active', 'inactive', 'transferred', 'deceased'],
                'genders' => ['male', 'female'],
                'age_groups' => ['children', 'youth', 'adults', 'seniors']
            ];
        });
    }

    /**
     * Optimized member search with debouncing and caching
     */
    public function quickSearch(Request $request)
    {
        $search = $request->get('query', '');
        $limit = $request->get('limit', 10);

        if (strlen($search) < 2) {
            return response()->json(['data' => []]);
        }

        $cacheKey = "member_search_" . md5($search . $limit);

        $results = Cache::remember($cacheKey, 30, function() use ($search, $limit) {
            $query = Member::select(['id', 'first_name', 'last_name', 'phone', 'email', 'membership_status']);

            // Use full-text search when available
            if (DB::connection()->getDriverName() === 'mysql') {
                $query->whereRaw('MATCH(first_name, last_name) AGAINST(? IN NATURAL LANGUAGE MODE)', [$search]);
            } else {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            return $query->limit($limit)->get();
        });

        return response()->json(['data' => $results]);
    }

    /**
     * Bulk operations with transaction optimization
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'member_ids' => 'required|array',
            'member_ids.*' => 'exists:members,id',
            'updates' => 'required|array'
        ]);

        DB::beginTransaction();
        try {
            // Use chunk processing for large bulk operations
            collect($request->member_ids)->chunk(100)->each(function ($chunk) use ($request) {
                Member::whereIn('id', $chunk)->update($request->updates);
            });

            DB::commit();

            // Clear relevant caches
            Cache::tags(['member_stats', 'member_filters'])->flush();

            return redirect()->back()->with('success', 'Members updated successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Bulk member update failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Bulk update failed. Please try again.');
        }
    }

    private function validateSortField(string $field): string
    {
        $allowedFields = [
            'first_name', 'last_name', 'email', 'phone', 'membership_status',
            'local_church', 'church_group', 'created_at', 'updated_at'
        ];

        return in_array($field, $allowedFields) ? $field : 'last_name';
    }
}
