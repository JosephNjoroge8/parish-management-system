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
use App\Exports\OptimizedMembersExport;
use App\Exports\MarriagesExport;
use App\Exports\SacramentsExport;
use App\Exports\TithesExport;
use App\Exports\ComprehensiveReportExport;
use App\Helpers\DatabaseHelper;

class ReportController extends Controller
{
    public function index()
    {
        try {
            $statistics = $this->getEnhancedParishStatistics();
            $chartData = $this->generateEnhancedChartData();
            
            return Inertia::render('Reports/Index', [
                'statistics' => $statistics,
                'charts' => $chartData,
                'filters' => $this->getAvailableFilters()
            ]);
        } catch (\Exception $e) {
            Log::error('Reports index failed: ' . $e->getMessage());
            
            // Return with minimal data to prevent page crash
            return Inertia::render('Reports/Index', [
                'statistics' => $this->getMinimalStatistics(),
                'charts' => $this->getMinimalChartData(),
                'filters' => $this->getAvailableFilters()
            ]);
        }
    }
    
    /**
     * Get minimal statistics as fallback
     */
    private function getMinimalStatistics(): array
    {
        return [
            'overview' => [
                'total_members' => Member::count(),
                'new_members' => 0,
                'active_members' => Member::where('membership_status', 'active')->count(),
                'inactive_members' => Member::where('membership_status', 'inactive')->count(),
                'growth_rate' => 0,
            ],
            'demographics' => [
                'age_groups' => ['children' => 0, 'youth' => 0, 'adults' => 0, 'seniors' => 0],
                'gender_distribution' => ['male' => 0, 'female' => 0],
                'marital_status' => [],
                'education_levels' => [],
                'occupations' => [],
            ],
            'church_groups' => [
                'primary_groups' => [],
                'additional_memberships' => [],
                'total_group_memberships' => 0,
            ],
            'local_churches' => [],
            'small_christian_communities' => [],
            'sacraments' => [
                'baptized' => 0,
                'confirmed' => 0,
                'married' => 0,
                'marriage_types' => [],
            ],
            'recent_activity' => [
                'new_registrations' => [],
                'recent_updates' => [],
            ],
            'period_info' => [
                'period' => 'all',
                'generated_at' => now()->toISOString(),
            ],
        ];
    }
    
    /**
     * Get minimal chart data as fallback
     */
    private function getMinimalChartData(): array
    {
        return [
            'monthly_trends' => [],
            'age_distribution' => [],
            'gender_distribution' => [],
            'status_distribution' => [],
            'church_groups_distribution' => [],
            'education_levels_distribution' => [],
            'local_churches_distribution' => [],
            'marriage_types_distribution' => [],
        ];
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
     * Export filtered members based on report criteria with enhanced database integration
     */
    public function exportFilteredMembers(Request $request)
    {
        try {
            // Validate and sanitize input
            $filters = $request->only([
                'church_group', 'local_church', 'education_level', 'gender',
                'matrimony_status', 'occupation', 'age_min', 'age_max',
                'has_baptism', 'has_confirmation', 'tribe', 'small_christian_community',
                'membership_status', 'marital_status', 'search'
            ]);

            $period = $request->input('period', 'all');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $format = $request->input('format', 'excel');
            $category = $request->input('category', 'filtered-members');

            Log::info('Enhanced export filtered members request', [
                'filters' => $filters,
                'period' => $period,
                'format' => $format,
                'user_id' => auth()->id()
            ]);

            // Build comprehensive query with filters
            $query = Member::query()
                ->select([
                    'id', 'first_name', 'middle_name', 'last_name', 'date_of_birth',
                    'gender', 'phone', 'email', 'residence', 'local_church', 'church_group',
                    'small_christian_community', 'membership_status', 'membership_date',
                    'baptism_date', 'confirmation_date', 'matrimony_status', 'marriage_type',
                    'occupation', 'education_level', 'tribe', 'clan', 'created_at', 'updated_at'
                ]);
            
            // Apply comprehensive filters
            $this->applyComprehensiveFilters($query, $filters);
            
            // Apply date filter for registration period
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            } else {
                $query = $this->applyDateFilter($query, $period);
            }

            // Log query for debugging
            Log::info('Filtered members query', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
                'count_estimate' => $query->count()
            ]);

            // Generate filename based on applied filters
            $filename = $this->generateEnhancedFilename($filters, $category);

            // Use chunked export for better performance
            return $this->exportMembersDataFromQuery($query, $format, $filename, $filters);
            
        } catch (\Exception $e) {
            Log::error('Export filtered members failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'error' => 'Export failed: ' . $e->getMessage(),
                'details' => 'Please check the filters and try again.'
            ], 500);
        }
    }

    /**
     * Export members by specific category with comprehensive database integration
     */
    public function exportMembersByCategory(Request $request)
    {
        try {
            $category = $request->input('category'); // 'church_group', 'education_level', etc.
            $value = $request->input('value', 'all'); // specific value for the category
            $format = $request->input('format', 'excel');
            $includeInactive = $request->boolean('include_inactive', false);

            Log::info('Export members by category request', [
                'category' => $category,
                'value' => $value,
                'format' => $format,
                'include_inactive' => $includeInactive
            ]);

            // Build comprehensive query with all necessary fields
            $query = Member::query()
                ->select([
                    'id', 'first_name', 'middle_name', 'last_name', 'date_of_birth',
                    'gender', 'phone', 'email', 'residence', 'local_church', 'church_group',
                    'small_christian_community', 'membership_status', 'membership_date',
                    'baptism_date', 'confirmation_date', 'matrimony_status', 'marriage_type',
                    'occupation', 'education_level', 'tribe', 'clan', 'id_number',
                    'created_at', 'updated_at'
                ]);

            // Apply category-specific filters
            switch ($category) {
                case 'church_group':
                    if ($value !== 'all') {
                        $query->where('church_group', $value);
                    }
                    $query->orderBy('church_group')->orderBy('last_name');
                    break;
                    
                case 'local_church':
                    if ($value !== 'all') {
                        $query->where('local_church', $value);
                    }
                    $query->orderBy('local_church')->orderBy('last_name');
                    break;
                    
                case 'education':
                case 'education_level':
                    if ($value !== 'all') {
                        $query->where('education_level', $value);
                    }
                    $query->orderBy('education_level')->orderBy('last_name');
                    break;
                    
                case 'gender':
                    if ($value !== 'all') {
                        $query->where('gender', $value);
                    }
                    $query->orderBy('gender')->orderBy('last_name');
                    break;
                    
                case 'marital_status':
                case 'matrimony_status':
                    if ($value !== 'all') {
                        $query->where('matrimony_status', $value);
                    }
                    $query->orderBy('matrimony_status')->orderBy('last_name');
                    break;
                    
                case 'membership_status':
                    if ($value !== 'all') {
                        $query->where('membership_status', $value);
                    }
                    $query->orderBy('membership_status')->orderBy('last_name');
                    break;
                    
                case 'occupation':
                    if ($value !== 'all') {
                        $query->where('occupation', 'LIKE', "%{$value}%");
                    }
                    $query->whereNotNull('occupation')
                          ->where('occupation', '!=', '')
                          ->orderBy('occupation')->orderBy('last_name');
                    break;
                    
                case 'tribe':
                    if ($value !== 'all') {
                        $query->where('tribe', $value);
                    }
                    $query->whereNotNull('tribe')
                          ->where('tribe', '!=', '')
                          ->orderBy('tribe')->orderBy('last_name');
                    break;
                    
                case 'small_christian_community':
                    if ($value !== 'all') {
                        $query->where('small_christian_community', $value);
                    }
                    $query->whereNotNull('small_christian_community')
                          ->where('small_christian_community', '!=', '')
                          ->orderBy('small_christian_community')->orderBy('last_name');
                    break;
                    
                case 'age_group':
                    $this->applyEnhancedAgeGroupFilter($query, $value);
                    $query->orderBy('date_of_birth', 'desc');
                    break;
                    
                case 'baptized':
                    $query->whereNotNull('baptism_date')
                          ->where('baptism_date', '!=', '')
                          ->orderBy('baptism_date', 'desc');
                    break;
                    
                case 'confirmed':
                    $query->whereNotNull('confirmation_date')
                          ->where('confirmation_date', '!=', '')
                          ->orderBy('confirmation_date', 'desc');
                    break;
                    
                case 'married':
                    $query->where('matrimony_status', 'married')
                          ->orderBy('marriage_date', 'desc');
                    break;
                    
                case 'marriage_type':
                    if ($value !== 'all') {
                        $query->where('marriage_type', $value);
                    }
                    $query->whereNotNull('marriage_type')
                          ->where('marriage_type', '!=', '')
                          ->orderBy('marriage_type')->orderBy('last_name');
                    break;
                    
                case 'monthly_trends':
                    // For monthly trends, return aggregated data
                    return $this->exportMonthlyTrendsData($format);
                    
                default:
                    Log::warning('Invalid export category requested', ['category' => $category]);
                    return response()->json(['error' => 'Invalid category: ' . $category], 400);
            }

            // Apply membership status filter if not including inactive
            if (!$includeInactive && $category !== 'membership_status') {
                $query->where('membership_status', 'active');
            }

            // Log query details
            $count = $query->count();
            Log::info('Category export query prepared', [
                'category' => $category,
                'value' => $value,
                'count' => $count,
                'sql' => $query->toSql()
            ]);

            // Generate descriptive filename
            $filename = $this->generateCategoryFilename($category, $value, $count);

            // Use chunked export for performance
            return $this->exportMembersDataFromQuery($query, $format, $filename, [$category => $value]);
            
        } catch (\Exception $e) {
            Log::error('Export members by category failed', [
                'error' => $e->getMessage(),
                'category' => $request->input('category'),
                'value' => $request->input('value'),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Export failed: ' . $e->getMessage(),
                'category' => $category ?? 'unknown'
            ], 500);
        }
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
            // For now, redirect Excel exports to CSV to avoid memory issues
            return $this->exportQueryToCSV($query, $filename, $filters);
        } catch (\Exception $e) {
            Log::error('Excel export from query failed: ' . $e->getMessage(), [
                'filename' => $filename,
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Excel export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export query results to CSV using chunked processing
     */
    private function exportQueryToCSV($query, $filename, $filters = [])
    {
        try {
            // Log the query details for debugging
            $count = $query->count();
            Log::info('CSV Export starting', [
                'filename' => $filename,
                'record_count' => $count,
                'filters' => $filters,
                'query' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);

            // For CSV, we'll use a simpler approach with chunked processing
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
            ];

            $callback = function() use ($query, $filename) {
                $file = fopen('php://output', 'w');
                
                // Write headers
                fputcsv($file, [
                    'ID', 'First Name', 'Last Name', 'Date of Birth', 'Gender',
                    'Phone', 'Email', 'Local Church', 'Church Group', 
                    'Membership Status', 'Membership Date'
                ]);

                // Process in chunks to avoid memory issues
                $totalProcessed = 0;
                $query->chunk(1000, function ($members) use ($file, &$totalProcessed) {
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
                        $totalProcessed++;
                    }
                });
                
                Log::info('CSV Export completed', ['records_processed' => $totalProcessed]);

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
            // For now, redirect to CSV to avoid memory issues
            return $this->exportMembersToCSV($members, $filename, $filters);
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
            // Use direct CSV streaming to avoid memory issues
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ];

            $callback = function () use ($members) {
                $file = fopen('php://output', 'w');
                
                // Write headers
                fputcsv($file, [
                    'ID', 'First Name', 'Last Name', 'Date of Birth', 'Gender',
                    'Phone', 'Email', 'Local Church', 'Church Group', 
                    'Membership Status', 'Membership Date'
                ]);

                // Convert collection to array if needed
                if (is_object($members) && method_exists($members, 'chunk')) {
                    // If it's a query builder or model, chunk it
                    $members->chunk(1000, function ($memberChunk) use ($file) {
                        foreach ($memberChunk as $member) {
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
                } else {
                    // If it's a collection, process directly
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
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('CSV export failed: ' . $e->getMessage());
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
     * Export members to PDF format with enhanced database details
     */
    private function exportMembersToPDF(array $filters = [], array $selectedFields = [], array $includeOptions = [], $membersCollection = null)
    {
        try {
            // Use Dompdf for PDF generation
            $pdf = app('dompdf.wrapper');
            
            // Get comprehensive member data with all relationships
            if ($membersCollection !== null) {
                $members = is_array($membersCollection) ? collect($membersCollection) : $membersCollection;
            } else {
                $query = Member::with(['baptismRecord', 'marriageRecord', 'sacraments', 'tithes' => function($query) {
                    $query->latest()->limit(5);
                }]);
                
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
            
            // Prepare comprehensive data for PDF view
            $title = 'Comprehensive Parish Members Report';
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
                'parish_name' => config('app.parish_name', 'Sacred Heart Kandara Parish'),
                'exportDate' => now()->format('Y-m-d H:i:s'),
                'statistics' => [
                    'total_members' => $members->count(),
                    'baptized_members' => $members->where('baptism_date', '!=', null)->count(),
                    'confirmed_members' => $members->where('confirmation_date', '!=', null)->count(),
                    'married_members' => $members->where('matrimony_status', 'married')->count(),
                    'male_members' => $members->where('gender', 'Male')->count(),
                    'female_members' => $members->where('gender', 'Female')->count(),
                ]
            ];
            
            // Generate PDF from enhanced view
            $pdf->loadView('exports.comprehensive-members-pdf', $data);
            $pdf->setPaper('A4', 'landscape');
            
            $filename = 'comprehensive-members-report-' . now()->format('Y-m-d-H-i-s') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            Log::error('Comprehensive PDF export failed: ' . $e->getMessage());
            return response()->json(['error' => 'PDF export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate enhanced filename based on comprehensive filters
     */
    private function generateEnhancedFilename($filters, $category)
    {
        $parts = [];
        $timestamp = now()->format('Y-m-d-H-i-s');
        
        // Add category prefix
        $parts[] = $category;
        
        // Add filter-specific parts
        if (!empty($filters['church_group'])) {
            $parts[] = 'group-' . str_replace([' ', '/'], ['-', '-'], strtolower($filters['church_group']));
        }
        
        if (!empty($filters['local_church'])) {
            $parts[] = 'church-' . str_replace([' ', '/'], ['-', '-'], strtolower($filters['local_church']));
        }
        
        if (!empty($filters['education_level'])) {
            $parts[] = 'edu-' . str_replace([' ', '/'], ['-', '-'], strtolower($filters['education_level']));
        }
        
        if (!empty($filters['gender'])) {
            $parts[] = strtolower($filters['gender']);
        }
        
        if (!empty($filters['membership_status'])) {
            $parts[] = 'status-' . strtolower($filters['membership_status']);
        }
        
        if (!empty($filters['matrimony_status'])) {
            $parts[] = 'marital-' . strtolower($filters['matrimony_status']);
        }
        
        // Add age range if specified
        if (!empty($filters['age_min']) || !empty($filters['age_max'])) {
            $ageRange = 'age';
            if (!empty($filters['age_min'])) $ageRange .= '-from-' . $filters['age_min'];
            if (!empty($filters['age_max'])) $ageRange .= '-to-' . $filters['age_max'];
            $parts[] = $ageRange;
        }
        
        // Add sacrament indicators
        if (isset($filters['has_baptism']) && ($filters['has_baptism'] === true || $filters['has_baptism'] === 'true')) {
            $parts[] = 'baptized';
        }
        
        if (isset($filters['has_confirmation']) && ($filters['has_confirmation'] === true || $filters['has_confirmation'] === 'true')) {
            $parts[] = 'confirmed';
        }
        
        // Join parts and add timestamp
        $filename = !empty($parts) ? implode('-', $parts) : 'members-export';
        
        // Ensure filename is not too long and sanitize
        $filename = substr($filename, 0, 200);
        $filename = preg_replace('/[^a-zA-Z0-9\-_]/', '', $filename);
        
        return $filename . '-' . $timestamp;
    }

    /**
     * Apply enhanced age group filter to query with comprehensive age ranges
     */
    private function applyEnhancedAgeGroupFilter($query, $ageGroup)
    {
        $ageSQL = DatabaseHelper::getAgeSQL('date_of_birth');
        
        switch ($ageGroup) {
            case 'children':
            case '0-12':
                $query->whereRaw("({$ageSQL}) BETWEEN 0 AND 12");
                break;
            case 'youth':
            case '13-24':
                $query->whereRaw("({$ageSQL}) BETWEEN 13 AND 24");
                break;
            case 'young_adults':
            case '18-30':
                $query->whereRaw("({$ageSQL}) BETWEEN 18 AND 30");
                break;
            case 'adults':
            case '25-59':
            case '31-50':
                $query->whereRaw("({$ageSQL}) BETWEEN 25 AND 59");
                break;
            case 'middle_aged':
            case '51-70':
                $query->whereRaw("({$ageSQL}) BETWEEN 51 AND 70");
                break;
            case 'seniors':
            case '60+':
            case '70+':
                $query->whereRaw("({$ageSQL}) >= 60");
                break;
            case 'elderly':
                $query->whereRaw("({$ageSQL}) >= 70");
                break;
            default:
                // Handle custom age ranges like "25-40"
                if (preg_match('/^(\d+)-(\d+)$/', $ageGroup, $matches)) {
                    $minAge = (int)$matches[1];
                    $maxAge = (int)$matches[2];
                    $query->whereRaw("({$ageSQL}) BETWEEN ? AND ?", [$minAge, $maxAge]);
                } elseif (preg_match('/^(\d+)\+$/', $ageGroup, $matches)) {
                    $minAge = (int)$matches[1];
                    $query->whereRaw("({$ageSQL}) >= ?", [$minAge]);
                }
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
        $this->applyComprehensiveFilters($baseQuery, $filters);
        
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
                    'children' => \App\Helpers\DatabaseHelper::getMembersByAgeGroup('children'),
                    'youth' => \App\Helpers\DatabaseHelper::getMembersByAgeGroup('youth'),
                    'adults' => \App\Helpers\DatabaseHelper::getMembersByAgeGroup('adults'),
                    'seniors' => \App\Helpers\DatabaseHelper::getMembersByAgeGroup('seniors'),
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
     * Apply comprehensive filters to query with enhanced database integration
     */
    private function applyComprehensiveFilters($query, $filters)
    {
        // Text search filter
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('middle_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('id_number', 'LIKE', "%{$search}%")
                  ->orWhereRaw('CONCAT(first_name, " ", COALESCE(middle_name, ""), " ", last_name) LIKE ?', ["%{$search}%"]);
            });
        }
        
        // Church and community filters
        if (!empty($filters['church_group'])) {
            $query->where('church_group', $filters['church_group']);
        }
        
        if (!empty($filters['local_church'])) {
            $query->where('local_church', $filters['local_church']);
        }
        
        if (!empty($filters['small_christian_community'])) {
            $query->where('small_christian_community', $filters['small_christian_community']);
        }
        
        // Personal information filters
        if (!empty($filters['education_level'])) {
            $query->where('education_level', $filters['education_level']);
        }
        
        if (!empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }
        
        if (!empty($filters['matrimony_status'])) {
            $query->where('matrimony_status', $filters['matrimony_status']);
        }
        
        if (!empty($filters['marriage_type'])) {
            $query->where('marriage_type', $filters['marriage_type']);
        }
        
        if (!empty($filters['occupation'])) {
            $query->where('occupation', 'LIKE', "%{$filters['occupation']}%");
        }
        
        if (!empty($filters['tribe'])) {
            $query->where('tribe', $filters['tribe']);
        }
        
        if (!empty($filters['clan'])) {
            $query->where('clan', $filters['clan']);
        }
        
        // Membership status filter
        if (!empty($filters['membership_status'])) {
            $query->where('membership_status', $filters['membership_status']);
        }
        
        // Age range filters with proper calculation for SQLite
        if (!empty($filters['age_min'])) {
            $query->whereRaw(DatabaseHelper::getAgeSQL() . ' >= ?', [(int)$filters['age_min']]);
        }
        
        if (!empty($filters['age_max'])) {
            $query->whereRaw(DatabaseHelper::getAgeSQL() . ' <= ?', [(int)$filters['age_max']]);
        }
        
        // Sacrament filters
        if (isset($filters['has_baptism'])) {
            if ($filters['has_baptism'] === true || $filters['has_baptism'] === 'true' || $filters['has_baptism'] === '1') {
                $query->whereNotNull('baptism_date')
                      ->where('baptism_date', '!=', '');
            } elseif ($filters['has_baptism'] === false || $filters['has_baptism'] === 'false' || $filters['has_baptism'] === '0') {
                $query->where(function($q) {
                    $q->whereNull('baptism_date')->orWhere('baptism_date', '');
                });
            }
        }
        
        if (isset($filters['has_confirmation'])) {
            if ($filters['has_confirmation'] === true || $filters['has_confirmation'] === 'true' || $filters['has_confirmation'] === '1') {
                $query->whereNotNull('confirmation_date')
                      ->where('confirmation_date', '!=', '');
            } elseif ($filters['has_confirmation'] === false || $filters['has_confirmation'] === 'false' || $filters['has_confirmation'] === '0') {
                $query->where(function($q) {
                    $q->whereNull('confirmation_date')->orWhere('confirmation_date', '');
                });
            }
        }
        
        // Additional church groups filter (JSON array)
        if (!empty($filters['additional_church_groups']) && is_array($filters['additional_church_groups'])) {
            $query->where(function($q) use ($filters) {
                foreach ($filters['additional_church_groups'] as $group) {
                    $q->orWhereJsonContains('additional_church_groups', $group);
                }
            });
        }
    }

    /**
     * Generate enhanced chart data matching frontend expectations
     */
    private function generateEnhancedChartData($filters = [])
    {
        $baseQuery = Member::query();
        $this->applyComprehensiveFilters($baseQuery, $filters);
        
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
                ['name' => 'Children (0-12)', 'value' => DatabaseHelper::getMembersByAgeGroup('children')],
                ['name' => 'Youth (13-24)', 'value' => DatabaseHelper::getMembersByAgeGroup('youth')],
                ['name' => 'Adults (25-59)', 'value' => DatabaseHelper::getMembersByAgeGroup('adults')], 
                ['name' => 'Seniors (60+)', 'value' => DatabaseHelper::getMembersByAgeGroup('seniors')],
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
            $query->whereRaw(DatabaseHelper::getAgeSQL() . ' >= ?', [$request->input('age_min')]);
        }

        if ($request->has('age_max')) {
            $query->whereRaw(DatabaseHelper::getAgeSQL() . ' <= ?', [$request->input('age_max')]);
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
        $this->applyComprehensiveFilters($query, $filters);

        // Apply age filters
        if ($request->has('age_min')) {
            $query->whereRaw(DatabaseHelper::getAgeSQL() . ' >= ?', [$request->input('age_min')]);
        }

        if ($request->has('age_max')) {
            $query->whereRaw(DatabaseHelper::getAgeSQL() . ' <= ?', [$request->input('age_max')]);
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
        try {
            // Increase memory limit and execution time for exports
            ini_set('memory_limit', '2G');
            ini_set('max_execution_time', 600);
            set_time_limit(600);
            
            $ageGroup = $request->input('value', 'all');
            $format = $request->input('format', 'excel');

            $query = Member::query();

        if ($ageGroup !== 'all') {
            switch ($ageGroup) {
                case '0-17':
                case 'children':
                    $query->whereRaw(DatabaseHelper::getAgeSQL() . ' BETWEEN 0 AND 17');
                    break;
                case '18-30':
                case 'youth':
                    $query->whereRaw(DatabaseHelper::getAgeSQL() . ' BETWEEN 18 AND 30');
                    break;
                case '31-50':
                case 'adults':
                    $query->whereRaw(DatabaseHelper::getAgeSQL() . ' BETWEEN 31 AND 50');
                    break;
                case '51-70':
                case 'seniors':
                    $query->whereRaw(DatabaseHelper::getAgeSQL() . ' BETWEEN 51 AND 70');
                    break;
                case '70+':
                case 'elderly':
                    $query->whereRaw(DatabaseHelper::getAgeSQL() . ' > 70');
                    break;
                default:
                    // If no valid age group, return all members
                    break;
            }
        }

        $members = $query->orderBy('date_of_birth')->get();
        $filename = $ageGroup === 'all' ? "all-members-by-age-{$format}" : "members-age-{$ageGroup}-{$format}";

        return $this->exportMembersData($members, $format, $filename);
        } catch (\Exception $e) {
            Log::error('Export by age group failed: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export members by gender
     */
    public function exportByGender(Request $request)
    {
        try {
            // Increase memory limit and execution time for exports
            ini_set('memory_limit', '2G');
            ini_set('max_execution_time', 600);
            set_time_limit(600);
            
            $gender = $request->input('value', 'all');
            $format = $request->input('format', 'excel');

            // Use query builder instead of getting all records
            $query = Member::query();
            if ($gender !== 'all') {
                $query->where('gender', $gender);
            }
            $query->orderBy('gender')->orderBy('last_name');
            
            $filename = $gender === 'all' ? "all-members-by-gender" : strtolower($gender) . "-members";

            return $this->exportMembersDataFromQuery($query, $format, $filename, ['gender' => $gender]);
        } catch (\Exception $e) {
            Log::error('Export by gender failed: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export members by membership status
     */
    public function exportByMembershipStatus(Request $request)
    {
        try {
            // Increase memory limit and execution time for exports
            ini_set('memory_limit', '2G');
            ini_set('max_execution_time', 600);
            set_time_limit(600);
            
            $status = $request->input('value', 'all');
            $format = $request->input('format', 'excel');

            // Normalize status to lowercase for case-insensitive matching
            if ($status !== 'all') {
                $status = strtolower($status);
            }

            Log::info('Export by membership status requested', [
                'status' => $status,
                'format' => $format,
                'raw_value' => $request->input('value')
            ]);

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
    /**
     * Generate category-specific filename with descriptive information
     */
    private function generateCategoryFilename($category, $value, $count = null)
    {
        $timestamp = now()->format('Y-m-d-H-i-s');
        $categoryName = str_replace('_', '-', $category);
        
        if ($value === 'all') {
            $filename = "all-{$categoryName}-members";
        } else {
            $sanitizedValue = str_replace([' ', '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $value);
            $sanitizedValue = strtolower(trim($sanitizedValue, '-'));
            $filename = "{$categoryName}-{$sanitizedValue}-members";
        }
        
        if ($count !== null) {
            $filename .= "-count-{$count}";
        }
        
        return $filename . '-' . $timestamp;
    }

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

    /**
     * Handle export members data route (for all members export)
     */
    public function exportMembersDataRoute(Request $request)
    {
        try {
            $value = $request->input('value', 'all');
            $format = $request->input('format', 'excel');
            
            if ($value === 'all') {
                $query = Member::query()
                    ->select([
                        'id', 'first_name', 'middle_name', 'last_name', 'date_of_birth',
                        'gender', 'phone', 'email', 'residence', 'local_church', 'church_group',
                        'small_christian_community', 'membership_status', 'membership_date',
                        'baptism_date', 'confirmation_date', 'matrimony_status', 'marriage_type',
                        'occupation', 'education_level', 'tribe', 'clan', 'created_at'
                    ])
                    ->orderBy('membership_status')
                    ->orderBy('local_church')
                    ->orderBy('church_group')
                    ->orderBy('last_name')
                    ->orderBy('first_name');
                
                $filename = "all-members-export-" . now()->format('Y-m-d-H-i-s');
                
                return $this->exportMembersDataFromQuery($query, $format, $filename);
            } else {
                return response()->json(['error' => 'Invalid value parameter'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Export members data route failed: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export members by tribe
     */
    public function exportByTribe(Request $request)
    {
        try {
            $tribe = $request->input('value', 'all');
            $format = $request->input('format', 'excel');

            $query = Member::query();
            if ($tribe !== 'all') {
                $query->where('tribe', $tribe);
            }
            $query->whereNotNull('tribe')
                  ->where('tribe', '!=', '')
                  ->orderBy('tribe')
                  ->orderBy('last_name');
            
            $filename = $tribe === 'all' ? "all-members-by-tribe" : Str::slug($tribe) . "-tribe-members";

            return $this->exportMembersDataFromQuery($query, $format, $filename, ['tribe' => $tribe]);
        } catch (\Exception $e) {
            Log::error('Export by tribe failed: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export members by small Christian community
     */
    public function exportByCommunity(Request $request)
    {
        try {
            $community = $request->input('value', 'all');
            $format = $request->input('format', 'excel');

            $query = Member::query();
            if ($community !== 'all') {
                $query->where('small_christian_community', $community);
            }
            $query->whereNotNull('small_christian_community')
                  ->where('small_christian_community', '!=', '')
                  ->orderBy('small_christian_community')
                  ->orderBy('last_name');
            
            $filename = $community === 'all' ? "all-members-by-community" : Str::slug($community) . "-community-members";

            return $this->exportMembersDataFromQuery($query, $format, $filename, ['community' => $community]);
        } catch (\Exception $e) {
            Log::error('Export by community failed: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export baptized members
     */
    public function exportBaptizedMembers(Request $request)
    {
        try {
            $format = $request->input('format', 'excel');
            $year = $request->input('year', 'all');

            $query = Member::query()
                ->whereNotNull('baptism_date')
                ->where('baptism_date', '!=', '')
                ->orderBy('baptism_date', 'desc');
                
            if ($year !== 'all' && is_numeric($year)) {
                $query->whereYear('baptism_date', $year);
            }
            
            $filename = $year === 'all' ? "all-baptized-members" : "baptized-members-{$year}";

            return $this->exportMembersDataFromQuery($query, $format, $filename, ['sacrament' => 'baptism', 'year' => $year]);
        } catch (\Exception $e) {
            Log::error('Export baptized members failed: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export confirmed members
     */
    public function exportConfirmedMembers(Request $request)
    {
        try {
            $format = $request->input('format', 'excel');
            $year = $request->input('year', 'all');

            $query = Member::query()
                ->whereNotNull('confirmation_date')
                ->where('confirmation_date', '!=', '')
                ->orderBy('confirmation_date', 'desc');
                
            if ($year !== 'all' && is_numeric($year)) {
                $query->whereYear('confirmation_date', $year);
            }
            
            $filename = $year === 'all' ? "all-confirmed-members" : "confirmed-members-{$year}";

            return $this->exportMembersDataFromQuery($query, $format, $filename, ['sacrament' => 'confirmation', 'year' => $year]);
        } catch (\Exception $e) {
            Log::error('Export confirmed members failed: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export married members
     */
    public function exportMarriedMembers(Request $request)
    {
        try {
            $format = $request->input('format', 'excel');
            $year = $request->input('year', 'all');
            $marriageType = $request->input('marriage_type', 'all');

            $query = Member::query()
                ->where('matrimony_status', 'married')
                ->orderBy('marriage_date', 'desc');
                
            if ($year !== 'all' && is_numeric($year)) {
                $query->whereYear('marriage_date', $year);
            }
            
            if ($marriageType !== 'all') {
                $query->where('marriage_type', $marriageType);
            }
            
            $filenameParts = ['married-members'];
            if ($marriageType !== 'all') $filenameParts[] = Str::slug($marriageType);
            if ($year !== 'all') $filenameParts[] = $year;
            
            $filename = implode('-', $filenameParts);

            return $this->exportMembersDataFromQuery($query, $format, $filename, ['matrimony_status' => 'married', 'year' => $year, 'marriage_type' => $marriageType]);
        } catch (\Exception $e) {
            Log::error('Export married members failed: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export comprehensive report with all member data
     */
    public function exportComprehensiveReport(Request $request)
    {
        try {
            $format = $request->input('format', 'excel');
            
            // Use the comprehensive report export class
            if ($format === 'excel') {
                return Excel::download(
                    new ComprehensiveReportExport([]), 
                    'comprehensive-parish-report-' . now()->format('Y-m-d-H-i-s') . '.xlsx'
                );
            }
            
            // For other formats, export all members with comprehensive data
            $query = Member::query()
                ->orderBy('membership_status')
                ->orderBy('local_church')
                ->orderBy('church_group')
                ->orderBy('last_name');
            
            $filename = "comprehensive-parish-report-" . now()->format('Y-m-d-H-i-s');
            
            return $this->exportMembersDataFromQuery($query, $format, $filename, ['type' => 'comprehensive']);
        } catch (\Exception $e) {
            Log::error('Export comprehensive report failed: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export member directory
     */
    public function exportMemberDirectory(Request $request)
    {
        try {
            $format = $request->input('format', 'excel');
            $category = $request->input('category', 'all'); // 'all', 'by_church', 'by_group', 'by_status'
            
            $query = Member::query()
                ->where('membership_status', 'active')
                ->orderBy('local_church')
                ->orderBy('church_group')
                ->orderBy('last_name')
                ->orderBy('first_name');
            
            $filename = "member-directory-{$category}-" . now()->format('Y-m-d-H-i-s');
            
            return $this->exportMembersDataFromQuery($query, $format, $filename, ['type' => 'directory', 'category' => $category]);
        } catch (\Exception $e) {
            Log::error('Export member directory failed: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    // ========================================
    // MISSING ROUTE HANDLER METHODS
    // ========================================

    /**
     * Analytics dashboard
     */
    public function analytics()
    {
        return $this->index();
    }

    /**
     * Dashboard view
     */
    public function dashboard()
    {
        return $this->index();
    }

    /**
     * Members report
     */
    public function membersReport()
    {
        return $this->index();
    }

    /**
     * Families report
     */
    public function familiesReport()
    {
        return $this->index();
    }

    /**
     * Sacraments report
     */
    public function sacramentsReport()
    {
        return $this->index();
    }

    /**
     * Activities report
     */
    public function activitiesReport()
    {
        return $this->index();
    }

    /**
     * Community groups report
     */
    public function communityGroupsReport()
    {
        return $this->index();
    }

    /**
     * Financial report
     */
    public function financialReport()
    {
        return $this->index();
    }

    /**
     * Financial summary
     */
    public function financialSummary()
    {
        return $this->index();
    }

    /**
     * Monthly financial report
     */
    public function monthlyFinancialReport()
    {
        return $this->index();
    }

    /**
     * Yearly financial report
     */
    public function yearlyFinancialReport()
    {
        return $this->index();
    }

    /**
     * Export members report
     */
    public function exportMembersReport(Request $request)
    {
        return $this->exportFilteredMembers($request);
    }

    /**
     * Export families report
     */
    public function exportFamiliesReport(Request $request)
    {
        return $this->exportFilteredMembers($request);
    }

    /**
     * Export sacraments report
     */
    public function exportSacramentsReport(Request $request)
    {
        return $this->exportFilteredMembers($request);
    }

    /**
     * Export activities report
     */
    public function exportActivitiesReport(Request $request)
    {
        return $this->exportFilteredMembers($request);
    }

    /**
     * Export financial report
     */
    public function exportFinancialReport(Request $request)
    {
        return $this->exportFilteredMembers($request);
    }

    /**
     * Custom export handler
     */
    public function customExport(Request $request)
    {
        return $this->exportFilteredMembers($request);
    }

    /**
     * General export handler
     */
    public function export(Request $request)
    {
        return $this->exportFilteredMembers($request);
    }
}
