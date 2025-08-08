<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MembersExport;
use App\Exports\MarriagesExport;
use App\Exports\SacramentsExport;
use App\Exports\TithesExport;
use App\Exports\AllDataExport;

class ReportController extends Controller
{
    public function index()
    {
        $statistics = $this->getParishStatistics();
        $chartData = $this->generateChartData();
        
        return Inertia::render('Reports/Index', [
            'statistics' => $statistics,
            'charts' => $chartData,
            'filters' => $this->getAvailableFilters()
        ]);
    }

    public function exportAll()
    {
        return Excel::download(new AllDataExport(), 'parish-report-all-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportMembers(Request $request)
    {
        $period = $request->get('period', 'all');
        return Excel::download(new MembersExport($period), 'members-' . $period . '-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function statistics()
    {
        return response()->json($this->getParishStatistics());
    }

    private function getParishStatistics($period = 'all', $startDate = null, $endDate = null)
    {
        $baseQuery = Member::query();
        $totalMembers = $baseQuery->count();
        $dateFilteredQuery = $this->applyDateFilter(clone $baseQuery, $period, $startDate, $endDate);
        
        return [
            'overview' => [
                'total_members' => $totalMembers,
                'new_members' => $dateFilteredQuery->count(),
                // Remove status-based queries since status column doesn't exist
                'active_members' => $totalMembers, // Use total as active
                'inactive_members' => 0, // Set to 0 since no status column
                'growth_rate' => $this->calculateGrowthRate($period),
            ],
            'demographics' => $this->getDemographics(),
            'sacraments' => $this->getSacramentalStatistics(),
            'recent_activity' => $this->getRecentActivity(),
            'period_info' => [
                'period' => $period,
                'generated_at' => now()->toDateTimeString(),
            ]
        ];
    }

    private function applyDateFilter($query, $period, $startDate = null, $endDate = null)
    {
        if ($startDate && $endDate) {
            return $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $now = Carbon::now();
        
        switch ($period) {
            case 'today':
                return $query->whereDate('created_at', $now->toDateString());
            case 'this_week':
                return $query->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()]);
            case 'this_month':
                return $query->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year);
            case 'this_year':
                return $query->whereYear('created_at', $now->year);
            default:
                return $query;
        }
    }

    private function getDemographics()
    {
        $demographics = [];
        
        // Only include age groups if date_of_birth column exists
        if (Schema::hasColumn('members', 'date_of_birth')) {
            $demographics['age_groups'] = [
                'children' => Member::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18')->count(),
                'youth' => Member::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 35')->count(),
                'adults' => Member::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 36 AND 60')->count(),
                'seniors' => Member::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) > 60')->count(),
            ];
        } else {
            $demographics['age_groups'] = [
                'children' => 0,
                'youth' => 0,
                'adults' => 0,
                'seniors' => 0,
            ];
        }

        // Only include gender distribution if gender column exists
        if (Schema::hasColumn('members', 'gender')) {
            $demographics['gender_distribution'] = [
                'male' => Member::where('gender', 'male')->count(),
                'female' => Member::where('gender', 'female')->count(),
            ];
        } else {
            $demographics['gender_distribution'] = [
                'male' => 0,
                'female' => 0,
            ];
        }

        // Only include marital status if marital_status column exists
        if (Schema::hasColumn('members', 'marital_status')) {
            $demographics['marital_status'] = Member::select('marital_status', DB::raw('count(*) as count'))
                ->groupBy('marital_status')
                ->pluck('count', 'marital_status')
                ->toArray();
        } else {
            $demographics['marital_status'] = [];
        }
        
        return $demographics;
    }

    private function getSacramentalStatistics()
    {
        $fields = ['baptism_date', 'confirmation_date', 'first_communion_date'];
        $sacraments = [];
        
        foreach ($fields as $field) {
            if (Schema::hasColumn('members', $field)) {
                $key = str_replace('_date', '', $field);
                $sacraments[$key] = Member::whereNotNull($field)->count();
            }
        }
        
        // If no sacramental fields exist, return default values
        if (empty($sacraments)) {
            $sacraments = [
                'baptism' => 0,
                'confirmation' => 0,
                'first_communion' => 0,
            ];
        }
        
        return $sacraments;
    }

    private function getRecentActivity()
    {
        // Get available columns for members table
        $columns = Schema::getColumnListing('members');
        
        // Build select array based on available columns
        $selectColumns = ['id'];
        if (in_array('first_name', $columns)) $selectColumns[] = 'first_name';
        if (in_array('last_name', $columns)) $selectColumns[] = 'last_name';
        if (in_array('email', $columns)) $selectColumns[] = 'email';
        $selectColumns[] = 'created_at';
        
        return [
            'new_registrations' => Member::latest()->take(10)->get($selectColumns),
            'recent_updates' => Member::orderBy('updated_at', 'desc')->take(10)->get($selectColumns),
        ];
    }

    public function exportReport(Request $request)
    {
        $type = $request->input('type', 'all');
        $period = $request->input('period', 'all');
        $format = $request->input('format', 'excel');
        
        $data = $this->prepareExportData($type, $period);
        
        return $this->generateExport($data, $type, $format);
    }

    public function export(Request $request)
    {
        $type = $request->input('type', 'all');
        $period = $request->input('period', 'all');
        $format = $request->input('format', 'excel');
        
        $data = $this->prepareExportData($type, $period);
        
        return $this->generateExport($data, $type, $format);
    }

    private function generateExport($data, $type, $format)
    {
        $filename = $type . '-report-' . now()->format('Y-m-d');
        
        switch ($type) {
            case 'members':
                return Excel::download(new MembersExport(), $filename . '.xlsx');
            case 'marriages':
                return Excel::download(new MarriagesExport(), $filename . '.xlsx');
            case 'sacraments':
                return Excel::download(new SacramentsExport(), $filename . '.xlsx');
            case 'tithes':
                return Excel::download(new TithesExport(), $filename . '.xlsx');
            default:
                return Excel::download(new AllDataExport(), $filename . '.xlsx');
        }
    }

    private function prepareExportData($type, $period)
    {
        $baseQuery = Member::query();
        $dateFilteredQuery = $this->applyDateFilter($baseQuery, $period);
        
        return $dateFilteredQuery->get();
    }

    private function calculateGrowthRate($period)
    {
        $currentCount = Member::count();
        $previousCount = 1; // Avoid division by zero
        
        if ($currentCount > 0) {
            return round((($currentCount - $previousCount) / $previousCount) * 100, 2);
        }
        
        return 0;
    }

    private function generateChartData()
    {
        $now = Carbon::now();
        $monthlyData = [];
        
        // Generate 12 months of data
        for ($i = 11; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $registrations = Member::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            
            $baptisms = 0;
            if (Schema::hasColumn('members', 'baptism_date')) {
                $baptisms = Member::whereMonth('baptism_date', $date->month)
                    ->whereYear('baptism_date', $date->year)
                    ->count();
            }
            
            $monthlyData[] = [
                'month' => $date->format('M Y'),
                'registrations' => $registrations,
                'baptisms' => $baptisms,
            ];
        }

        return [
            'monthly_trends' => $monthlyData,
            'age_distribution' => $this->getAgeDistributionChart(),
            'gender_distribution' => $this->getGenderDistributionChart(),
            'status_distribution' => $this->getStatusDistributionChart(),
        ];
    }

    private function getAvailableFilters()
    {
        return [
            'periods' => [
                'all' => 'All Time',
                'today' => 'Today',
                'this_week' => 'This Week',
                'this_month' => 'This Month',
                'this_year' => 'This Year',
                'custom' => 'Custom Range'
            ],
            'export_types' => [
                'members' => 'Members',
                'marriages' => 'Marriages',
                'sacraments' => 'Sacraments',
                'tithes' => 'Tithes',
                'all' => 'All Data'
            ],
            'formats' => [
                'excel' => 'Excel (.xlsx)',
                'csv' => 'CSV',
                'pdf' => 'PDF'
            ]
        ];
    }

    private function getAgeDistributionChart()
    {
        if (!Schema::hasColumn('members', 'date_of_birth')) {
            return [
                ['name' => 'Children (0-17)', 'value' => 0],
                ['name' => 'Youth (18-35)', 'value' => 0],
                ['name' => 'Adults (36-60)', 'value' => 0],
                ['name' => 'Seniors (60+)', 'value' => 0],
            ];
        }

        return [
            ['name' => 'Children (0-17)', 'value' => Member::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18')->count()],
            ['name' => 'Youth (18-35)', 'value' => Member::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 35')->count()],
            ['name' => 'Adults (36-60)', 'value' => Member::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 36 AND 60')->count()],
            ['name' => 'Seniors (60+)', 'value' => Member::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) > 60')->count()],
        ];
    }

    private function getGenderDistributionChart()
    {
        if (!Schema::hasColumn('members', 'gender')) {
            return [
                ['name' => 'Male', 'value' => 0],
                ['name' => 'Female', 'value' => 0],
            ];
        }

        return [
            ['name' => 'Male', 'value' => Member::where('gender', 'male')->count()],
            ['name' => 'Female', 'value' => Member::where('gender', 'female')->count()],
        ];
    }

    private function getStatusDistributionChart()
    {
        // Since status column doesn't exist, return total members as active
        $totalMembers = Member::count();
        
        return [
            ['name' => 'Total Members', 'value' => $totalMembers],
            ['name' => 'Records', 'value' => $totalMembers],
        ];
    }

    public function getStatistics(Request $request)
    {
        $period = $request->input('period', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        return response()->json([
            'statistics' => $this->getParishStatistics($period, $startDate, $endDate),
            'charts' => $this->generateChartData()
        ]);
    }

    public function financialReport(Request $request)
    {
        // Fetch or calculate your financial data here
        $financial_data = [
            // ...populate with your actual data...
            'total_amount' => 0,
            'previous_period_amount' => 0,
            'period_growth' => 0,
            'monthly_data' => [],
            'by_type' => [],
            'by_method' => [],
            'top_contributors' => [],
            'statistics' => [
                'average_contribution' => 0,
                'total_contributors' => 0,
                'highest_single_contribution' => 0,
                'most_active_month' => '',
            ],
        ];

        return Inertia::render('Reports/Financial/Summary', [
            'financial_data' => $financial_data,
            'period' => $request->input('period', 'This Year'),
            'year' => $request->input('year', date('Y')),
            'filters' => [
                'period' => $request->input('period', 'This Year'),
                'year' => $request->input('year', date('Y')),
                'offering_type' => $request->input('offering_type', ''),
            ],
            'auth' => [
                'user' => $request->user(),
            ],
        ]);
    }

    /**
     * Get detailed member statistics by church and status
     */
    public function memberStatistics(Request $request)
    {
        $localChurch = $request->input('local_church');
        
        // Base statistics
        $stats = [
            'total_members' => Member::count(),
            'active_members' => Member::where('membership_status', 'active')->count(),
            'inactive_members' => Member::where('membership_status', 'inactive')->count(),
            'deceased_members' => Member::where('membership_status', 'deceased')->count(),
            'transferred_members' => Member::where('membership_status', 'transferred')->count(),
        ];

        // Church-specific statistics
        if ($localChurch) {
            $stats['church_total'] = Member::where('local_church', $localChurch)->count();
            $stats['church_active'] = Member::where('local_church', $localChurch)
                ->where('membership_status', 'active')->count();
            $stats['church_inactive'] = Member::where('local_church', $localChurch)
                ->where('membership_status', 'inactive')->count();
            $stats['church_deceased'] = Member::where('local_church', $localChurch)
                ->where('membership_status', 'deceased')->count();
        }

        // Group breakdown by church
        $groupBreakdown = Member::select('local_church', 'church_group', 'membership_status')
            ->selectRaw('COUNT(*) as count')
            ->when($localChurch, function($query, $church) {
                return $query->where('local_church', $church);
            })
            ->groupBy('local_church', 'church_group', 'membership_status')
            ->get()
            ->groupBy(['local_church', 'church_group']);

        // Format group data for easy frontend consumption
        $groupStats = [];
        foreach($groupBreakdown as $church => $groups) {
            $groupStats[$church] = [];
            foreach($groups as $group => $statuses) {
                $groupStats[$church][$group] = [
                    'total' => $statuses->sum('count'),
                    'active' => $statuses->where('membership_status', 'active')->sum('count'),
                    'inactive' => $statuses->where('membership_status', 'inactive')->sum('count'),
                    'deceased' => $statuses->where('membership_status', 'deceased')->sum('count'),
                    'transferred' => $statuses->where('membership_status', 'transferred')->sum('count'),
                ];
            }
        }

        return response()->json([
            'statistics' => $stats,
            'group_breakdown' => $groupStats,
            'available_churches' => [
                'St James Kangemi',
                'St Veronica Pembe Tatu',
                'Our Lady of Consolata Cathedral',
                'St Peter Kiawara',
                'Sacred Heart Kandara'
            ]
        ]);
    }
}
