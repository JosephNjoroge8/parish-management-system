<?php

namespace App\Services;

use App\Exports\MembersExport;
use App\Models\Member;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class ReportingService
{
    private const CACHE_TTL = 1800; // 30 minutes

    /**
     * Get comprehensive member reports with preview and download options
     */
    public function getMemberReport(array $filters = []): array
    {
        $cacheKey = 'member_report:'.md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $query = $this->buildMemberQuery($filters);

            return [
                'preview' => $this->getReportPreview($query->clone(), $filters),
                'summary' => $this->getReportSummary($query->clone()),
                'charts' => $this->getReportCharts($query->clone()),
                'filters' => $filters,
                'total_count' => $query->count(),
                'generated_at' => now()->format('Y-m-d H:i:s'),
            ];
        });
    }

    /**
     * World-class PMC members report by church
     */
    public function getPMCMembersReport(?string $church = null): array
    {
        $query = Member::byGroup('PMC');

        if ($church) {
            $query->byChurch($church);
        }

        $members = $query->with(['family'])
            ->orderBy('local_church')
            ->orderBy('first_name')
            ->get();

        return [
            'title' => $church ? "PMC Members - {$church}" : 'PMC Members - All Churches',
            'members' => $members->map(function ($member) {
                return [
                    'id' => $member->id,
                    'full_name' => $member->full_name,
                    'age' => $member->age,
                    'gender' => $member->gender,
                    'local_church' => $member->local_church,
                    'small_christian_community' => $member->small_christian_community,
                    'family_name' => $member->family?->family_name,
                    'phone' => $member->phone,
                    'baptism_date' => $member->baptism_date?->format('Y-m-d'),
                    'confirmation_date' => $member->confirmation_date?->format('Y-m-d'),
                ];
            }),
            'summary' => [
                'total_count' => $members->count(),
                'by_church' => $members->groupBy('local_church')->map->count(),
                'by_gender' => $members->groupBy('gender')->map->count(),
                'by_age_group' => $this->groupByAgeGroup($members),
                'baptized_count' => $members->whereNotNull('baptism_date')->count(),
                'confirmed_count' => $members->whereNotNull('confirmation_date')->count(),
            ],
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Married members report with comprehensive details
     */
    public function getMarriedMembersReport(?string $church = null): array
    {
        $query = Member::where('matrimony_status', 'married');

        if ($church) {
            $query->byChurch($church);
        }

        $members = $query->with(['family'])
            ->orderBy('local_church')
            ->orderBy('marriage_date', 'desc')
            ->get();

        return [
            'title' => $church ? "Married Members - {$church}" : 'Married Members - All Churches',
            'members' => $members->map(function ($member) {
                return [
                    'id' => $member->id,
                    'full_name' => $member->full_name,
                    'spouse_name' => $member->spouse_name,
                    'marriage_date' => $member->marriage_date?->format('Y-m-d'),
                    'marriage_type' => $member->marriage_type,
                    'marriage_location' => $member->marriage_location,
                    'local_church' => $member->local_church,
                    'years_married' => $member->marriage_date ?
                        now()->diffInYears($member->marriage_date) : null,
                    'phone' => $member->phone,
                    'family_name' => $member->family?->family_name,
                ];
            }),
            'summary' => [
                'total_count' => $members->count(),
                'by_church' => $members->groupBy('local_church')->map->count(),
                'by_marriage_type' => $members->groupBy('marriage_type')->map->count(),
                'by_marriage_year' => $members->groupBy(function ($member) {
                    return $member->marriage_date?->year;
                })->map->count(),
                'recent_marriages' => $members->where('marriage_date', '>=', now()->subYear())->count(),
            ],
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Community group analysis report
     */
    public function getCommunityGroupReport(?string $groupName = null): array
    {
        $query = Member::query();

        if ($groupName) {
            $query->byGroup($groupName);
        }

        $members = $query->with(['family'])
            ->orderBy('church_group')
            ->orderBy('local_church')
            ->orderBy('first_name')
            ->get();

        $groupedMembers = $members->groupBy('church_group');

        return [
            'title' => $groupName ? "Group Report - {$groupName}" : 'All Community Groups Report',
            'groups' => $groupedMembers->map(function ($groupMembers, $groupName) {
                return [
                    'group_name' => $groupName,
                    'total_members' => $groupMembers->count(),
                    'by_church' => $groupMembers->groupBy('local_church')->map->count(),
                    'by_gender' => $groupMembers->groupBy('gender')->map->count(),
                    'age_distribution' => $this->groupByAgeGroup($groupMembers),
                    'members' => $groupMembers->map(function ($member) {
                        return [
                            'id' => $member->id,
                            'full_name' => $member->full_name,
                            'age' => $member->age,
                            'gender' => $member->gender,
                            'local_church' => $member->local_church,
                            'phone' => $member->phone,
                            'membership_date' => $member->membership_date?->format('Y-m-d'),
                        ];
                    }),
                ];
            }),
            'overall_summary' => [
                'total_members' => $members->count(),
                'total_groups' => $groupedMembers->count(),
                'largest_group' => $groupedMembers->sortByDesc(function ($group) {
                    return $group->count();
                })->keys()->first(),
                'most_active_church' => $members->groupBy('local_church')
                    ->sortByDesc(function ($church) {
                        return $church->count();
                    })
                    ->keys()->first(),
            ],
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Age group analysis
     */
    public function getAgeGroupReport(): array
    {
        $members = Member::whereNotNull('date_of_birth')
            ->with(['family'])
            ->get();

        $ageGroups = [
            'children' => $members->filter(fn ($m) => $m->age >= 0 && $m->age <= 12),
            'youth' => $members->filter(fn ($m) => $m->age >= 13 && $m->age <= 24),
            'adults' => $members->filter(fn ($m) => $m->age >= 25 && $m->age <= 59),
            'seniors' => $members->filter(fn ($m) => $m->age >= 60),
        ];

        return [
            'title' => 'Age Group Distribution Report',
            'age_groups' => collect($ageGroups)->map(function ($members, $groupName) {
                return [
                    'group_name' => ucfirst($groupName),
                    'total_count' => $members->count(),
                    'by_gender' => $members->groupBy('gender')->map->count(),
                    'by_church' => $members->groupBy('local_church')->map->count(),
                    'percentage' => round(($members->count() / Member::count()) * 100, 2),
                ];
            }),
            'summary' => [
                'total_members' => $members->count(),
                'average_age' => round($members->avg('age'), 1),
                'youngest_member' => $members->sortBy('age')->first()?->age,
                'oldest_member' => $members->sortByDesc('age')->first()?->age,
            ],
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Sacrament completion report
     */
    public function getSacramentReport(): array
    {
        $members = Member::with(['family'])->get();

        return [
            'title' => 'Sacrament Completion Report',
            'sacraments' => [
                'baptism' => [
                    'completed' => $members->whereNotNull('baptism_date')->count(),
                    'pending' => $members->whereNull('baptism_date')->count(),
                    'by_church' => $members->whereNotNull('baptism_date')
                        ->groupBy('local_church')->map->count(),
                    'recent' => $members->where('baptism_date', '>=', now()->subYear())
                        ->whereNotNull('baptism_date')->count(),
                ],
                'confirmation' => [
                    'completed' => $members->whereNotNull('confirmation_date')->count(),
                    'pending' => $members->whereNull('confirmation_date')->count(),
                    'by_church' => $members->whereNotNull('confirmation_date')
                        ->groupBy('local_church')->map->count(),
                    'recent' => $members->where('confirmation_date', '>=', now()->subYear())
                        ->whereNotNull('confirmation_date')->count(),
                ],
                'eucharist' => [
                    'completed' => $members->whereNotNull('eucharist_date')->count(),
                    'pending' => $members->whereNull('eucharist_date')->count(),
                    'by_church' => $members->whereNotNull('eucharist_date')
                        ->groupBy('local_church')->map->count(),
                    'recent' => $members->where('eucharist_date', '>=', now()->subYear())
                        ->whereNotNull('eucharist_date')->count(),
                ],
                'marriage' => [
                    'completed' => $members->whereNotNull('marriage_date')->count(),
                    'by_type' => $members->whereNotNull('marriage_date')
                        ->groupBy('marriage_type')->map->count(),
                    'by_church' => $members->whereNotNull('marriage_date')
                        ->groupBy('local_church')->map->count(),
                    'recent' => $members->where('marriage_date', '>=', now()->subYear())
                        ->whereNotNull('marriage_date')->count(),
                ],
            ],
            'completion_analysis' => [
                'all_sacraments' => $members->filter(function ($member) {
                    return $member->baptism_date && $member->confirmation_date && $member->eucharist_date;
                })->count(),
                'incomplete_sacraments' => $members->filter(function ($member) {
                    return ! $member->baptism_date || ! $member->confirmation_date || ! $member->eucharist_date;
                })->count(),
            ],
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Generate downloadable report
     */
    public function generateDownloadableReport(string $reportType, array $filters = []): string
    {
        $reportData = match ($reportType) {
            'pmc_members' => $this->getPMCMembersReport($filters['church'] ?? null),
            'married_members' => $this->getMarriedMembersReport($filters['church'] ?? null),
            'community_groups' => $this->getCommunityGroupReport($filters['group'] ?? null),
            'age_groups' => $this->getAgeGroupReport(),
            'sacraments' => $this->getSacramentReport(),
            'comprehensive' => $this->getMemberReport($filters),
            default => $this->getMemberReport($filters),
        };

        // Generate Excel file
        $fileName = $reportType.'_report_'.now()->format('Y_m_d_H_i_s').'.xlsx';

        Excel::store(new MembersExport($reportData), $fileName, 'public');

        return $fileName;
    }

    /**
     * Private helper methods
     */
    private function buildMemberQuery(array $filters)
    {
        $query = Member::query();

        foreach ($filters as $key => $value) {
            if (empty($value)) {
                continue;
            }

            match ($key) {
                'local_church' => $query->byChurch($value),
                'church_group' => $query->byGroup($value),
                'gender' => $query->byGender($value),
                'membership_status' => $query->where('membership_status', $value),
                'matrimony_status' => $query->where('matrimony_status', $value),
                'age_group' => $query->byAgeGroup($value),
                'date_from' => $query->where('created_at', '>=', $value),
                'date_to' => $query->where('created_at', '<=', $value),
                default => null,
            };
        }

        return $query;
    }

    private function getReportPreview($query, array $filters, int $limit = 50)
    {
        return $query->with(['family'])
            ->limit($limit)
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'full_name' => $member->full_name,
                    'age' => $member->age,
                    'gender' => $member->gender,
                    'local_church' => $member->local_church,
                    'church_group' => $member->church_group,
                    'membership_status' => $member->membership_status,
                    'phone' => $member->phone,
                    'family_name' => $member->family?->family_name,
                ];
            });
    }

    private function getReportSummary($query)
    {
        $members = $query->get();

        return [
            'total_count' => $members->count(),
            'by_gender' => $members->groupBy('gender')->map->count(),
            'by_church' => $members->groupBy('local_church')->map->count(),
            'by_group' => $members->groupBy('church_group')->map->count(),
            'by_status' => $members->groupBy('membership_status')->map->count(),
            'age_distribution' => $this->groupByAgeGroup($members),
        ];
    }

    private function getReportCharts($query)
    {
        $members = $query->get();

        return [
            'gender_pie' => $members->groupBy('gender')->map->count(),
            'church_bar' => $members->groupBy('local_church')->map->count(),
            'age_group_bar' => $this->groupByAgeGroup($members),
            'membership_trend' => $this->getMembershipTrend($members),
        ];
    }

    private function groupByAgeGroup($members)
    {
        return [
            'children' => $members->filter(fn ($m) => $m->age >= 0 && $m->age <= 12)->count(),
            'youth' => $members->filter(fn ($m) => $m->age >= 13 && $m->age <= 24)->count(),
            'adults' => $members->filter(fn ($m) => $m->age >= 25 && $m->age <= 59)->count(),
            'seniors' => $members->filter(fn ($m) => $m->age >= 60)->count(),
        ];
    }

    private function getMembershipTrend($members)
    {
        return $members->groupBy(function ($member) {
            return $member->created_at->format('Y-m');
        })->map->count();
    }

    /**
     * Clear report cache
     */
    public function clearReportCache(): void
    {
        Cache::tags(['reports'])->flush();
    }
}
