<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Family;
use App\Exports\MembersExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;

class MemberController extends Controller
{
    // Valid sortable columns based on your existing database
    private const VALID_SORT_COLUMNS = [
        'id', 'first_name', 'middle_name', 'last_name', 'date_of_birth',
        'gender', 'phone', 'email', 'local_church', 'church_group',
        'membership_status', 'membership_date', 'created_at', 'updated_at'
    ];

    /**
     * Display a listing of the members with enhanced search and filtering.
     */
    public function index(Request $request)
    {
        // Validate and sanitize sort parameters to prevent function injection
        $sortField = $this->validateSortField($request->get('sort', 'last_name'));
        $sortDirection = $this->validateSortDirection($request->get('direction', 'asc'));

        $query = Member::query();

        // Enhanced search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        // Apply filters
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

        if ($request->filled('age_group')) {
            $ageGroup = $request->get('age_group');
            $today = \Carbon\Carbon::today();
            
            switch ($ageGroup) {
                case 'children':
                    $query->whereDate('date_of_birth', '>', $today->copy()->subYears(18));
                    break;
                case 'youth':
                    $query->whereDate('date_of_birth', '<=', $today->copy()->subYears(18))
                          ->whereDate('date_of_birth', '>', $today->copy()->subYears(30));
                    break;
                case 'adults':
                    $query->whereDate('date_of_birth', '<=', $today->copy()->subYears(30))
                          ->whereDate('date_of_birth', '>', $today->copy()->subYears(60));
                    break;
                case 'seniors':
                    $query->whereDate('date_of_birth', '<=', $today->copy()->subYears(60));
                    break;
            }
        }

        // Apply validated sorting
        $query->orderBy($sortField, $sortDirection);

        $members = $query->paginate($request->get('per_page', 15))->withQueryString();

        return Inertia::render('Members/Index', [
            'members' => $members,
            'filters' => $request->only([
                'search', 'local_church', 'church_group', 
                'membership_status', 'gender', 'age_group', 'sort', 'direction'
            ]),
            'stats' => $this->getStats(),
        ]);
    }

    /**
     * Show the form for creating a new member.
     */
    public function create()
    {
        return Inertia::render('Members/Create', [
            'families' => Family::select('id', 'family_name')
                ->orderBy('family_name')
                ->get(),
            'filters' => $this->getFilterOptions(),
        ]);
    }

    /**
     * Store a newly created member in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'required|in:male,female',
            'id_number' => 'nullable|string|max:20|unique:members',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:members',
            'residence' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'local_church' => 'required|in:St James Kangemi,St Veronica Pembe Tatu,Our Lady of Consolata Cathedral,St Peter Kiawara,Sacred Heart Kandara',
            'church_group' => 'required|in:PMC,Youth,Young Parents,C.W.A,CMA,Choir,Catholic Action,Pioneer',
            'membership_status' => 'required|in:active,inactive,transferred,deceased',
            'membership_date' => 'nullable|date',
            'baptism_date' => 'nullable|date',
            'confirmation_date' => 'nullable|date',
            'matrimony_status' => 'nullable|in:single,married,divorced,widowed',
            'occupation' => 'nullable|in:employed,self_employed,not_employed',
            'education_level' => 'nullable|string|max:255',
            'family_id' => 'nullable|exists:families,id',
            'parent' => 'nullable|string|max:255',
            'sponsor' => 'nullable|string|max:255',
            'minister' => 'nullable|string|max:255',
            'tribe' => 'nullable|string|max:255',
            'clan' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            $member = Member::create($validated);
            return redirect()->route('members.show', $member->id)
                ->with('success', 'Member created successfully! Member ID: ' . $member->id);
        } catch (\Exception $e) {
            Log::error('Failed to create member: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create member.'])->withInput();
        }
    }

    /**
     * Display the specified member.
     */
    public function show(Member $member)
    {
        return Inertia::render('Members/Show', [
            'member' => $member,
        ]);
    }

    /**
     * Show the form for editing the specified member.
     */
    public function edit(Member $member)
    {
        return Inertia::render('Members/Edit', [
            'member' => $member->load('family'),
            'families' => Family::select('id', 'family_name')
                ->orderBy('family_name')
                ->get(),
            'filters' => $this->getFilterOptions(),
        ]);
    }

    /**
     * Update the specified member in storage.
     */
    public function update(Request $request, Member $member)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'required|in:male,female',
            'id_number' => 'nullable|string|max:20|unique:members,id_number,' . $member->id,
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:members,email,' . $member->id,
            'residence' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'local_church' => 'required|in:St James Kangemi,St Veronica Pembe Tatu,Our Lady of Consolata Cathedral,St Peter Kiawara,Sacred Heart Kandara',
            'church_group' => 'required|in:PMC,Youth,Young Parents,C.W.A,CMA,Choir,Catholic Action,Pioneer',
            'membership_status' => 'required|in:active,inactive,transferred,deceased',
            'membership_date' => 'nullable|date',
            'baptism_date' => 'nullable|date',
            'confirmation_date' => 'nullable|date',
            'matrimony_status' => 'nullable|in:single,married,divorced,widowed',
            'occupation' => 'nullable|in:employed,self_employed,not_employed',
            'education_level' => 'nullable|string|max:255',
            'family_id' => 'nullable|exists:families,id',
            'parent' => 'nullable|string|max:255',
            'sponsor' => 'nullable|string|max:255',
            'minister' => 'nullable|string|max:255',
            'tribe' => 'nullable|string|max:255',
            'clan' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            $member->update($validated);
            return redirect()->route('members.index')->with('success', 'Member updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update member: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update member.'])->withInput();
        }
    }

    /**
     * Remove the specified member from storage.
     */
    public function destroy(Member $member)
    {
        try {
            $member->delete();
            return redirect()->route('members.index')->with('success', 'Member deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete member: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to delete member.']);
        }
    }

    /**
     * Toggle member status.
     */
    public function toggleStatus(Member $member)
    {
        try {
            $newStatus = $member->membership_status === 'active' ? 'inactive' : 'active';
            $member->update(['membership_status' => $newStatus]);

            return redirect()->back()
                           ->with('success', "Member status updated to {$newStatus}!");
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Error updating member status: ' . $e->getMessage());
        }
    }

    /**
     * Quick status toggle for AJAX requests
     */
    public function quickStatusToggle(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'status' => 'required|in:active,inactive,transferred,deceased'
        ]);

        try {
            $member = Member::findOrFail($request->member_id);
            $member->update(['membership_status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => "Member status updated to {$request->status}",
                'member' => [
                    'id' => $member->id,
                    'name' => $member->full_name,
                    'status' => $member->membership_status
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Quick status toggle failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update member status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search members (API endpoint).
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        $members = Member::where(function ($q) use ($query) {
            $q->where('first_name', 'like', "%{$query}%")
              ->orWhere('last_name', 'like', "%{$query}%")
              ->orWhere('middle_name', 'like', "%{$query}%")
              ->orWhere('phone', 'like', "%{$query}%")
              ->orWhere('email', 'like', "%{$query}%");
        })
        ->select('id', 'first_name', 'middle_name', 'last_name', 'phone', 'email', 'church_group', 'local_church')
        ->limit(10)
        ->get();

        return response()->json($members);
    }

    /**
     * Get members by church.
     */
    public function getByChurch(string $church)
    {
        $members = Member::where('local_church', $church)
                        ->select('id', 'first_name', 'middle_name', 'last_name', 'church_group', 'membership_status')
                        ->get();

        return response()->json($members);
    }

    /**
     * Get members by group.
     */
    public function getByGroup(string $group)
    {
        $members = Member::where('church_group', $group)
                        ->select('id', 'first_name', 'middle_name', 'last_name', 'local_church', 'membership_status')
                        ->get();

        return response()->json($members);
    }

    /**
     * Get member statistics.
     */
    public function getStatistics()
    {
        $stats = [
            'total' => Member::count(),
            'active' => Member::where('membership_status', 'active')->count(),
            'by_church' => Member::select('local_church', DB::raw('count(*) as count'))
                                ->groupBy('local_church')
                                ->pluck('count', 'local_church'),
            'by_group' => Member::select('church_group', DB::raw('count(*) as count'))
                               ->groupBy('church_group')
                               ->pluck('count', 'church_group'),
            'by_status' => Member::select('membership_status', DB::raw('count(*) as count'))
                                ->groupBy('membership_status')
                                ->pluck('count', 'membership_status'),
        ];

        return response()->json($stats);
    }

    /**
     * Show the import form
     */
    public function showImport(): InertiaResponse
    {
        return Inertia::render('Members/Import', [
            'stats' => [
                'total_members' => Member::count(),
                'recent_imports' => $this->getRecentImports(),
                'supported_formats' => ['csv', 'xlsx', 'xls'],
                'max_file_size' => '10MB',
                'max_records' => 2000
            ]
        ]);
    }

    /**
     * Show the export form
     */
    public function showExport(): InertiaResponse
    {
        $stats = [
            'total_members' => Member::count(),
            'active_members' => Member::where('membership_status', 'active')->count(),
            'by_church' => Member::select('local_church', DB::raw('count(*) as count'))
                                ->groupBy('local_church')
                                ->pluck('count', 'local_church')
                                ->toArray(),
            'by_group' => Member::select('church_group', DB::raw('count(*) as count'))
                               ->groupBy('church_group')
                               ->pluck('count', 'church_group')
                               ->toArray(),
            'by_status' => Member::select('membership_status', DB::raw('count(*) as count'))
                                ->groupBy('membership_status')
                                ->pluck('count', 'membership_status')
                                ->toArray(),
        ];

        $filterOptions = [
            'local_churches' => ['Kangemi', 'Pembe Tatu', 'Cathedral', 'Kiawara', 'Kandara'],
            'church_groups' => [
                ['value' => 'PMC', 'label' => 'PMC (Pontifical Missionary Childhood)'],
                ['value' => 'Youth', 'label' => 'Youth'],
                ['value' => 'Young Parents', 'label' => 'Young Parents'],
                ['value' => 'C.W.A', 'label' => 'C.W.A'],
                ['value' => 'CMA', 'label' => 'CMA'],
                ['value' => 'Choir', 'label' => 'Choir']
            ],
            'membership_statuses' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
                ['value' => 'transferred', 'label' => 'Transferred'],
                ['value' => 'deceased', 'label' => 'Deceased']
            ],
        ];

        return Inertia::render('Members/Export', [
            'stats' => $stats,
            'filterOptions' => $filterOptions,
            'recentExports' => $this->getRecentExports()
        ]);
    }

    /**
     * Enhanced import with comprehensive validation and error handling
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240', // 10MB max
            'update_existing' => 'boolean',
            'skip_duplicates' => 'boolean',
            'validate_families' => 'boolean',
        ]);

        try {
            $file = $request->file('file');
            $updateExisting = $request->boolean('update_existing', false);
            $skipDuplicates = $request->boolean('skip_duplicates', true);
            $validateFamilies = $request->boolean('validate_families', false);
            
            // Store file temporarily
            $path = $file->store('imports', 'local');
            $fullPath = storage_path('app/' . $path);
            
            // Detect file type and parse accordingly
            $extension = strtolower($file->getClientOriginalExtension());
            $data = $this->parseImportFile($fullPath, $extension);
            
            if (empty($data)) {
                throw new \Exception('No valid data found in the uploaded file.');
            }

            if (count($data) > 2000) {
                throw new \Exception('File contains too many records. Maximum allowed is 2000 records.');
            }

            // Validate and process data
            $result = $this->processImportData($data, $updateExisting, $skipDuplicates, $validateFamilies);
            
            // Clean up temporary file
            Storage::disk('local')->delete($path);
            
            // Log import activity
            $this->logImportActivity($result, $request->user()->id);
            
            return response()->json([
                'success' => true,
                'message' => $this->formatImportMessage($result),
                'imported' => $result['imported'],
                'updated' => $result['updated'],
                'skipped' => $result['skipped'],
                'errors' => $result['errors'],
                'total_processed' => $result['total_processed'],
                'warnings' => $result['warnings']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Import failed: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'file_name' => $file->getClientOriginalName() ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0
            ], 422);
        }
    }

    /**
     * Parse import file based on extension
     */
    private function parseImportFile(string $path, string $extension): array
    {
        switch ($extension) {
            case 'csv':
            case 'txt':
                return $this->parseCsvFile($path);
            case 'xlsx':
            case 'xls':
                return $this->parseExcelFile($path);
            default:
                throw new \Exception('Unsupported file format: ' . $extension);
        }
    }

    /**
     * Parse CSV file
     */
    private function parseCsvFile(string $path): array
    {
        $data = [];
        $handle = fopen($path, 'r');
        
        if (!$handle) {
            throw new \Exception('Unable to read the uploaded file.');
        }

        // Read header
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new \Exception('Invalid CSV file format - no headers found.');
        }

        // Normalize headers
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);
        
        // Read data rows
        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            if (count($row) !== count($headers)) {
                Log::warning("Row {$rowNumber}: Column count mismatch");
                continue;
            }
            
            $data[] = array_combine($headers, $row);
        }
        
        fclose($handle);
        return $data;
    }

    /**
     * Parse Excel file (simplified version - you might want to use Laravel Excel)
     */
    private function parseExcelFile(string $path): array
    {
        // For now, convert to CSV and parse
        // In production, you should use Laravel Excel package
        throw new \Exception('Excel file support requires Laravel Excel package. Please use CSV format.');
    }

    /**
     * Process import data with comprehensive validation
     */
    private function processImportData(array $data, bool $updateExisting, bool $skipDuplicates, bool $validateFamilies): array
    {
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $warnings = [];
        $totalProcessed = 0;

        // Prepare families cache for validation
        $familiesCache = [];
        if ($validateFamilies) {
            $familiesCache = Family::pluck('id', 'family_name')->toArray();
        }

        DB::beginTransaction();
        
        try {
            foreach ($data as $index => $row) {
                $totalProcessed++;
                $rowNumber = $index + 2; // Account for header row
                
                try {
                    // Validate required fields
                    $validationResult = $this->validateMemberRow($row, $rowNumber);
                    if (!$validationResult['valid']) {
                        $errors = array_merge($errors, $validationResult['errors']);
                        $skipped++;
                        continue;
                    }
                    
                    // Prepare member data
                    $memberData = $this->prepareMemberData($row, $familiesCache, $validateFamilies);
                    
                    // Check for existing member
                    $existingMember = $this->findExistingMember($memberData);
                    
                    if ($existingMember) {
                        if ($updateExisting) {
                            $this->updateMemberRecord($existingMember, $memberData);
                            $updated++;
                        } elseif ($skipDuplicates) {
                            $warnings[] = "Row {$rowNumber}: Member '{$memberData['first_name']} {$memberData['last_name']}' already exists - skipped";
                            $skipped++;
                        } else {
                            $errors[] = "Row {$rowNumber}: Member '{$memberData['first_name']} {$memberData['last_name']}' already exists";
                            $skipped++;
                        }
                    } else {
                        // Create new member
                        Member::create($memberData);
                        $imported++;
                    }
                    
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                    $skipped++;
                }
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'warnings' => $warnings,
            'total_processed' => $totalProcessed
        ];
    }

    /**
     * Validate member row data
     */
    private function validateMemberRow(array $row, int $rowNumber): array
    {
        $errors = [];
        
        // Required fields mapping (CSV header => validation rule)
        $requiredFields = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:Male,Female', // Fixed: Use consistent capitalization
            'local_church' => 'required|in:Kangemi,Pembe Tatu,Cathedral,Kiawara,Kandara',
            'church_group' => 'required|in:PMC,Youth,Young Parents,C.W.A,CMA,Choir',
        ];

        // Optional fields with validation
        $optionalFields = [
            'middle_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'id_number' => 'nullable|string|max:20',
            'membership_status' => 'nullable|in:active,inactive,transferred,deceased',
            'occupation' => 'nullable|in:employed,self_employed,not_employed',
            'residence' => 'nullable|string|max:500',
        ];

        $allFields = array_merge($requiredFields, $optionalFields);
        
        foreach ($allFields as $field => $rules) {
            $value = $row[$field] ?? null;
            
            // Skip validation for nullable fields that are empty
            if (str_contains($rules, 'nullable') && empty($value)) {
                continue;
            }
            
            $validator = Validator::make([$field => $value], [$field => $rules]);
            
            if ($validator->fails()) {
                foreach ($validator->errors()->get($field) as $error) {
                    $errors[] = "Row {$rowNumber}: {$field} - {$error}";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Prepare member data for database insertion
     */
    private function prepareMemberData(array $row, array $familiesCache, bool $validateFamilies): array
    {
        $memberData = [
            'first_name' => trim($row['first_name']),
            'middle_name' => !empty($row['middle_name']) ? trim($row['middle_name']) : null,
            'last_name' => trim($row['last_name']),
            'date_of_birth' => \Carbon\Carbon::parse($row['date_of_birth'])->format('Y-m-d'),
            'gender' => ucfirst(strtolower(trim($row['gender']))), // Fixed: Ensure proper capitalization
            'local_church' => trim($row['local_church']),
            'church_group' => trim($row['church_group']),
            'membership_status' => !empty($row['membership_status']) ? trim($row['membership_status']) : 'active',
            'membership_date' => !empty($row['membership_date']) ? 
                \Carbon\Carbon::parse($row['membership_date'])->format('Y-m-d') : 
                now()->format('Y-m-d'),
            'phone' => !empty($row['phone']) ? $this->formatPhoneNumber(trim($row['phone'])) : null,
            'email' => !empty($row['email']) ? strtolower(trim($row['email'])) : null,
            'id_number' => !empty($row['id_number']) ? trim($row['id_number']) : null,
            'occupation' => !empty($row['occupation']) ? trim($row['occupation']) : null,
            'residence' => !empty($row['residence']) ? trim($row['residence']) : null,
            'sponsor' => !empty($row['sponsor']) ? trim($row['sponsor']) : null,
            'parent' => !empty($row['parent']) ? trim($row['parent']) : null,
            'minister' => !empty($row['minister']) ? trim($row['minister']) : null,
            'tribe' => !empty($row['tribe']) ? trim($row['tribe']) : null,
            'clan' => !empty($row['clan']) ? trim($row['clan']) : null,
            'education_level' => !empty($row['education_level']) ? trim($row['education_level']) : null,
            'matrimony_status' => !empty($row['matrimony_status']) ? trim($row['matrimony_status']) : null,
            'baptism_date' => !empty($row['baptism_date']) ? 
                \Carbon\Carbon::parse($row['baptism_date'])->format('Y-m-d') : null,
            'confirmation_date' => !empty($row['confirmation_date']) ? 
                \Carbon\Carbon::parse($row['confirmation_date'])->format('Y-m-d') : null,
            'emergency_contact' => !empty($row['emergency_contact']) ? trim($row['emergency_contact']) : null,
            'emergency_phone' => !empty($row['emergency_phone']) ? 
                $this->formatPhoneNumber(trim($row['emergency_phone'])) : null,
            'notes' => !empty($row['notes']) ? trim($row['notes']) : null,
        ];

        // Handle family assignment
        if (!empty($row['family_name']) && $validateFamilies) {
            $familyName = trim($row['family_name']);
            if (isset($familiesCache[$familyName])) {
                $memberData['family_id'] = $familiesCache[$familyName];
            }
        }

        return $memberData;
    }

    /**
     * Format phone number to consistent format
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Handle Kenyan phone numbers
        if (strlen($phone) === 9 && $phone[0] === '7') {
            return '+254' . $phone;
        } elseif (strlen($phone) === 10 && $phone[0] === '0') {
            return '+254' . substr($phone, 1);
        } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === '254') {
            return '+' . $phone;
        } elseif (strlen($phone) === 13 && substr($phone, 0, 4) === '2547') {
            return '+' . $phone;
        }
        
        return $phone; // Return as is if format not recognized
    }

    /**
     * Find existing member by email, phone, or ID number
     */
    private function findExistingMember(array $memberData): ?Member
    {
        $query = Member::query();
        
        if (!empty($memberData['email'])) {
            $query->orWhere('email', $memberData['email']);
        }
        
        if (!empty($memberData['phone'])) {
            $query->orWhere('phone', $memberData['phone']);
        }
        
        if (!empty($memberData['id_number'])) {
            $query->orWhere('id_number', $memberData['id_number']);
        }
        
        return $query->first();
    }

    /**
     * Update existing member record
     */
    private function updateMemberRecord(Member $member, array $memberData): void
    {
        // Don't update certain critical fields
        unset($memberData['created_at']);
        
        $member->update($memberData);
    }

    /**
     * Export members to various formats
     */
    public function export(Request $request)
    {
        try {
            // Validate the format
            $format = $request->get('format', 'csv');
            if (!in_array($format, ['csv', 'excel', 'pdf'])) {
                return response()->json(['error' => 'Invalid export format'], 400);
            }

            $filters = $this->getExportFilters($request);
            $selectedFields = $this->getSelectedFields($request);
            $includeOptions = $this->getIncludeOptions($request);

            // Log the export attempt
            Log::info('Export attempt', [
                'format' => $format,
                'filters' => $filters,
                'user_id' => Auth::id(),
            ]);

            // Generate filename
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "members_export_{$timestamp}";

            return match($format) {
                'excel' => Excel::download(
                    new MembersExport($filters, $selectedFields, $includeOptions),
                    "{$filename}.xlsx"
                ),
                'csv' => Excel::download(
                    new MembersExport($filters, $selectedFields, $includeOptions),
                    "{$filename}.csv",
                    \Maatwebsite\Excel\Excel::CSV,
                    [
                        'Content-Type' => 'text/csv',
                        'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
                    ]
                ),
                'pdf' => $this->exportToPdf($filters, $selectedFields, $includeOptions, $filename),
                default => response()->json(['error' => 'Invalid export format'], 400),
            };

        } catch (\Exception $e) {
            Log::error('Export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::user()?->id,
            ]);
            
            return response()->json([
                'error' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportPage()
    {
        $stats = [
            'total_members' => Member::count(),
            'by_church' => Member::groupBy('local_church')
                ->selectRaw('local_church, count(*) as count')
                ->pluck('count', 'local_church')
                ->toArray(),
            'by_group' => Member::groupBy('church_group')
                ->selectRaw('church_group, count(*) as count')
                ->pluck('count', 'church_group')
                ->toArray(),
            'by_status' => Member::groupBy('membership_status')
                ->selectRaw('membership_status, count(*) as count')
                ->pluck('count', 'membership_status')
                ->toArray(),
            'recent_exports' => [],
        ];

        $filters = [
            'local_churches' => Member::distinct()->pluck('local_church')->filter()->values()->toArray(),
            'church_groups' => Member::distinct()
                ->pluck('church_group')
                ->filter()
                ->map(fn($group) => ['value' => $group, 'label' => $group])
                ->values()
                ->toArray(),
            'membership_statuses' => Member::distinct()
                ->pluck('membership_status')
                ->filter()
                ->map(fn($status) => ['value' => $status, 'label' => $status])
                ->values()
                ->toArray(),
            'genders' => [
                ['value' => 'Male', 'label' => 'Male'],
                ['value' => 'Female', 'label' => 'Female'],
            ],
        ];

        return Inertia::render('Members/Export', [
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    public function exportPreview(Request $request)
    {
        try {
            $filters = $this->getExportFilters($request);
            
            $query = Member::query();
            $this->applyFilters($query, $filters);

            $totalCount = $query->count();
            $previewData = $query->limit(10)->get()->map(function ($member) {
                return [
                    'id' => $member->id,
                    'full_name' => $member->full_name,
                    'age' => $member->age,
                    'gender' => $member->gender,
                    'church_group' => $member->church_group,
                    'membership_status' => $member->membership_status,
                    'local_church' => $member->local_church,
                    'phone' => $member->phone,
                    'email' => $member->email,
                    'family_name' => $member->family_name,
                ];
            });

            return response()->json([
                'preview_data' => $previewData,
                'showing' => min(10, $totalCount),
                'total_count' => $totalCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Export preview failed: ' . $e->getMessage());
            return response()->json(['error' => 'Preview failed: ' . $e->getMessage()], 500);
        }
    }

    private function validateSortField(string $field): string
    {
        // Critical fix: Check for function injection
        if (str_contains($field, 'function') || str_contains($field, '[native code]') || str_contains($field, '()')) {
            Log::warning('Potential function injection detected in sort field', ['field' => $field]);
            return 'last_name';
        }

        return in_array($field, self::VALID_SORT_COLUMNS) ? $field : 'last_name';
    }

    private function validateSortDirection(string $direction): string
    {
        return in_array(strtolower($direction), ['asc', 'desc']) ? strtolower($direction) : 'asc';
    }

    private function exportToPdf($filters, $selectedFields, $includeOptions, $filename)
    {
        $query = Member::query();
        
        // Apply filters
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }
        if (!empty($filters['local_church'])) {
            $query->byChurch($filters['local_church']);
        }
        if (!empty($filters['church_group'])) {
            $query->byGroup($filters['church_group']);
        }
        if (!empty($filters['membership_status'])) {
            $query->byStatus($filters['membership_status']);
        }
        if (!empty($filters['gender'])) {
            $query->byGender($filters['gender']);
        }
        if (!empty($filters['age_group'])) {
            $query->byAgeGroup($filters['age_group']);
        }

        $members = $query->get();

        $pdf = Pdf::loadView('exports.members-pdf', [
            'members' => $members,
            'selectedFields' => $selectedFields,
            'includeOptions' => $includeOptions,
            'filters' => $filters,
        ]);

        $pdf->setPaper('A4', 'landscape');
        return $pdf->download("{$filename}.pdf");
    }

    private function getStats(): array
    {
        try {
            return [
                'total_members' => Member::count(),
                'by_church' => Member::groupBy('local_church')
                    ->selectRaw('local_church, count(*) as count')
                    ->pluck('count', 'local_church')
                    ->toArray(),
                'by_group' => Member::groupBy('church_group')
                    ->selectRaw('church_group, count(*) as count')
                    ->pluck('count', 'church_group')
                    ->toArray(),
                'by_status' => Member::groupBy('membership_status')
                    ->selectRaw('membership_status, count(*) as count')
                    ->pluck('count', 'membership_status')
                    ->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get stats: ' . $e->getMessage());
            return [
                'total_members' => 0,
                'by_church' => [],
                'by_group' => [],
                'by_status' => [],
            ];
        }
    }

    private function getExportStats(): array
    {
        return $this->getStats();
    }

    private function getFilterOptions(): array
    {
        try {
            return [
                'local_churches' => [
                    ['value' => 'St James Kangemi', 'label' => 'St James Kangemi'],
                    ['value' => 'St Veronica Pembe Tatu', 'label' => 'St Veronica Pembe Tatu'],
                    ['value' => 'Our Lady of Consolata Cathedral', 'label' => 'Our Lady of Consolata Cathedral'],
                    ['value' => 'St Peter Kiawara', 'label' => 'St Peter Kiawara'],
                    ['value' => 'Sacred Heart Kandara', 'label' => 'Sacred Heart Kandara'],
                ],
                'church_groups' => [
                    ['value' => 'PMC', 'label' => 'PMC (Pontifical Missionary Childhood)'],
                    ['value' => 'Youth', 'label' => 'Youth'],
                    ['value' => 'Young Parents', 'label' => 'Young Parents'],
                    ['value' => 'C.W.A', 'label' => 'C.W.A (Catholic Women Association)'],
                    ['value' => 'CMA', 'label' => 'CMA (Catholic Men Association)'],
                    ['value' => 'Choir', 'label' => 'Choir'],
                    ['value' => 'Catholic Action', 'label' => 'Catholic Action'],
                    ['value' => 'Pioneer', 'label' => 'Pioneer'],
                ],
                'membership_statuses' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                    ['value' => 'transferred', 'label' => 'Transferred'],
                    ['value' => 'deceased', 'label' => 'Deceased'],
                ],
                'genders' => [
                    ['value' => 'male', 'label' => 'Male'],
                    ['value' => 'female', 'label' => 'Female'],
                ],
                'families' => Family::select('id', 'family_name')
                    ->orderBy('family_name')
                    ->get()
                    ->map(fn($family) => [
                        'value' => $family->id,
                        'label' => $family->family_name
                    ])
                    ->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get filter options: ' . $e->getMessage());
            return [
                'local_churches' => [],
                'church_groups' => [],
                'membership_statuses' => [],
                'genders' => [
                    ['value' => 'male', 'label' => 'Male'],
                    ['value' => 'female', 'label' => 'Female'],
                ],
                'families' => [],
            ];
        }
    }

    /**
     * Show the import template
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="members_import_template.csv"',
            'Cache-Control' => 'no-cache, must-revalidate',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'first_name', 'middle_name', 'last_name', 'date_of_birth', 'gender',
                'phone', 'email', 'id_number', 'local_church', 'church_group',
                'membership_status', 'membership_date', 'residence', 'occupation', 
                'family_name', 'emergency_contact', 'emergency_phone', 'baptism_date',
                'confirmation_date', 'matrimony_status', 'notes'
            ]);

            // Sample data with proper formatting
            fputcsv($file, [
                'John', 'Mwangi', 'Doe', '1990-01-15', 'Male', // Fixed: Use 'Male' instead of 'male'
                '+254712345678', 'john.doe@email.com', '12345678', 'Kangemi', 'CMA',
                'active', '2024-01-01', 'Kangemi Estate House 123', 'employed',
                'Doe Family', 'Jane Doe', '+254798765432', '2010-05-20',
                '2015-08-15', 'married', 'Sample member record'
            ]);
            
            fputcsv($file, [
                'Mary', 'Wanjiku', 'Smith', '1985-05-20', 'Female', // Fixed: Use 'Female' instead of 'female'
                '+254798765432', 'mary.smith@email.com', '87654321', 'Cathedral', 'C.W.A',
                'active', '2024-01-01', 'Cathedral Area Apt 45', 'self_employed',
                'Smith Family', 'Peter Smith', '+254723456789', '2005-03-10',
                '2012-12-08', 'married', 'Another sample record'
            ]);
            
            fclose($file);
        };

        return Response::streamDownload($callback, 'members_import_template.csv', $headers);
    }

    /**
     * Bulk delete members
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'exists:members,id'
        ]);

        try {
            $deletedCount = Member::whereIn('id', $request->member_ids)->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} members.",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk delete failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Bulk delete failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent import activities
     */
    private function getRecentImports(): array
    {
        // You can implement this to track import history
        // For now, return an empty array or sample data
        return [
            [
                'date' => now()->subDays(1)->format('Y-m-d H:i:s'),
                'filename' => 'members_import_2024.csv',
                'records_imported' => 25,
                'status' => 'completed'
            ],
            [
                'date' => now()->subDays(3)->format('Y-m-d H:i:s'),
                'filename' => 'youth_members.csv',
                'records_imported' => 12,
                'status' => 'completed'
            ]
        ];
    }

    /**
     * Get recent export activities
     */
    private function getRecentExports(): array
    {
        // You can implement this to track export history
        // For now, return an empty array or sample data
        return [
            [
                'date' => now()->subHours(2)->format('Y-m-d H:i:s'),
                'filename' => 'members_export_2024-01-15.csv',
                'records_exported' => 150,
                'format' => 'csv',
                'status' => 'completed'
            ],
            [
                'date' => now()->subDays(1)->format('Y-m-d H:i:s'),
                'filename' => 'active_members.pdf',
                'records_exported' => 120,
                'format' => 'pdf',
                'status' => 'completed'
            ]
        ];
    }

    /**
     * Format import result message
     */
    private function formatImportMessage(array $result): string
    {
        $parts = [];
        
        if ($result['imported'] > 0) {
            $parts[] = "{$result['imported']} imported";
        }
        
        if ($result['updated'] > 0) {
            $parts[] = "{$result['updated']} updated";
        }
        
        if ($result['skipped'] > 0) {
            $parts[] = "{$result['skipped']} skipped";
        }
        
        return 'Import completed: ' . implode(', ', $parts);
    }

    /**
     * Log import activity
     */
    private function logImportActivity(array $result, int $userId): void
    {
        Log::info('Member import completed', [
            'user_id' => $userId,
            'imported' => $result['imported'],
            'updated' => $result['updated'],
            'skipped' => $result['skipped'],
            'errors_count' => count($result['errors']),
            'warnings_count' => count($result['warnings']),
            'total_processed' => $result['total_processed']
        ]);
    }

    /**
     * Get export filters from request
     */
    private function getExportFilters(Request $request): array
    {
        return $request->only([
            'search', 'local_church', 'church_group', 
            'membership_status', 'gender', 'age_group'
        ]);
    }

    /**
     * Get selected fields from request
     */
    private function getSelectedFields(Request $request): array
    {
        $defaultFields = [
            'first_name', 'middle_name', 'last_name', 'date_of_birth', 'gender',
            'phone', 'email', 'local_church', 'church_group', 'membership_status'
        ];

        $selectedFields = $request->get('selected_fields', $defaultFields);
        
        // Ensure we always have at least the basic fields
        if (empty($selectedFields)) {
            return $defaultFields;
        }

        return is_array($selectedFields) ? $selectedFields : $defaultFields;
    }

    /**
     * Get include options from request
     */
    private function getIncludeOptions(Request $request): array
    {
        return [
            'include_photos' => $request->boolean('include_photos', false),
            'include_statistics' => $request->boolean('include_statistics', true),
            'include_summary' => $request->boolean('include_summary', true),
            'include_filters_applied' => $request->boolean('include_filters_applied', true),
        ];
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
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

        if (!empty($filters['age_group'])) {
            $ageGroup = $filters['age_group'];
            $today = \Carbon\Carbon::today();
            
            switch ($ageGroup) {
                case 'children':
                    $query->whereDate('date_of_birth', '>', $today->copy()->subYears(18));
                    break;
                case 'youth':
                    $query->whereDate('date_of_birth', '<=', $today->copy()->subYears(18))
                          ->whereDate('date_of_birth', '>', $today->copy()->subYears(30));
                    break;
                case 'adults':
                    $query->whereDate('date_of_birth', '<=', $today->copy()->subYears(30))
                          ->whereDate('date_of_birth', '>', $today->copy()->subYears(60));
                    break;
                case 'seniors':
                    $query->whereDate('date_of_birth', '<=', $today->copy()->subYears(60));
                    break;
            }
        }
    }
}
