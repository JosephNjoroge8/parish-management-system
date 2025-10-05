<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MemberSearchService
{
    private const CACHE_TTL = 300; // 5 minutes cache for search results

    private const MAX_RESULTS = 1000; // Limit results for performance

    /**
     * Perform real-time search with caching and optimization
     */
    public function search(array $filters, int $perPage = 25): array
    {
        $cacheKey = $this->generateCacheKey($filters, $perPage);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters, $perPage) {
            return $this->performSearch($filters, $perPage);
        });
    }

    /**
     * Real-time search without caching (for live updates)
     */
    public function liveSearch(array $filters, int $perPage = 25): array
    {
        return $this->performSearch($filters, $perPage);
    }

    /**
     * Advanced search with multiple search mechanisms
     */
    public function advancedSearch(array $filters, int $perPage = 25): array
    {
        $query = Member::query()
            ->select([
                'id', 'first_name', 'middle_name', 'last_name', 'date_of_birth',
                'gender', 'phone', 'email', 'local_church', 'church_group',
                'small_christian_community', 'membership_status', 'matrimony_status',
                'id_number', 'tribe', 'occupation', 'education_level',
            ]);

        // Apply search mechanisms
        $this->applyTextSearch($query, $filters);
        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        // Add performance optimizations
        $query->with(['family:id,family_name,family_code']);

        return [
            'data' => $query->paginate($perPage)->withQueryString(),
            'filters' => $this->getAvailableFilters(),
            'stats' => $this->getSearchStats($query->clone()),
        ];
    }

    /**
     * Real-time autocomplete search
     */
    public function autocomplete(string $search, int $limit = 10): array
    {
        if (strlen($search) < 2) {
            return [];
        }

        $cacheKey = 'autocomplete:'.md5($search);

        return Cache::remember($cacheKey, 60, function () use ($search, $limit) {
            return Member::query()
                ->select(['id', 'first_name', 'middle_name', 'last_name', 'phone', 'local_church'])
                ->search($search)
                ->limit($limit)
                ->get()
                ->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'label' => $member->full_name,
                        'subtitle' => $member->local_church,
                        'phone' => $member->phone,
                    ];
                })->toArray();
        });
    }

    /**
     * Search by multiple criteria simultaneously
     */
    public function multiCriteriaSearch(array $criteria): array
    {
        $queries = [];

        // Name search
        if (! empty($criteria['name'])) {
            $queries['name'] = Member::search($criteria['name'])
                ->select(['id', 'first_name', 'middle_name', 'last_name', 'local_church'])
                ->limit(50)
                ->get();
        }

        // Phone search
        if (! empty($criteria['phone'])) {
            $queries['phone'] = Member::where('phone', 'like', "%{$criteria['phone']}%")
                ->select(['id', 'first_name', 'middle_name', 'last_name', 'phone', 'local_church'])
                ->limit(50)
                ->get();
        }

        // ID number search
        if (! empty($criteria['id_number'])) {
            $queries['id_number'] = Member::where('id_number', 'like', "%{$criteria['id_number']}%")
                ->select(['id', 'first_name', 'middle_name', 'last_name', 'id_number', 'local_church'])
                ->limit(50)
                ->get();
        }

        // Church-based search
        if (! empty($criteria['church'])) {
            $queries['church'] = Member::byChurch($criteria['church'])
                ->select(['id', 'first_name', 'middle_name', 'last_name', 'local_church', 'church_group'])
                ->limit(100)
                ->get();
        }

        return $queries;
    }

    /**
     * Get members by various search mechanisms
     */
    public function searchByMechanism(string $mechanism, string $value): array
    {
        return match ($mechanism) {
            'name' => $this->searchByName($value),
            'phone' => $this->searchByPhone($value),
            'id_number' => $this->searchByIdNumber($value),
            'email' => $this->searchByEmail($value),
            'church' => $this->searchByChurch($value),
            'group' => $this->searchByGroup($value),
            'community' => $this->searchByCommunity($value),
            'family' => $this->searchByFamily($value),
            default => []
        };
    }

    /**
     * Private methods for specific search mechanisms
     */
    private function searchByName(string $name): array
    {
        return Member::search($name)
            ->with(['family:id,family_name'])
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'local_church', 'family_id'])
            ->toArray();
    }

    private function searchByPhone(string $phone): array
    {
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);

        return Member::where('phone', 'like', "%{$cleanPhone}%")
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'phone', 'local_church'])
            ->toArray();
    }

    private function searchByIdNumber(string $idNumber): array
    {
        return Member::where('id_number', 'like', "%{$idNumber}%")
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'id_number', 'local_church'])
            ->toArray();
    }

    private function searchByEmail(string $email): array
    {
        return Member::where('email', 'like', "%{$email}%")
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'email', 'local_church'])
            ->toArray();
    }

    private function searchByChurch(string $church): array
    {
        return Member::byChurch($church)
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'local_church', 'church_group'])
            ->toArray();
    }

    private function searchByGroup(string $group): array
    {
        return Member::byGroup($group)
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'church_group', 'local_church'])
            ->toArray();
    }

    private function searchByCommunity(string $community): array
    {
        return Member::where('small_christian_community', 'like', "%{$community}%")
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'small_christian_community', 'local_church'])
            ->toArray();
    }

    private function searchByFamily(string $family): array
    {
        return Member::whereHas('family', function ($query) use ($family) {
            $query->where('family_name', 'like', "%{$family}%")
                ->orWhere('family_code', 'like', "%{$family}%");
        })
            ->with(['family:id,family_name,family_code'])
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'local_church', 'family_id'])
            ->toArray();
    }

    /**
     * Apply text search to query
     */
    private function applyTextSearch(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }
    }

    /**
     * Apply various filters to query
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $filterMethods = [
            'local_church' => 'byChurch',
            'church_group' => 'byGroup',
            'gender' => 'byGender',
            'age_group' => 'byAgeGroup',
            'membership_status' => function ($query, $value) {
                $query->where('membership_status', $value);
            },
            'matrimony_status' => function ($query, $value) {
                $query->where('matrimony_status', $value);
            },
            'education_level' => function ($query, $value) {
                $query->where('education_level', $value);
            },
            'tribe' => function ($query, $value) {
                $query->where('tribe', 'like', "%{$value}%");
            },
        ];

        foreach ($filterMethods as $filter => $method) {
            if (! empty($filters[$filter])) {
                if (is_callable($method)) {
                    $method($query, $filters[$filter]);
                } else {
                    $query->$method($filters[$filter]);
                }
            }
        }

        // Date range filters
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Age range filters
        if (! empty($filters['age_from']) || ! empty($filters['age_to'])) {
            $this->applyAgeRangeFilter($query, $filters);
        }
    }

    /**
     * Apply age range filter
     */
    private function applyAgeRangeFilter(Builder $query, array $filters): void
    {
        $today = now()->format('Y-m-d');

        if (! empty($filters['age_from'])) {
            $maxBirthDate = now()->subYears($filters['age_from'])->format('Y-m-d');
            $query->where('date_of_birth', '<=', $maxBirthDate);
        }

        if (! empty($filters['age_to'])) {
            $minBirthDate = now()->subYears($filters['age_to'] + 1)->addDay()->format('Y-m-d');
            $query->where('date_of_birth', '>=', $minBirthDate);
        }
    }

    /**
     * Apply sorting to query
     */
    private function applySorting(Builder $query, array $filters): void
    {
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';

        $allowedSortFields = [
            'first_name', 'last_name', 'date_of_birth', 'created_at',
            'membership_date', 'local_church', 'church_group',
        ];

        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Secondary sort by name for consistency
        if ($sortField !== 'first_name') {
            $query->orderBy('first_name', 'asc');
        }
    }

    /**
     * Get available filter options
     */
    private function getAvailableFilters(): array
    {
        return Cache::remember('member_filters', 3600, function () {
            return [
                'churches' => Member::distinct('local_church')
                    ->whereNotNull('local_church')
                    ->pluck('local_church')
                    ->sort()
                    ->values(),
                'groups' => array_keys(Member::CHURCH_GROUPS),
                'education_levels' => array_keys(Member::EDUCATION_LEVELS),
                'membership_statuses' => array_keys(Member::MEMBERSHIP_STATUSES),
                'matrimony_statuses' => array_keys(Member::MATRIMONY_STATUSES),
                'tribes' => Member::distinct('tribe')
                    ->whereNotNull('tribe')
                    ->pluck('tribe')
                    ->sort()
                    ->values(),
            ];
        });
    }

    /**
     * Get search statistics
     */
    private function getSearchStats(Builder $query): array
    {
        $baseQuery = $query->getQuery();

        return [
            'total_results' => $query->count(),
            'gender_distribution' => $query->clone()
                ->select('gender', DB::raw('count(*) as count'))
                ->groupBy('gender')
                ->pluck('count', 'gender'),
            'church_distribution' => $query->clone()
                ->select('local_church', DB::raw('count(*) as count'))
                ->groupBy('local_church')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'local_church'),
            'age_groups' => $this->getAgeGroupStats($query->clone()),
        ];
    }

    /**
     * Get age group statistics
     */
    private function getAgeGroupStats(Builder $query): array
    {
        $today = now()->format('Y-m-d');

        return [
            'children' => $query->clone()->whereRaw("TIMESTAMPDIFF(YEAR, date_of_birth, '{$today}') BETWEEN 0 AND 12")->count(),
            'youth' => $query->clone()->whereRaw("TIMESTAMPDIFF(YEAR, date_of_birth, '{$today}') BETWEEN 13 AND 24")->count(),
            'adults' => $query->clone()->whereRaw("TIMESTAMPDIFF(YEAR, date_of_birth, '{$today}') BETWEEN 25 AND 59")->count(),
            'seniors' => $query->clone()->whereRaw("TIMESTAMPDIFF(YEAR, date_of_birth, '{$today}') >= 60")->count(),
        ];
    }

    /**
     * Perform the actual search
     */
    private function performSearch(array $filters, int $perPage): array
    {
        return $this->advancedSearch($filters, $perPage);
    }

    /**
     * Generate cache key for search
     */
    private function generateCacheKey(array $filters, int $perPage): string
    {
        return 'member_search:'.md5(serialize($filters).$perPage);
    }

    /**
     * Clear search cache
     */
    public function clearSearchCache(): void
    {
        Cache::flush(); // In production, you'd want more selective cache clearing
    }
}
