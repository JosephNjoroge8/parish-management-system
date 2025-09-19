<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Sacrament;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MembersExport;
use App\Exports\MarriagesExport;
use App\Exports\SacramentsExport;
use App\Exports\TithesExport;
use App\Exports\ComprehensiveReportExport;

class ReportController extends Controller
{
    public function index()
    {
        $statistics = $this->getEnhancedParishStatistics();
        $chartData = $this->generateEnhancedChartData();
        
        return Inertia::render('Reports/Index', [
            'statistics' => $statistics,
            'charts' => $chartData,
            'filters' => $this->getAvailableFilters()
        ]);
    }

    /**
     * Get enhanced statistics for reports dashboard
     */
    public function getEnhancedStatistics(Request $request)
    {
        $period = $request->input('period', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Apply advanced filters if provided
        $filters = $request->only([
            'church_group', 'local_church', 'education_level', 'gender',
            'matrimony_status', 'occupation', 'age_min', 'age_max',
            'has_baptism', 'has_confirmation'
        ]);
        
        return response()->json([
            'statistics' => $this->getEnhancedParishStatistics($period, $startDate, $endDate, $filters),
            'charts' => $this->generateEnhancedChartData($filters)
        ]);
    }

    /**
     * Export filtered members based on report criteria
     */
    public function exportFilteredMembers(Request $request)
    {
        $filters = $request->only([
            'church_group', 'local_church', 'education_level', 'gender',
            'matrimony_status', 'occupation', 'age_min', 'age_max',
            'has_baptism', 'has_confirmation', 'tribe', 'small_christian_community',
            'membership_status', 'marital_status'
        ]);

        $period = $request->input('period', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $format = $request->input('format', 'excel');
        $category = $request->input('category', 'filtered-members');

        // Build query with filters
        $query = Member::query();
        
        // Apply advanced filters
        $this->applyAdvancedFilters($query, $filters);
        
        // Apply date filter
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } else {
            $query = $this->applyDateFilter($query, $period);
        }

        $members = $query->orderBy('created_at', 'desc')->get();

        // Generate filename based on applied filters
        $filename = $this->generateFilteredFilename($filters, $category);

        return $this->exportMembersData($members, $format, $filename, $filters);
    }

    /**
     * Export members by specific category (church group, education level, etc.)
     */
    public function exportMembersByCategory(Request $request)
    {
        $category = $request->input('category'); // 'church_group', 'education_level', etc.
        $value = $request->input('value'); // specific value for the category
        $format = $request->input('format', 'excel');

        $query = Member::query();

        switch ($category) {
            case 'church_group':
                $query->where('church_group', $value);
                break;
            case 'local_church':
                $query->where('local_church', $value);
                break;
            case 'education':
                $query->where('education_level', $value);
                break;
            case 'gender':
                $query->where('gender', $value);
                break;
            case 'marital_status':
                $query->where('marital_status', $value);
                break;
            case 'membership_status':
                $query->where('membership_status', $value);
                break;
            case 'occupation':
                $query->where('occupation', $value);
                break;
            case 'tribe':
                $query->where('tribe', $value);
                break;
            case 'small_christian_community':
                $query->where('small_christian_community', $value);
                break;
            case 'age_group':
                $this->applyAgeGroupFilter($query, $value);
                break;
            case 'baptized':
                $query->whereNotNull('baptism_date');
                break;
            case 'confirmed':
                $query->whereNotNull('confirmation_date');
                break;
            case 'married':
                $query->where('marital_status', 'married');
                break;
            case 'marriage_type':
                $query->where('marriage_type', $value);
                break;
            case 'monthly_trends':
                // For monthly trends, return aggregated data
                return $this->exportMonthlyTrendsData($format);
            default:
                return response()->json(['error' => 'Invalid category'], 400);
        }

        $members = $query->orderBy('created_at', 'desc')->get();
        $filename = $category . '-' . str_replace(' ', '-', strtolower($value)) . '-members-' . now()->format('Y-m-d');

        return $this->exportMembersData($members, $format, $filename, [$category => $value]);
    }

    /**
     * Export monthly trends data
     */
    private function exportMonthlyTrendsData($format)
    {
        $now = Carbon::now();
        $monthlyData = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $registrations = Member::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            
            $baptisms = Member::whereMonth('baptism_date', $date->month)
                ->whereYear('baptism_date', $date->year)
                ->count();
                
            $confirmations = Member::whereMonth('confirmation_date', $date->month)
                ->whereYear('confirmation_date', $date->year)
                ->count();
            
            $monthlyData[] = [
                'month' => $date->format('M Y'),
                'registrations' => $registrations,
                'baptisms' => $baptisms,
                'confirmations' => $confirmations,
            ];
        }

        $filename = 'monthly-trends-' . now()->format('Y-m-d');
        
        if ($format === 'excel') {
            return Excel::download(
                new class($monthlyData) implements \Maatwebsite\Excel\Concerns\FromArray {
                    private $data;
                    
                    public function __construct($data) {
                        $this->data = $data;
                    }
                    
                    public function array(): array {
                        return $this->data;
                    }
                },
                $filename . '.xlsx'
            );
        }
        
        return response()->json($monthlyData);
    }

    /**
     * Export members data in specified format with memory optimization
     */
    public function exportMembersDataFromQuery($query, $format = 'excel', $filename = null, $filters = [])
    {
        try {
            // Ensure filename is set
            if (!$filename) {
                $filename = 'members-export-' . now()->format('Y-m-d-H-i-s');
            }

            switch ($format) {
                case 'excel':
                    return $this->exportQueryToExcel($query, $filename, $filters);
                case 'csv':
                    return $this->exportQueryToCSV($query, $filename, $filters);
                case 'json':
                    return $this->exportQueryToJSON($query, $filename, $filters);
                case 'pdf':
                    // For PDF, we still need to load data into memory
                    $members = $query->get();
                    return $this->exportMembersToPDF($filters, [], [], $members);
                default:
                    return response()->json(['error' => 'Invalid export format. Supported formats: excel, csv, json, pdf'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Export from query failed: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export query results to Excel using chunked processing
     */
    private function exportQueryToExcel($query, $filename, $filters = [])
    {
        try {
            // Use a memory-efficient export
            $export = new \App\Exports\OptimizedMembersExport($query, $filters);
            return Excel::download($export, $filename . '.xlsx');
        } catch (\Exception $e) {
            Log::error('Excel export from query failed: ' . $e->getMessage());
            return response()->json(['error' => 'Excel export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export query results to CSV using chunked processing
     */
    private function exportQueryToCSV($query, $filename, $filters = [])
    {
        try {
            // For CSV, we'll use a simpler approach with chunked processing
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
            ];

            $callback = function() use ($query) {
                $file = fopen('php://output', 'w');
                
                // Write headers
                fputcsv($file, [
                    'ID', 'First Name', 'Last Name', 'Date of Birth', 'Gender',
                    'Phone', 'Email', 'Local Church', 'Church Group', 
                    'Membership Status', 'Membership Date'
                ]);

                // Process in chunks to avoid memory issues
                $query->chunk(1000, function ($members) use ($file) {
                    foreach ($members as $member) {
                        fputcsv($file, [
                            $member->id ?? '',
                            $member->first_name ?? '',
                            $member->last_name ?? '',
                            $member->date_of_birth ?? '',
                            $member->gender ?? '',
                            $this->formatPhoneForExport($member->phone ?? ''),
                            $member->email ?? '',
                            $member->local_church ?? '',
                            $member->church_group ?? '',
                            $member->membership_status ?? '',
                            $member->membership_date ?? '',
                        ]);
                    }
                });

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('CSV export from query failed: ' . $e->getMessage());
            return response()->json(['error' => 'CSV export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export query results to JSON
     */
    private function exportQueryToJSON($query, $filename, $filters = [])
    {
        try {
            // For JSON, we can use chunked processing and return as JSON response
            $members = [];
            
            $query->chunk(1000, function ($chunk) use (&$members) {
                foreach ($chunk as $member) {
                    $members[] = [
                        'id' => $member->id ?? '',
                        'first_name' => $member->first_name ?? '',
                        'last_name' => $member->last_name ?? '',
                        'date_of_birth' => $member->date_of_birth ?? '',
                        'gender' => $member->gender ?? '',
                        'phone' => $member->phone ?? '',
                        'email' => $member->email ?? '',
                        'local_church' => $member->local_church ?? '',
                        'church_group' => $member->church_group ?? '',
                        'membership_status' => $member->membership_status ?? '',
                        'membership_date' => $member->membership_date ?? '',
                    ];
                }
            });

            $response = [
                'success' => true,
                'data' => $members,
                'total' => count($members),
                'filters' => $filters,
                'exported_at' => now()->toISOString(),
                'filename' => $filename
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('JSON export from query failed: ' . $e->getMessage());
            return response()->json(['error' => 'JSON export failed: ' . $e->getMessage()], 500);
        }
    }
    public function exportMembersData($members, $format = 'excel', $filename = null, $filters = [])
    {
        try {
            Log::info('Export members data started', [
                'format' => $format,
                'filename' => $filename,
                'members_count' => is_countable($members) ? count($members) : 'unknown'
            ]);
            
            // Ensure filename is set
            if (!$filename) {
                $filename = 'members-export-' . now()->format('Y-m-d-H-i-s');
            }

            // Convert collection to array if needed
            if (is_object($members) && method_exists($members, 'toArray')) {
                $membersArray = $members->toArray();
            } elseif (is_object($members) && method_exists($members, 'all')) {
                $membersArray = $members->all();
            } else {
                $membersArray = $members;
            }

            switch ($format) {
                case 'excel':
                    return $this->exportMembersToExcel($membersArray, $filename, $filters);
                case 'csv':
                    return $this->exportMembersToCSV($membersArray, $filename, $filters);
                case 'json':
                    return $this->exportMembersToJSON($membersArray, $filename, $filters);
                case 'pdf':
                    return $this->exportMembersToPDF($filters, [], [], $members);
                default:
                    return response()->json(['error' => 'Invalid export format. Supported formats: excel, csv, json, pdf'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Export members data failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export members to Excel format
     */
    private function exportMembersToExcel($members, $filename, $filters = [])
    {
        try {
            // Convert to collection if it's not already
            if (is_array($members)) {
                $members = collect($members);
            } elseif (is_object($members) && method_exists($members, 'toArray')) {
                $members = collect($members->toArray());
            } elseif (is_object($members) && method_exists($members, 'all')) {
                $members = collect($members->all());
            }
            
            $export = new MembersExport($filters, [], [], $members);
            return Excel::download($export, $filename . '.xlsx');
        } catch (\Exception $e) {
            Log::error('Excel export failed: ' . $e->getMessage(), [
                'filename' => $filename,
                'filters' => $filters,
                'members_count' => is_countable($members) ? count($members) : 'unknown'
            ]);
            return response()->json(['error' => 'Excel export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export members to JSON format
     */
    private function exportMembersToJSON($members, $filename, $filters = [])
    {
        try {
            // Convert members array to standardized format
            $exportData = [];
            
            foreach ($members as $member) {
                if (is_array($member)) {
                    $member = (object) $member;
                }
                
                $exportData[] = [
                    'id' => $member->id ?? '',
                    'first_name' => $member->first_name ?? '',
                    'last_name' => $member->last_name ?? '',
                    'date_of_birth' => $member->date_of_birth ?? '',
                    'gender' => $member->gender ?? '',
                    'phone' => $member->phone ?? '',
                    'email' => $member->email ?? '',
                    'local_church' => $member->local_church ?? '',
                    'church_group' => $member->church_group ?? '',
                    'membership_status' => $member->membership_status ?? '',
                    'membership_date' => $member->membership_date ?? '',
                ];
            }

            $response = [
                'success' => true,
                'data' => $exportData,
                'total' => count($exportData),
                'filters' => $filters,
                'exported_at' => now()->toISOString(),
                'filename' => $filename
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('JSON export failed: ' . $e->getMessage());
            return response()->json(['error' => 'JSON export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export members to CSV format
     */
    private function exportMembersToCSV($members, $filename, $filters = [])
    {
        try {
            $export = new MembersExport($filters, [], [], $members);
            return Excel::download($export, $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
        } catch (\Exception $e) {
            return response()->json(['error' => 'CSV export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export members with filters (alternative signature for compatibility)
     */
    public function exportMembersWithFilters(array $filters = [], array $selectedFields = [], array $includeOptions = [], $membersCollection = null)
    {
        try {
            $format = $filters['format'] ?? 'excel';
            
            switch ($format) {
                case 'excel':
                    $export = new MembersExport($filters, $selectedFields, $includeOptions, $membersCollection);
                    $filename = 'members-export-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
                    return Excel::download($export, $filename);
                case 'csv':
                    $export = new MembersExport($filters, $selectedFields, $includeOptions, $membersCollection);
                    $filename = 'members-export-' . now()->format('Y-m-d-H-i-s') . '.csv';
                    return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
                case 'pdf':
                    return $this->exportMembersToPDF($filters, $selectedFields, $includeOptions, $membersCollection);
                default:
                    return response()->json(['error' => 'Invalid export format'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export members to PDF format
     */
    private function exportMembersToPDF(array $filters = [], array $selectedFields = [], array $includeOptions = [], $membersCollection = null)
    {
        try {
            // Use Dompdf for PDF generation
            $pdf = app('dompdf.wrapper');
            
            // Get the data
            if ($membersCollection !== null) {
                $members = is_array($membersCollection) ? collect($membersCollection) : $membersCollection;
            } else {
                $query = Member::query();
                
                // Apply filters
                if (!empty($filters['search'])) {
                    $query->where(function($q) use ($filters) {
                        $search = $filters['search'];
                        $q->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")
                          ->orWhere('middle_name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%");
                    });
                }
                
                if (!empty($filters['local_church'])) {
                    $query->where('local_church', $filters['local_church']);
                }
                
                if (!empty($filters['church_group'])) {
                    $query->where('church_group', $filters['church_group']);
                }
                
                if (!empty($filters['membership_status'])) {
                    $query->where('membership_status', $filters['membership_status']);
                }
                
                if (!empty($filters['gender'])) {
                    $query->where('gender', $filters['gender']);
                }
                
                $members = $query->limit(500)->get(); // Limit for PDF performance
            }
            
            // Prepare data for PDF view
            $title = 'Members Export';
            if (!empty($filters['local_church'])) {
                $title .= ' - ' . $filters['local_church'];
            }
            if (!empty($filters['church_group'])) {
                $title .= ' - ' . $filters['church_group'];
            }
            
            $data = [
                'title' => $title,
                'members' => $members,
                'selectedFields' => $selectedFields,
                'filters' => $filters,
                'exportDate' => now()->format('Y-m-d H:i:s')
            ];
            
            // Generate PDF from view
            $html = view('exports.members-pdf', $data)->render();
            $pdf->loadHTML($html);
            $pdf->setPaper('A4', 'landscape');
            
            $filename = 'members-export-' . now()->format('Y-m-d-H-i-s') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'PDF export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate filename based on filters
     */
    private function generateFilteredFilename($filters, $category)
    {
        $parts = [];
        
        if (!empty($filters['church_group'])) {
            $parts[] = str_replace(' ', '-', $filters['church_group']);
        }
        
        if (!empty($filters['local_church'])) {
            $parts[] = str_replace(' ', '-', $filters['local_church']);
        }
        
        if (!empty($filters['education_level'])) {
            $parts[] = str_replace(' ', '-', $filters['education_level']);
        }
        
        if (!empty($filters['gender'])) {
            $parts[] = $filters['gender'];
        }
        
        $filterPart = !empty($parts) ? implode('-', $parts) : $category;
        
        return $filterPart . '-members-' . now()->format('Y-m-d');
    }

    /**
     * Apply age group filter to query
     */
    private function applyAgeGroupFilter($query, $ageGroup)
    {
        switch ($ageGroup) {
            case 'children':
                $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) BETWEEN 0 AND 12');
                break;
            case 'youth':
                $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) BETWEEN 13 AND 24');
                break;
            case 'adults':
                $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) BETWEEN 25 AND 59');
                break;
            case 'seniors':
                $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) >= 60');
                break;
        }
    }

    public function exportAll()
    {
        return Excel::download(new ComprehensiveReportExport(), 'parish-comprehensive-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportMembers(Request $request)
    {
        $period = $request->get('period', 'all');
        return Excel::download(new MembersExport($period), 'members-' . $period . '-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function statistics()
    {
        return response()->json($this->getEnhancedParishStatistics());
    }

    /**
     * Get enhanced parish statistics with comprehensive data structure
     */
    private function getEnhancedParishStatistics($period = 'all', $startDate = null, $endDate = null, $filters = [])
    {
        $baseQuery = Member::query();
        $totalMembers = $baseQuery->count();
        
        // Apply filters
        $this->applyAdvancedFilters($baseQuery, $filters);
        
        $dateFilteredQuery = $this->applyDateFilter(clone $baseQuery, $period, $startDate, $endDate);
        
        // Get church groups data
        $churchGroupsData = Member::select('church_group', DB::raw('count(*) as count'))
            ->groupBy('church_group')
            ->pluck('count', 'church_group')
            ->toArray();
            
        // Get additional church groups if the column exists
        $additionalMemberships = [];
        if (Schema::hasColumn('members', 'additional_church_groups')) {
            // This would need custom logic to parse JSON/array field
            $additionalMemberships = [];
        }
        
        return [
            'overview' => [
                'total_members' => $totalMembers,
                'new_members' => $dateFilteredQuery->count(),
                'active_members' => Member::where('membership_status', 'active')->count(),
                'inactive_members' => Member::where('membership_status', 'inactive')->count(),
                'growth_rate' => $this->calculateGrowthRate($period),
            ],
            'demographics' => [
                'age_groups' => [
                    'children' => Member::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) BETWEEN 0 AND 12')->count(),
                    'youth' => Member::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) BETWEEN 13 AND 24')->count(),
                    'adults' => Member::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) BETWEEN 25 AND 59')->count(),
                    'seniors' => Member::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) >= 60')->count(),
                ],
                'gender_distribution' => [
                    'male' => Member::where('gender', 'Male')->count(),
                    'female' => Member::where('gender', 'Female')->count(),
                ],
                'marital_status' => Member::select('matrimony_status', DB::raw('count(*) as count'))
                    ->whereNotNull('matrimony_status')
                    ->groupBy('matrimony_status')
                    ->pluck('count', 'matrimony_status')
                    ->toArray(),
                'education_levels' => Member::select('education_level', DB::raw('count(*) as count'))
                    ->whereNotNull('education_level')
                    ->groupBy('education_level')
                    ->pluck('count', 'education_level')
                    ->toArray(),
                'occupations' => Member::select('occupation', DB::raw('count(*) as count'))
                    ->whereNotNull('occupation')
                    ->groupBy('occupation')
                    ->pluck('count', 'occupation')
                    ->toArray(),
            ],
            'church_groups' => [
                'primary_groups' => $churchGroupsData,
                'additional_memberships' => $additionalMemberships,
                'total_group_memberships' => array_sum($churchGroupsData) + array_sum($additionalMemberships),
            ],
            'local_churches' => Member::select('local_church', DB::raw('count(*) as count'))
                ->groupBy('local_church')
                ->pluck('count', 'local_church')
                ->toArray(),
            'small_christian_communities' => Member::select('small_christian_community', DB::raw('count(*) as count'))
                ->whereNotNull('small_christian_community')
                ->where('small_christian_community', '!=', '')
                ->groupBy('small_christian_community')
                ->pluck('count', 'small_christian_community')
                ->toArray(),
            'sacraments' => [
                'baptized' => Member::whereNotNull('baptism_date')->count(),
                'confirmed' => Member::whereNotNull('confirmation_date')->count(),
                'married' => Member::where('matrimony_status', 'married')->count(),
                'marriage_types' => Member::select('marriage_type', DB::raw('count(*) as count'))
                    ->whereNotNull('marriage_type')
                    ->groupBy('marriage_type')
                    ->pluck('count', 'marriage_type')
                    ->toArray(),
            ],
            'recent_activity' => $this->getRecentActivity(),
            'period_info' => [
                'period' => $period,
                'generated_at' => now()->toDateTimeString(),
            ]
        ];
    }

    /**
     * Apply advanced filters to query
     */
    private function applyAdvancedFilters($query, $filters)
    {
        if (!empty($filters['church_group'])) {
            $query->where('church_group', $filters['church_group']);
        }
        
        if (!empty($filters['local_church'])) {
            $query->where('local_church', $filters['local_church']);
        }
        
        if (!empty($filters['education_level'])) {
            $query->where('education_level', $filters['education_level']);
        }
        
        if (!empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }
        
        if (!empty($filters['matrimony_status'])) {
            $query->where('matrimony_status', $filters['matrimony_status']);
        }
        
        if (!empty($filters['occupation'])) {
            $query->where('occupation', $filters['occupation']);
        }
        
        if (!empty($filters['age_min'])) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) >= ?', [$filters['age_min']]);
        }
        
        if (!empty($filters['age_max'])) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) <= ?', [$filters['age_max']]);
        }
        
        if (isset($filters['has_baptism']) && $filters['has_baptism']) {
            $query->whereNotNull('baptism_date');
        }
        
        if (isset($filters['has_confirmation']) && $filters['has_confirmation']) {
            $query->whereNotNull('confirmation_date');
        }
    }

    /**
     * Generate enhanced chart data matching frontend expectations
     */
    private function generateEnhancedChartData($filters = [])
    {
        $baseQuery = Member::query();
        $this->applyAdvancedFilters($baseQuery, $filters);
        
        $now = Carbon::now();
        $monthlyData = [];
        
        // Generate 12 months of data
        for ($i = 11; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $registrations = Member::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            
            $baptisms = Member::whereMonth('baptism_date', $date->month)
                ->whereYear('baptism_date', $date->year)
                ->count();
                
            $confirmations = Member::whereMonth('confirmation_date', $date->month)
                ->whereYear('confirmation_date', $date->year)
                ->count();
            
            $monthlyData[] = [
                'month' => $date->format('M Y'),
                'registrations' => $registrations,
                'baptisms' => $baptisms,
                'confirmations' => $confirmations,
            ];
        }

        return [
            'monthly_trends' => $monthlyData,
            'age_distribution' => [
                ['name' => 'Children (0-12)', 'value' => Member::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) BETWEEN 0 AND 12')->count()],
                ['name' => 'Youth (13-24)', 'value' => Member::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) BETWEEN 13 AND 24')->count()],
                ['name' => 'Adults (25-59)', 'value' => Member::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) BETWEEN 25 AND 59')->count()],
                ['name' => 'Seniors (60+)', 'value' => Member::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) >= 60')->count()],
            ],
            'gender_distribution' => [
                ['name' => 'Male', 'value' => Member::where('gender', 'Male')->count()],
                ['name' => 'Female', 'value' => Member::where('gender', 'Female')->count()],
            ],
            'status_distribution' => [
                ['name' => 'Active', 'value' => Member::where('membership_status', 'active')->count()],
                ['name' => 'Inactive', 'value' => Member::where('membership_status', 'inactive')->count()],
                ['name' => 'Transferred', 'value' => Member::where('membership_status', 'transferred')->count()],
                ['name' => 'Deceased', 'value' => Member::where('membership_status', 'deceased')->count()],
            ],
            'church_groups_distribution' => Member::select('church_group', DB::raw('count(*) as value'))
                ->selectRaw('church_group as name')
                ->groupBy('church_group')
                ->get()
                ->toArray(),
            'education_levels_distribution' => Member::select('education_level', DB::raw('count(*) as value'))
                ->selectRaw('education_level as name')
                ->whereNotNull('education_level')
                ->groupBy('education_level')
                ->get()
                ->toArray(),
            'local_churches_distribution' => Member::select('local_church', DB::raw('count(*) as value'))
                ->selectRaw('local_church as name')
                ->groupBy('local_church')
                ->get()
                ->toArray(),
            'marriage_types_distribution' => Member::select('marriage_type', DB::raw('count(*) as value'))
                ->selectRaw('marriage_type as name')
                ->whereNotNull('marriage_type')
                ->groupBy('marriage_type')
                ->get()
                ->toArray(),
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

    private function calculateGrowthRate($period)
    {
        $currentCount = Member::count();
        $previousCount = 1; // Avoid division by zero
        
        if ($currentCount > 0) {
            return round((($currentCount - $previousCount) / $previousCount) * 100, 2);
        }
        
        return 0;
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
            ],
            'local_churches' => $this->getFilterOptions('local_church'),
            'church_groups' => $this->getFilterOptions('church_group'),
            'education_levels' => $this->getFilterOptions('education_level'),
            'membership_statuses' => [
                'active' => 'Active',
                'inactive' => 'Inactive', 
                'transferred' => 'Transferred',
                'deceased' => 'Deceased'
            ],
            'tribes' => $this->getFilterOptions('tribe'),
            'small_christian_communities' => $this->getFilterOptions('small_christian_community'),
            'genders' => [
                'male' => 'Male',
                'female' => 'Female'
            ],
            'occupations' => $this->getFilterOptions('occupation'),
            'age_groups' => [
                'children' => 'Children (0-12)',
                'youth' => 'Youth (13-24)',
                'adults' => 'Adults (25-59)',
                'seniors' => 'Seniors (60+)'
            ]
        ];
    }

    private function getFilterOptions($column)
    {
        if (!Schema::hasColumn('members', $column)) {
            return [];
        }

        return Member::select($column)
            ->distinct()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderBy($column)
            ->pluck($column)
            ->mapWithKeys(fn($value) => [$value => $value])
            ->toArray();
    }

    // ========================================
    // MEMBER LISTS GENERATION METHODS
    // ========================================

    /**
     * Get member lists by local church
     */
    public function getMembersByLocalChurch(Request $request)
    {
        $localChurch = $request->input('local_church');
        $format = $request->input('format', 'json');
        $includeInactive = $request->input('include_inactive', false);

        $query = Member::query();
        
        if ($localChurch) {
            $query->where('local_church', $localChurch);
        }

        if (!$includeInactive) {
            $query->where('membership_status', 'active');
        }

        $members = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        if ($format === 'export') {
            $filename = 'members-' . str_replace(' ', '-', strtolower($localChurch)) . '-' . now()->format('Y-m-d');
            return $this->exportMembersData($members, 'excel', $filename);
        }

        return response()->json([
            'members' => $members,
            'total_count' => $members->count(),
            'church' => $localChurch,
            'generated_at' => now()->toDateTimeString()
        ]);
    }

    /**
     * Get member lists by church group
     */
    public function getMembersByChurchGroup(Request $request)
    {
        $churchGroup = $request->input('church_group');
        $format = $request->input('format', 'json');
        $includeInactive = $request->input('include_inactive', false);

        $query = Member::query();
        
        if ($churchGroup) {
            $query->where('church_group', $churchGroup);
        }

        if (!$includeInactive) {
            $query->where('membership_status', 'active');
        }

        $members = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        if ($format === 'export') {
            $filename = 'members-' . str_replace(' ', '-', strtolower($churchGroup)) . '-' . now()->format('Y-m-d');
            return $this->exportMembersData($members, 'excel', $filename);
        }

        return response()->json([
            'members' => $members,
            'total_count' => $members->count(),
            'church_group' => $churchGroup,
            'generated_at' => now()->toDateTimeString()
        ]);
    }

    /**
     * Get member lists by age group
     */
    public function getMembersByAgeGroup(Request $request)
    {
        $ageGroup = $request->input('age_group'); // 'children', 'youth', 'adults', 'seniors'
        $format = $request->input('format', 'json');
        $includeInactive = $request->input('include_inactive', false);

        $query = Member::query();
        
        // Apply age group filter
        $this->applyAgeGroupFilter($query, $ageGroup);

        if (!$includeInactive) {
            $query->where('membership_status', 'active');
        }

        $members = $query->orderBy('date_of_birth', 'desc')
            ->orderBy('last_name')
            ->get();

        if ($format === 'export') {
            $filename = 'members-age-group-' . $ageGroup . '-' . now()->format('Y-m-d');
            return $this->exportMembersData($members, 'excel', $filename);
        }

        return response()->json([
            'members' => $members,
            'total_count' => $members->count(),
            'age_group' => $ageGroup,
            'generated_at' => now()->toDateTimeString()
        ]);
    }

    /**
     * Get member lists by gender
     */
    public function getMembersByGender(Request $request)
    {
        $gender = $request->input('gender'); // 'Male', 'Female'
        $format = $request->input('format', 'json');
        $includeInactive = $request->input('include_inactive', false);

        $query = Member::query()->where('gender', $gender);

        if (!$includeInactive) {
            $query->where('membership_status', 'active');
        }

        $members = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        if ($format === 'export') {
            $filename = 'members-' . strtolower($gender) . '-' . now()->format('Y-m-d');
            return $this->exportMembersData($members, 'excel', $filename);
        }

        return response()->json([
            'members' => $members,
            'total_count' => $members->count(),
            'gender' => $gender,
            'generated_at' => now()->toDateTimeString()
        ]);
    }

    /**
     * Get active members list
     */
    public function getActiveMembers(Request $request)
    {
        $format = $request->input('format', 'json');
        $localChurch = $request->input('local_church');
        $churchGroup = $request->input('church_group');

        $query = Member::where('membership_status', 'active');

        if ($localChurch) {
            $query->where('local_church', $localChurch);
        }

        if ($churchGroup) {
            $query->where('church_group', $churchGroup);
        }

        $members = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        if ($format === 'export') {
            $filename = 'active-members-' . now()->format('Y-m-d');
            return $this->exportMembersData($members, 'excel', $filename);
        }

        return response()->json([
            'members' => $members,
            'total_count' => $members->count(),
            'status' => 'active',
            'generated_at' => now()->toDateTimeString()
        ]);
    }

    /**
     * Get inactive members list
     */
    public function getInactiveMembers(Request $request)
    {
        $format = $request->input('format', 'json');
        $localChurch = $request->input('local_church');
        $churchGroup = $request->input('church_group');

        $query = Member::where('membership_status', 'inactive');

        if ($localChurch) {
            $query->where('local_church', $localChurch);
        }

        if ($churchGroup) {
            $query->where('church_group', $churchGroup);
        }

        $members = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        if ($format === 'export') {
            $filename = 'inactive-members-' . now()->format('Y-m-d');
            return $this->exportMembersData($members, 'excel', $filename);
        }

        return response()->json([
            'members' => $members,
            'total_count' => $members->count(),
            'status' => 'inactive',
            'generated_at' => now()->toDateTimeString()
        ]);
    }

    /**
     * Get transferred members list
     */
    public function getTransferredMembers(Request $request)
    {
        $format = $request->input('format', 'json');
        $localChurch = $request->input('local_church');
        $churchGroup = $request->input('church_group');

        $query = Member::where('membership_status', 'transferred');

        if ($localChurch) {
            $query->where('local_church', $localChurch);
        }

        if ($churchGroup) {
            $query->where('church_group', $churchGroup);
        }

        $members = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        if ($format === 'export') {
            $filename = 'transferred-members-' . now()->format('Y-m-d');
            return $this->exportMembersData($members, 'excel', $filename);
        }

        return response()->json([
            'members' => $members,
            'total_count' => $members->count(),
            'status' => 'transferred',
            'generated_at' => now()->toDateTimeString()
        ]);
    }

    /**
     * Get deceased members list
     */
    public function getDeceasedMembers(Request $request)
    {
        $format = $request->input('format', 'json');
        $localChurch = $request->input('local_church');
        $churchGroup = $request->input('church_group');

        $query = Member::where('membership_status', 'deceased');

        if ($localChurch) {
            $query->where('local_church', $localChurch);
        }

        if ($churchGroup) {
            $query->where('church_group', $churchGroup);
        }

        $members = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        if ($format === 'export') {
            $filename = 'deceased-members-' . now()->format('Y-m-d');
            return $this->exportMembersData($members, 'excel', $filename);
        }

        return response()->json([
            'members' => $members,
            'total_count' => $members->count(),
            'status' => 'deceased',
            'generated_at' => now()->toDateTimeString()
        ]);
    }

    /**
     * Get all clear member records (comprehensive member list)
     */
    public function getAllClearRecords(Request $request)
    {
        $format = $request->input('format', 'json');
        $filters = $request->only([
            'local_church', 'church_group', 'membership_status', 'gender',
            'education_level', 'occupation', 'tribe', 'small_christian_community'
        ]);

        $query = Member::query();

        // Apply all specified filters
        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                $query->where($field, $value);
            }
        }

        // Age range filters
        if ($request->has('age_min')) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) >= ?', [$request->input('age_min')]);
        }

        if ($request->has('age_max')) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) <= ?', [$request->input('age_max')]);
        }

        // Sacrament filters
        if ($request->boolean('has_baptism')) {
            $query->whereNotNull('baptism_date');
        }

        if ($request->boolean('has_confirmation')) {
            $query->whereNotNull('confirmation_date');
        }

        $members = $query->orderBy('membership_status')
            ->orderBy('local_church')
            ->orderBy('church_group')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        if ($format === 'export') {
            $filename = 'all-members-clear-records-' . now()->format('Y-m-d');
            return $this->exportMembersData($members, 'excel', $filename, $filters);
        }

        return response()->json([
            'members' => $members,
            'total_count' => $members->count(),
            'filters_applied' => $filters,
            'generated_at' => now()->toDateTimeString()
        ]);
    }

    /**
     * Get member lists with advanced filtering
     */
    public function getFilteredMembersList(Request $request)
    {
        $filters = $request->only([
            'local_church', 'church_group', 'membership_status', 'gender',
            'education_level', 'occupation', 'tribe', 'small_christian_community',
            'matrimony_status', 'marriage_type'
        ]);

        $format = $request->input('format', 'json');
        $sortBy = $request->input('sort_by', 'last_name');
        $sortOrder = $request->input('sort_order', 'asc');

        $query = Member::query();

        // Apply advanced filters
        $this->applyAdvancedFilters($query, $filters);

        // Apply age filters
        if ($request->has('age_min')) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) >= ?', [$request->input('age_min')]);
        }

        if ($request->has('age_max')) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) <= ?', [$request->input('age_max')]);
        }

        // Apply sacrament filters
        if ($request->boolean('has_baptism')) {
            $query->whereNotNull('baptism_date');
        }

        if ($request->boolean('has_confirmation')) {
            $query->whereNotNull('confirmation_date');
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        $members = $query->get();

        if ($format === 'export') {
            $filename = $this->generateFilteredFilename($filters, 'filtered-members-list');
            return $this->exportMembersData($members, 'excel', $filename, $filters);
        }

        return response()->json([
            'members' => $members,
            'total_count' => $members->count(),
            'filters_applied' => $filters,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
            'generated_at' => now()->toDateTimeString()
        ]);
    }

    /**
     * Get comprehensive member directory with all categories
     */
    public function getMemberDirectory(Request $request)
    {
        $format = $request->input('format', 'json');
        $category = $request->input('category', 'all'); // 'all', 'by_church', 'by_group', 'by_status'

        $data = [];

        switch ($category) {
            case 'by_church':
                $churches = Member::distinct()->pluck('local_church')->filter();
                foreach ($churches as $church) {
                    $data[$church] = Member::where('local_church', $church)
                        ->orderBy('last_name')
                        ->get();
                }
                break;

            case 'by_group':
                $groups = Member::distinct()->pluck('church_group')->filter();
                foreach ($groups as $group) {
                    $data[$group] = Member::where('church_group', $group)
                        ->orderBy('last_name')
                        ->get();
                }
                break;

            case 'by_status':
                $statuses = ['active', 'inactive', 'transferred', 'deceased'];
                foreach ($statuses as $status) {
                    $data[$status] = Member::where('membership_status', $status)
                        ->orderBy('last_name')
                        ->get();
                }
                break;

            default: // 'all'
                $data['all_members'] = Member::orderBy('local_church')
                    ->orderBy('church_group')
                    ->orderBy('last_name')
                    ->get();
                break;
        }

        if ($format === 'export') {
            $filename = 'member-directory-' . $category . '-' . now()->format('Y-m-d');
            
            // For directory exports, we'll export all data as separate sheets
            return $this->exportDirectoryData($data, $filename, $category);
        }

        return response()->json([
            'directory' => $data,
            'category' => $category,
            'generated_at' => now()->toDateTimeString()
        ]);
    }

    /**
     * Export directory data with multiple sheets
     */
    private function exportDirectoryData($data, $filename, $category)
    {
        // This would require a custom export class that handles multiple sheets
        // For now, flatten the data and export as single sheet
        $allMembers = collect();
        
        foreach ($data as $categoryName => $members) {
            if (is_object($members) && method_exists($members, 'each')) {
                $members->each(function ($member) use ($categoryName, $allMembers) {
                    $member->category = $categoryName;
                    $allMembers->push($member);
                });
            }
        }

        return $this->exportMembersData($allMembers, 'excel', $filename);
    }

    // ========================================
    // INDIVIDUAL EXPORT METHODS
    // ========================================

    /**
     * Export members by local church
     */
    public function exportByLocalChurch(Request $request)
    {
        try {
            Log::info('Export by local church started', [
                'value' => $request->input('value'),
                'format' => $request->input('format')
            ]);
            
            // Increase memory limit and execution time for exports
            ini_set('memory_limit', '1G');
            set_time_limit(300); // 5 minutes
            
            $church = $request->input('value', 'all');
            $format = $request->input('format', 'excel');

            // Create query without executing it
            $query = Member::select([
                'id', 'first_name', 'last_name', 'date_of_birth', 
                'gender', 'phone', 'email', 'local_church', 'church_group', 
                'membership_status', 'membership_date'
            ]);

            if ($church !== 'all') {
                $query->where('local_church', $church);
            }
            
            $query->orderBy('local_church')->orderBy('last_name');

            $filename = $church === 'all' ? "all-members-by-church" : Str::slug($church) . "-members";

            Log::info('Query prepared for export', [
                'church' => $church,
                'filename' => $filename
            ]);

            return $this->exportMembersDataFromQuery($query, $format, $filename, ['local_church' => $church]);
        } catch (\Exception $e) {
            Log::error('Export by local church failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export members by church group
     */
    public function exportByChurchGroup(Request $request)
    {
        try {
            // Increase memory limit and execution time for exports
            ini_set('memory_limit', '1G');
            set_time_limit(300);
            
            $group = $request->input('value', 'all');
            $format = $request->input('format', 'excel');

            // Create query without executing it
            $query = Member::select([
                'id', 'first_name', 'last_name', 'date_of_birth', 
                'gender', 'phone', 'email', 'local_church', 'church_group', 
                'membership_status', 'membership_date'
            ]);

            if ($group !== 'all') {
                $query->where('church_group', $group);
            }
            
            $query->orderBy('church_group')->orderBy('last_name');

            $filename = $group === 'all' ? "all-members-by-group" : Str::slug($group) . "-group-members";

            return $this->exportMembersDataFromQuery($query, $format, $filename, ['church_group' => $group]);
        } catch (\Exception $e) {
            Log::error('Export by church group failed: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export members by age group
     */
    public function exportByAgeGroup(Request $request)
    {
        $ageGroup = $request->input('value', 'all');
        $format = $request->input('format', 'excel');

        $query = Member::query();

        if ($ageGroup !== 'all') {
            switch ($ageGroup) {
                case '0-17':
                    $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 0 AND 17');
                    break;
                case '18-30':
                    $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 30');
                    break;
                case '31-50':
                    $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 31 AND 50');
                    break;
                case '51-70':
                    $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 51 AND 70');
                    break;
                case '70+':
                    $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) > 70');
                    break;
            }
        }

        $members = $query->orderBy('date_of_birth')->get();
        $filename = $ageGroup === 'all' ? "all-members-by-age-{$format}" : "members-age-{$ageGroup}-{$format}";

        return $this->exportMembersData($members, $format, $filename);
    }

    /**
     * Export members by gender
     */
    public function exportByGender(Request $request)
    {
        $gender = $request->input('value', 'all');
        $format = $request->input('format', 'excel');

        if ($gender === 'all') {
            $members = Member::orderBy('gender')->orderBy('last_name')->get();
            $filename = "all-members-by-gender-{$format}";
        } else {
            $members = Member::where('gender', $gender)->orderBy('last_name')->get();
            $filename = strtolower($gender) . "-members-{$format}";
        }

        return $this->exportMembersData($members, $format, $filename);
    }

    /**
     * Export members by membership status
     */
    public function exportByMembershipStatus(Request $request)
    {
        try {
            // Increase memory limit and execution time for exports
            ini_set('memory_limit', '1G');
            set_time_limit(300);
            
            $status = $request->input('value', 'all');
            $format = $request->input('format', 'excel');

            // Create query without executing it
            $query = Member::select([
                'id', 'first_name', 'last_name', 'date_of_birth', 
                'gender', 'phone', 'email', 'local_church', 'church_group', 
                'membership_status', 'membership_date'
            ]);

            if ($status !== 'all') {
                $query->where('membership_status', $status);
            }
            
            $query->orderBy('membership_status')->orderBy('last_name');

            $filename = $status === 'all' ? "all-members-by-status" : $status . "-members";

            return $this->exportMembersDataFromQuery($query, $format, $filename, ['membership_status' => $status]);
        } catch (\Exception $e) {
            Log::error('Export by membership status failed: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export members by marital status
     */
    public function exportByMaritalStatus(Request $request)
    {
        $status = $request->input('value', 'all');
        $format = $request->input('format', 'excel');

        if ($status === 'all') {
            $members = Member::orderBy('marital_status')->orderBy('last_name')->get();
            $filename = "all-members-by-marital-status-{$format}";
        } else {
            $members = Member::where('marital_status', $status)->orderBy('last_name')->get();
            $filename = Str::slug($status) . "-members-{$format}";
        }

        return $this->exportMembersData($members, $format, $filename);
    }

    /**
     * Export members by state
     */
    public function exportByState(Request $request)
    {
        $state = $request->input('value', 'all');
        $format = $request->input('format', 'excel');

        if ($state === 'all') {
            $members = Member::orderBy('state')->orderBy('last_name')->get();
            $filename = "all-members-by-state-{$format}";
        } else {
            $members = Member::where('state', $state)->orderBy('last_name')->get();
            $filename = Str::slug($state) . "-state-members-{$format}";
        }

        return $this->exportMembersData($members, $format, $filename);
    }

    /**
     * Export members by LGA
     */
    public function exportByLga(Request $request)
    {
        $lga = $request->input('value', 'all');
        $format = $request->input('format', 'excel');

        if ($lga === 'all') {
            $members = Member::orderBy('lga')->orderBy('last_name')->get();
            $filename = "all-members-by-lga-{$format}";
        } else {
            $members = Member::where('lga', $lga)->orderBy('last_name')->get();
            $filename = Str::slug($lga) . "-lga-members-{$format}";
        }

        return $this->exportMembersData($members, $format, $filename);
    }

    /**
     * Export members by education level
     */
    public function exportByEducationLevel(Request $request)
    {
        $level = $request->input('value', 'all');
        $format = $request->input('format', 'excel');

        if ($level === 'all') {
            $members = Member::orderBy('education_level')->orderBy('last_name')->get();
            $filename = "all-members-by-education-{$format}";
        } else {
            $members = Member::where('education_level', $level)->orderBy('last_name')->get();
            $filename = Str::slug($level) . "-education-members-{$format}";
        }

        return $this->exportMembersData($members, $format, $filename);
    }

    /**
     * Export members by occupation
     */
    public function exportByOccupation(Request $request)
    {
        $occupation = $request->input('value', 'all');
        $format = $request->input('format', 'excel');

        if ($occupation === 'all') {
            $members = Member::orderBy('occupation')->orderBy('last_name')->get();
            $filename = "all-members-by-occupation-{$format}";
        } else {
            $members = Member::where('occupation', $occupation)->orderBy('last_name')->get();
            $filename = Str::slug($occupation) . "-occupation-members-{$format}";
        }

        return $this->exportMembersData($members, $format, $filename);
    }

    /**
     * Export members by year joined
     */
    public function exportByYearJoined(Request $request)
    {
        $year = $request->input('value', 'all');
        $format = $request->input('format', 'excel');

        if ($year === 'all') {
            $members = Member::orderBy('date_joined')->orderBy('last_name')->get();
            $filename = "all-members-by-year-joined-{$format}";
        } else {
            $members = Member::whereYear('date_joined', $year)->orderBy('last_name')->get();
            $filename = "members-joined-{$year}-{$format}";
        }

        return $this->exportMembersData($members, $format, $filename);
    }

    /**
     * Format phone number for export to preserve original formatting
     */
    private function formatPhoneForExport($phone): string
    {
        if (!$phone) return '';
        
        // For CSV, we can use quotes to preserve phone format
        $phone = trim($phone);
        
        // If phone contains special characters or starts with +, wrap in quotes
        if (preg_match('/^[\+\-\(\)\s\d]+$/', $phone)) {
            return '"' . $phone . '"'; // Wrap in quotes for CSV
        }
        
        return $phone;
    }
}
