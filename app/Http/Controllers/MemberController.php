<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Family;
use App\Models\BaptismRecord;
use App\Models\MarriageRecord;
use App\Models\Sacrament;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Helpers\DatabaseCompatibilityHelper;

class MemberController extends Controller
{
    // Valid sortable columns based on your existing database
    private const VALID_SORT_COLUMNS = [
        'id', 'first_name', 'middle_name', 'last_name', 'date_of_birth',
        'gender', 'phone', 'email', 'local_church', 'church_group',
        'membership_status', 'membership_date', 'created_at', 'updated_at'
    ];

    /**
     * Display a listing of the members - OPTIMIZED FOR PERFORMANCE
     */
    public function index(Request $request)
    {
        try {
            // Simple pagination with basic search only
            $query = Member::query();
            
            // Basic search only (remove complex CONCAT operations)
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Simple filters only
            if ($request->filled('membership_status')) {
                $query->where('membership_status', $request->get('membership_status'));
            }
            
            if ($request->filled('local_church')) {
                $query->where('local_church', $request->get('local_church'));
            }

            // Simple sorting
            $sortField = in_array($request->get('sort'), ['first_name', 'last_name', 'created_at']) 
                ? $request->get('sort') 
                : 'last_name';
            $sortDirection = $request->get('direction') === 'desc' ? 'desc' : 'asc';
            
            $query->orderBy($sortField, $sortDirection);

            // Fast pagination with smaller page size
            $members = $query->paginate(10)->withQueryString();

            // Get comprehensive stats using the dedicated method
            $stats = $this->getStats();

            return Inertia::render('Members/Index', [
                'members' => $members,
                'filters' => $request->only(['search', 'membership_status', 'local_church']),
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Member index error: ' . $e->getMessage());
            return Inertia::render('Members/Index', [
                'members' => ['data' => [], 'total' => 0],
                'filters' => [],
                'stats' => ['total' => 0, 'active' => 0],
            ]);
        }
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
     * Store a newly created member in storage - ENHANCED VERSION WITH BETTER UX
     */
    public function store(Request $request)
    {
        Log::info('Member creation attempt', [
            'user_id' => Auth::id(),
            'data_keys' => array_keys($request->all()),
            'ip' => $request->ip(),
            'timestamp' => now()
        ]);

        try {
            // Enhanced validation with custom error messages
            $validated = $request->validate([
                // Essential fields with custom messages
                'first_name' => 'required|string|max:255|min:2',
                'last_name' => 'required|string|max:255|min:2',
                'gender' => 'required|in:Male,Female',
                
                // Core optional fields with enhanced validation
                'middle_name' => 'nullable|string|max:255',
                'date_of_birth' => 'nullable|date|before:today',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email:rfc,dns|max:255|unique:members,email',
                'id_number' => 'nullable|string|max:20|unique:members,id_number',
                'residence' => 'nullable|string|max:500',
                
                // Church fields with specific validation
                'local_church' => 'required|string|in:St James Kangemi,St Veronica Pembe Tatu,Our Lady of Consolata Cathedral,St Peter Kiawara,Sacred Heart Kandara',
                'small_christian_community' => 'nullable|string|max:255',
                'church_group' => 'required|string|in:PMC,Youth,Young Parents,C.W.A,CMA,Choir,Catholic Action,Pioneer',
                'additional_church_groups' => 'nullable|array',
                
                // Membership fields
                'membership_status' => 'nullable|string|in:active,inactive,transferred,deceased',
                'membership_date' => 'nullable|date|before_or_equal:today',
                'matrimony_status' => 'nullable|string|in:single,married,widowed,separated,divorced',
                'marriage_type' => 'nullable|string|in:customary,church,civil',
                'occupation' => 'nullable|string|in:employed,self_employed,not_employed,student,retired',
                'education_level' => 'nullable|string|in:none,primary,kcpe,secondary,kcse,certificate,diploma,degree,masters,phd',
                
                // Family and relationships
                'family_id' => 'nullable|string',
                'parent' => 'nullable|string|max:255',
                'mother_name' => 'nullable|string|max:255',
                'godparent' => 'nullable|string|max:255',
                'minister' => 'nullable|string|max:255',
                'tribe' => 'nullable|string|max:255',
                'clan' => 'nullable|string|max:255',
                
                // Disability
                'is_differently_abled' => 'nullable|string|in:yes,no',
                'disability_description' => 'nullable|string|max:1000',
                
                // Sacraments with date validation
                'baptism_date' => 'nullable|date|before_or_equal:today',
                'baptism_location' => 'nullable|string|max:255',
                'baptized_by' => 'nullable|string|max:255',
                'confirmation_date' => 'nullable|date|before_or_equal:today|after:baptism_date',
                'confirmation_location' => 'nullable|string|max:255',
                'confirmation_register_number' => 'nullable|string|max:50',
                'confirmation_number' => 'nullable|string|max:50',
                'eucharist_date' => 'nullable|date|before_or_equal:today',
                'eucharist_location' => 'nullable|string|max:255',
                
                // Marriage Certificate Fields with conditional validation
                'marriage_date' => 'nullable|date|before_or_equal:today',
                'marriage_location' => 'nullable|string|max:255',
                'marriage_sub_county' => 'nullable|string|max:255',
                'marriage_county' => 'nullable|string|max:255',
                'marriage_entry_number' => 'nullable|string|max:50',
                'marriage_certificate_number' => 'nullable|string|max:50',
                'marriage_religion' => 'nullable|string|max:255',
                'marriage_license_number' => 'nullable|string|max:255',
                'marriage_officiant_name' => 'nullable|string|max:255',
                'marriage_witness1_name' => 'nullable|string|max:255',
                'marriage_witness2_name' => 'nullable|string|max:255',
                
                // Spouse Information with conditional validation
                'spouse_name' => 'nullable|string|max:255',
                'spouse_age' => 'nullable|integer|min:1|max:120',
                'spouse_residence' => 'nullable|string|max:255',
                'spouse_county' => 'nullable|string|max:255',
                'spouse_marital_status' => 'nullable|string|max:255',
                'spouse_occupation' => 'nullable|string|max:255',
                'spouse_father_name' => 'nullable|string|max:255',
                'spouse_father_occupation' => 'nullable|string|max:255',
                'spouse_father_residence' => 'nullable|string|max:255',
                'spouse_mother_name' => 'nullable|string|max:255',
                'spouse_mother_occupation' => 'nullable|string|max:255',
                'spouse_mother_residence' => 'nullable|string|max:255',
                
                // Contact
                'emergency_contact' => 'nullable|string|max:255',
                'emergency_phone' => 'nullable|string|max:20',
                
                // Notes
                'notes' => 'nullable|string|max:2000',
                
                // Accept all other fields as nullable
                '*' => 'nullable'
            ], [
                // Custom error messages for better UX
                'first_name.required' => 'First name is required',
                'first_name.min' => 'First name must be at least 2 characters',
                'last_name.required' => 'Last name is required',
                'last_name.min' => 'Last name must be at least 2 characters',
                'gender.required' => 'Please select a gender',
                'gender.in' => 'Gender must be either Male or Female',
                'local_church.required' => 'Please select a local church',
                'local_church.in' => 'Please select a valid local church from the list',
                'church_group.required' => 'Please select a church group',
                'church_group.in' => 'Please select a valid church group from the list',
                'email.email' => 'Please enter a valid email address',
                'email.unique' => 'This email address is already registered',
                'id_number.unique' => 'This ID number is already registered',
                'date_of_birth.before' => 'Date of birth must be in the past',
                'confirmation_date.after' => 'Confirmation date must be after baptism date',
                'marriage_date.before_or_equal' => 'Marriage date cannot be in the future',
                'membership_date.before_or_equal' => 'Membership date cannot be in the future',
                'spouse_age.min' => 'Spouse age must be at least 1',
                'spouse_age.max' => 'Spouse age cannot exceed 120',
                'notes.max' => 'Notes cannot exceed 2000 characters',
            ]);

            // Conditional validation for married members
            if ($validated['matrimony_status'] === 'married') {
                $conditionalRules = [
                    'spouse_name' => 'required|string|max:255',
                    'marriage_date' => 'required|date|before_or_equal:today',
                ];
                
                $request->validate($conditionalRules, [
                    'spouse_name.required' => 'Spouse name is required for married members',
                    'marriage_date.required' => 'Marriage date is required for married members',
                ]);
            }

            // Check for potential duplicate member
            $duplicateCheck = Member::where(function($query) use ($validated) {
                $query->where('first_name', $validated['first_name'])
                      ->where('last_name', $validated['last_name']);
                
                if (!empty($validated['date_of_birth'])) {
                    $query->where('date_of_birth', $validated['date_of_birth']);
                }
            })->first();

            if ($duplicateCheck) {
                return back()->withErrors([
                    'first_name' => 'A member with similar details already exists. Please check the member list.',
                ])->withInput();
            }

            // Process and clean the data
            $memberData = $this->processValidatedMemberData($validated);

            // Create the member in a transaction
            DB::beginTransaction();
            
            $member = Member::create($memberData);

            // Create related records if needed
            $this->createRelatedRecords($member, $validated);

            DB::commit();

            // Clear relevant cache
            $this->clearMemberCache();

            Log::info('Member created successfully', [
                'member_id' => $member->id,
                'name' => $member->first_name . ' ' . $member->last_name,
                'church' => $member->local_church,
                'group' => $member->church_group,
                'created_by' => Auth::id()
            ]);

            // Return appropriate response based on request type
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Member successfully added to the parish database!',
                    'member' => [
                        'id' => $member->id,
                        'name' => $member->first_name . ' ' . $member->last_name,
                        'church' => $member->local_church,
                        'group' => $member->church_group,
                    ],
                    'redirect' => route('members.show', $member),
                    'stats' => [
                        'total_members' => Member::count(),
                        'active_members' => Member::where('membership_status', 'active')->count(),
                    ]
                ]);
            }

            return redirect()->route('members.show', $member)
                ->with('success', 'Member ' . $member->first_name . ' ' . $member->last_name . ' successfully added! (ID: ' . $member->id . ')');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Member creation validation failed', [
                'user_id' => Auth::id(),
                'errors' => $e->errors(),
                'input' => $request->except(['password', 'password_confirmation'])
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please correct the errors below',
                    'errors' => $e->errors()
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Member creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'input' => $request->except(['password', 'password_confirmation']),
                'validated_data' => isset($memberData) ? $memberData : null,
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            $message = 'Failed to create member. Please try again. If the problem persists, contact support.';
            
            // Add specific error details for debugging
            if (app()->environment('local')) {
                $message .= ' Error: ' . $e->getMessage();
            }
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error_code' => 'MEMBER_CREATE_FAILED',
                    'debug_info' => app()->environment('local') ? [
                        'error' => $e->getMessage(),
                        'line' => $e->getLine(),
                        'file' => basename($e->getFile())
                    ] : null
                ], 500);
            }

            return back()->withErrors(['error' => $message])->withInput();
        }
    }

    /**
     * Process validated member data for storage
     */
    private function processValidatedMemberData(array $validated): array
    {
        $memberData = [];
        
        // Essential fields
        $memberData['first_name'] = $validated['first_name'];
        $memberData['last_name'] = $validated['last_name'];
        $memberData['gender'] = $validated['gender'];
        
        // Optional fields with defaults and type conversion
        $memberData['middle_name'] = $validated['middle_name'] ?? null;
        $memberData['date_of_birth'] = !empty($validated['date_of_birth']) ? $validated['date_of_birth'] : null;
        $memberData['phone'] = $validated['phone'] ?? null;
        $memberData['email'] = $validated['email'] ?? null;
        $memberData['id_number'] = $validated['id_number'] ?? null;
        $memberData['residence'] = $validated['residence'] ?? null;
        
        // Church information
        $memberData['local_church'] = $validated['local_church'];
        $memberData['small_christian_community'] = $validated['small_christian_community'] ?? null;
        $memberData['church_group'] = $validated['church_group'];
        $memberData['additional_church_groups'] = $validated['additional_church_groups'] ?? null;
        
        // Membership
        $memberData['membership_status'] = $validated['membership_status'] ?? 'active';
        $memberData['membership_date'] = !empty($validated['membership_date']) ? $validated['membership_date'] : now()->format('Y-m-d');
        $memberData['matrimony_status'] = $validated['matrimony_status'] ?? 'single';
        $memberData['marriage_type'] = $validated['marriage_type'] ?? null;
        $memberData['occupation'] = $validated['occupation'] ?? 'not_employed';
        $memberData['education_level'] = $validated['education_level'] ?? 'none';
        
        // Family relationships
        $memberData['family_id'] = (!empty($validated['family_id']) && $validated['family_id'] !== '' && is_numeric($validated['family_id'])) ? (int)$validated['family_id'] : null;
        $memberData['parent'] = $validated['parent'] ?? null;
        $memberData['mother_name'] = $validated['mother_name'] ?? null;
        $memberData['godparent'] = $validated['godparent'] ?? null;
        $memberData['minister'] = $validated['minister'] ?? null;
        
        // Auto-sync fields for sacramental records
        $memberData['father_name'] = $memberData['parent'];
        $memberData['baptized_by'] = $memberData['minister'];
        $memberData['sponsor'] = $memberData['godparent'];
        
        $memberData['tribe'] = $validated['tribe'] ?? null;
        $memberData['clan'] = $validated['clan'] ?? null;
        
        // Disability
        $memberData['is_differently_abled'] = ($validated['is_differently_abled'] ?? 'no') === 'yes';
        $memberData['disability_description'] = $memberData['is_differently_abled'] ? ($validated['disability_description'] ?? null) : null;
        
        // Sacraments
        $memberData['baptism_date'] = !empty($validated['baptism_date']) ? $validated['baptism_date'] : null;
        $memberData['baptism_location'] = $validated['baptism_location'] ?? null;
        $memberData['baptized_by'] = $validated['baptized_by'] ?? $memberData['minister'];
        $memberData['confirmation_date'] = !empty($validated['confirmation_date']) ? $validated['confirmation_date'] : null;
        $memberData['confirmation_location'] = $validated['confirmation_location'] ?? null;
        $memberData['confirmation_register_number'] = $validated['confirmation_register_number'] ?? null;
        $memberData['confirmation_number'] = $validated['confirmation_number'] ?? null;
        $memberData['eucharist_date'] = !empty($validated['eucharist_date']) ? $validated['eucharist_date'] : null;
        $memberData['eucharist_location'] = $validated['eucharist_location'] ?? null;
        
        // Marriage information
        $memberData['marriage_date'] = !empty($validated['marriage_date']) ? $validated['marriage_date'] : null;
        $memberData['marriage_location'] = $validated['marriage_location'] ?? null;
        $memberData['marriage_county'] = $validated['marriage_county'] ?? null;
        $memberData['marriage_sub_county'] = $validated['marriage_sub_county'] ?? null;
        $memberData['marriage_entry_number'] = $validated['marriage_entry_number'] ?? null;
        $memberData['marriage_certificate_number'] = $validated['marriage_certificate_number'] ?? null;
        $memberData['marriage_religion'] = $validated['marriage_religion'] ?? null;
        $memberData['marriage_license_number'] = $validated['marriage_license_number'] ?? null;
        $memberData['marriage_officiant_name'] = $validated['marriage_officiant_name'] ?? null;
        $memberData['marriage_witness1_name'] = $validated['marriage_witness1_name'] ?? null;
        $memberData['marriage_witness2_name'] = $validated['marriage_witness2_name'] ?? null;
        
        // Spouse information
        $memberData['spouse_name'] = $validated['spouse_name'] ?? null;
        $memberData['spouse_age'] = !empty($validated['spouse_age']) && is_numeric($validated['spouse_age']) ? (int)$validated['spouse_age'] : null;
        $memberData['spouse_residence'] = $validated['spouse_residence'] ?? null;
        $memberData['spouse_county'] = $validated['spouse_county'] ?? null;
        $memberData['spouse_marital_status'] = $validated['spouse_marital_status'] ?? null;
        $memberData['spouse_occupation'] = $validated['spouse_occupation'] ?? null;
        $memberData['spouse_father_name'] = $validated['spouse_father_name'] ?? null;
        $memberData['spouse_father_occupation'] = $validated['spouse_father_occupation'] ?? null;
        $memberData['spouse_father_residence'] = $validated['spouse_father_residence'] ?? null;
        $memberData['spouse_mother_name'] = $validated['spouse_mother_name'] ?? null;
        $memberData['spouse_mother_occupation'] = $validated['spouse_mother_occupation'] ?? null;
        $memberData['spouse_mother_residence'] = $validated['spouse_mother_residence'] ?? null;
        
        // Contact
        $memberData['emergency_contact'] = $validated['emergency_contact'] ?? null;
        $memberData['emergency_phone'] = $validated['emergency_phone'] ?? null;
        
        // Notes
        $memberData['notes'] = $validated['notes'] ?? null;
        
        // Filter out empty strings and replace with null
        return array_map(function($value) {
            return ($value === '' || $value === []) ? null : $value;
        }, $memberData);
    }

    /**
     * Create related records (baptism, marriage, etc.)
     */
    private function createRelatedRecords(Member $member, array $validated): void
    {
        try {
            // Create baptism record if baptism date is provided
            if (!empty($validated['baptism_date'])) {
                $this->createComprehensiveBaptismRecord($member, $validated);
            }

            // Create marriage record if married and marriage details provided
            if ($member->matrimony_status === 'married' && !empty($validated['marriage_date'])) {
                $this->createComprehensiveMarriageRecord($member, $validated);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to create related records', [
                'member_id' => $member->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clear member-related cache
     */
    private function clearMemberCache(): void
    {
        try {
            Cache::forget('optimized_stats');
            Cache::forget('dashboard_core_' . Auth::id());
            Cache::forget('parish_overview');
            Cache::forget('recent_activities_' . Auth::id());
            
            // Clear user-specific cache patterns
            $userId = Auth::id();
            $cacheKeys = [
                "user_permissions_{$userId}",
                "dashboard_alerts_{$userId}",
                "quick_actions_{$userId}",
                "api_stats"
            ];
            
            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clear cache', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Create comprehensive baptism record for a member
     */
    private function createComprehensiveBaptismRecord(Member $member, array $validated)
    {
        try {
            // Check if baptism record already exists for this member
            $existingRecord = BaptismRecord::where('member_id', $member->id)->first();
            if ($existingRecord) {
                return [
                    'success' => false,
                    'message' => 'A baptism record already exists for this member.',
                ];
            }

            // Create baptism sacrament record
            $baptismSacrament = new Sacrament([
                'member_id' => $member->id,
                'sacrament_type' => 'baptism',
                'sacrament_date' => $validated['baptism_date'] ?? $member->date_of_birth,
                'location' => $validated['baptism_location'] ?? '',
                'celebrant' => $validated['baptized_by'] ?? '',
                'godparent_1' => $validated['sponsor'] ?? '',
                'recorded_by' => Auth::id(),
            ]);
            $baptismSacrament->save();

            // Create eucharist sacrament record if date provided
            $eucharistSacrament = null;
            if (!empty($validated['eucharist_date']) && !empty($validated['eucharist_location'])) {
                $eucharistSacrament = new Sacrament([
                    'member_id' => $member->id,
                    'sacrament_type' => 'eucharist',
                    'sacrament_date' => $validated['eucharist_date'],
                    'location' => $validated['eucharist_location'],
                    'recorded_by' => Auth::id(),
                ]);
                $eucharistSacrament->save();
            }

            // Create confirmation sacrament record if date provided
            $confirmationSacrament = null;
            if (!empty($validated['confirmation_date']) && !empty($validated['confirmation_location'])) {
                $confirmationSacrament = new Sacrament([
                    'member_id' => $member->id,
                    'sacrament_type' => 'confirmation',
                    'sacrament_date' => $validated['confirmation_date'],
                    'location' => $validated['confirmation_location'],
                    'certificate_number' => $validated['confirmation_number'] ?? null,
                    'book_number' => $validated['confirmation_register_number'] ?? null,
                    'recorded_by' => Auth::id(),
                ]);
                $confirmationSacrament->save();
            }

            // Create comprehensive baptism record
            $baptismRecord = new BaptismRecord([
                'record_number' => BaptismRecord::generateRecordNumber(),
                'member_id' => $member->id,
                
                // Personal information
                'father_name' => $validated['father_name'] ?? '',
                'mother_name' => $validated['mother_name'] ?? '',
                'tribe' => $validated['tribe'] ?? $member->tribe ?? '',
                'birth_village' => $validated['birth_village'] ?? '',
                'county' => $validated['county'] ?? '',
                'birth_date' => $member->date_of_birth,
                'residence' => $validated['residence'] ?? $member->address ?? '',
                
                // Baptism information
                'baptism_location' => $validated['baptism_location'] ?? '',
                'baptism_date' => $validated['baptism_date'] ?? $member->date_of_birth,
                'baptized_by' => $validated['baptized_by'] ?? '',
                'sponsor' => $validated['sponsor'] ?? '',
                
                // Eucharist information
                'eucharist_location' => $validated['eucharist_location'] ?? null,
                'eucharist_date' => $validated['eucharist_date'] ?? null,
                
                // Confirmation information
                'confirmation_location' => $validated['confirmation_location'] ?? null,
                'confirmation_date' => $validated['confirmation_date'] ?? null,
                'confirmation_register_number' => $validated['confirmation_register_number'] ?? null,
                'confirmation_number' => $validated['confirmation_number'] ?? null,
                
                // Marriage information (if member is married)
                'marriage_spouse' => null, // Will be filled when marriage record is created
                'marriage_location' => null,
                'marriage_date' => null,
                'marriage_register_number' => null,
                'marriage_number' => null,
                
                // Link sacrament records
                'baptism_sacrament_id' => $baptismSacrament->id,
                'eucharist_sacrament_id' => $eucharistSacrament ? $eucharistSacrament->id : null,
                'confirmation_sacrament_id' => $confirmationSacrament ? $confirmationSacrament->id : null,
                'marriage_sacrament_id' => null,
            ]);
            
            $baptismRecord->save();
            
            // Link baptism record to sacrament records (polymorphic relationship)
            $baptismSacrament->detailed_record_type = BaptismRecord::class;
            $baptismSacrament->detailed_record_id = $baptismRecord->id;
            $baptismSacrament->save();
            
            return [
                'success' => true,
                'record' => $baptismRecord,
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create baptism record: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create comprehensive marriage record for a member
     */
    private function createComprehensiveMarriageRecord(Member $member, array $validated)
    {
        try {
            // Only create if matrimony status is married and marriage details are provided
            if ($member->matrimony_status !== 'married' || empty($validated['marriage_date'])) {
                return [
                    'success' => false,
                    'message' => 'Marriage record only created for married members with marriage details.',
                ];
            }

            // Generate record number
            $recordNumber = MarriageRecord::generateRecordNumber();
            
            // Create the marriage sacrament record
            $marriageSacrament = new Sacrament([
                'member_id' => $member->id,
                'sacrament_type' => 'marriage',
                'sacrament_date' => $validated['marriage_date'],
                'location' => $validated['marriage_church'] ?? $member->local_church,
                'celebrant' => $validated['presence_of'] ?? '',
                'witness_1' => $validated['male_witness_full_name'] ?? '',
                'witness_2' => $validated['female_witness_full_name'] ?? '',
                'certificate_number' => $recordNumber,
                'notes' => 'District: ' . ($validated['district'] ?? '') . ', Province: ' . ($validated['province'] ?? ''),
                'recorded_by' => Auth::id(),
            ]);
            
            $marriageSacrament->save();
            
            // Create comprehensive marriage record
            $marriageRecord = new MarriageRecord([
                'record_number' => $recordNumber,
                
                // Determine if member is husband or wife
                'husband_id' => $member->gender === 'Male' ? $member->id : null,
                'wife_id' => $member->gender === 'Female' ? $member->id : null,
                
                // Member (husband/wife) information
                ($member->gender === 'Male' ? 'husband' : 'wife') . '_name' => $member->full_name,
                ($member->gender === 'Male' ? 'husband' : 'wife') . '_father_name' => $validated['father_name'] ?? '',
                ($member->gender === 'Male' ? 'husband' : 'wife') . '_mother_name' => $validated['mother_name'] ?? '',
                ($member->gender === 'Male' ? 'husband' : 'wife') . '_tribe' => $member->tribe ?? '',
                ($member->gender === 'Male' ? 'husband' : 'wife') . '_clan' => $member->clan ?? '',
                ($member->gender === 'Male' ? 'husband' : 'wife') . '_birth_place' => $validated['birth_village'] ?? '',
                ($member->gender === 'Male' ? 'husband' : 'wife') . '_domicile' => $member->residence ?? '',
                ($member->gender === 'Male' ? 'husband' : 'wife') . '_baptized_at' => $validated['baptism_location'] ?? '',
                ($member->gender === 'Male' ? 'husband' : 'wife') . '_baptism_date' => $member->baptism_date,
                ($member->gender === 'Male' ? 'husband' : 'wife') . '_parent_consent' => 'Yes',
                
                // Spouse information
                ($member->gender === 'Male' ? 'wife' : 'husband') . '_name' => $validated['spouse_name'] ?? '',
                ($member->gender === 'Male' ? 'wife' : 'husband') . '_father_name' => $validated['spouse_father_name'] ?? '',
                ($member->gender === 'Male' ? 'wife' : 'husband') . '_mother_name' => $validated['spouse_mother_name'] ?? '',
                ($member->gender === 'Male' ? 'wife' : 'husband') . '_tribe' => $validated['spouse_tribe'] ?? '',
                ($member->gender === 'Male' ? 'wife' : 'husband') . '_clan' => $validated['spouse_clan'] ?? '',
                ($member->gender === 'Male' ? 'wife' : 'husband') . '_birth_place' => $validated['spouse_birth_place'] ?? '',
                ($member->gender === 'Male' ? 'wife' : 'husband') . '_domicile' => $validated['spouse_domicile'] ?? '',
                ($member->gender === 'Male' ? 'wife' : 'husband') . '_baptized_at' => $validated['spouse_baptized_at'] ?? '',
                ($member->gender === 'Male' ? 'wife' : 'husband') . '_baptism_date' => $validated['spouse_baptism_date'],
                ($member->gender === 'Male' ? 'wife' : 'husband') . '_parent_consent' => $validated['spouse_parent_consent'] ?? 'Yes',
                
                // Banas information
                'banas_number' => $validated['banas_number'] ?? '',
                'banas_church_1' => $validated['banas_church_1'] ?? '',
                'banas_date_1' => $validated['banas_date_1'],
                'banas_church_2' => $validated['banas_church_2'],
                'banas_date_2' => $validated['banas_date_2'],
                'dispensation_from' => $validated['dispensation_from'],
                'dispensation_given_by' => $validated['dispensation_given_by'],
                
                // Dispensation information
                'dispensation_impediment' => $validated['dispensation_impediment'],
                'dispensation_authority' => $validated['dispensation_authority'],
                'dispensation_date' => $validated['dispensation_date'],
                
                // Marriage contract information
                'marriage_date' => $validated['marriage_date'],
                'marriage_month' => date('F', strtotime($validated['marriage_date'])),
                'marriage_year' => date('Y', strtotime($validated['marriage_date'])),
                'marriage_church' => $validated['marriage_church'] ?? $member->local_church,
                'district' => $validated['district'] ?? '',
                'province' => $validated['province'] ?? '',
                'presence_of' => $validated['presence_of'] ?? '',
                'delegated_by' => $validated['delegated_by'],
                'delegation_date' => $validated['delegation_date'],
                
                // Witness information
                'male_witness_full_name' => $validated['male_witness_full_name'] ?? '',
                'male_witness_father' => $validated['male_witness_father'] ?? '',
                'male_witness_clan' => $validated['male_witness_clan'] ?? '',
                'female_witness_full_name' => $validated['female_witness_full_name'] ?? '',
                'female_witness_father' => $validated['female_witness_father'] ?? '',
                'female_witness_clan' => $validated['female_witness_clan'] ?? '',
                
                // Additional documents
                'other_documents' => $validated['other_documents'],
                'civil_marriage_certificate_number' => $validated['civil_marriage_certificate_number'],
                
                // System relationships
                'parish_priest_id' => Auth::id(),
                'sacrament_id' => $marriageSacrament->id,
            ]);
            
            $marriageRecord->save();
            
            // Link marriage record to sacrament record (polymorphic relationship)
            $marriageSacrament->detailed_record_type = MarriageRecord::class;
            $marriageSacrament->detailed_record_id = $marriageRecord->id;
            $marriageSacrament->save();
            
            return [
                'success' => true,
                'record' => $marriageRecord,
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create marriage record: ' . $e->getMessage(),
            ];
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
            'gender' => 'required|in:Male,Female',
            'id_number' => 'nullable|string|max:20|unique:members,id_number,' . $member->id,
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:members,email,' . $member->id,
            'residence' => 'nullable|string|max:255',
            'local_church' => 'required|in:St James Kangemi,St Veronica Pembe Tatu,Our Lady of Consolata Cathedral,St Peter Kiawara,Sacred Heart Kandara',
            'church_group' => 'required|in:PMC,Youth,C.W.A,CMA,Choir,Catholic Action,Pioneer',
            'membership_status' => 'nullable|in:active,inactive,transferred,deceased',
            'membership_date' => 'nullable|date',
            'baptism_date' => 'nullable|date',
            'confirmation_date' => 'nullable|date',
            'matrimony_status' => 'required|in:single,married,widowed,separated',
            'marriage_type' => 'nullable|in:customary,church,civil',
            'is_differently_abled' => 'nullable|boolean',
            'disability_description' => 'nullable|string|max:1000',
            'occupation' => 'required|in:employed,self_employed,not_employed',
            'education_level' => 'required|in:none,primary,kcpe,secondary,kcse,certificate,diploma,degree,masters,phd',
            'family_id' => 'nullable|exists:families,id',
            'parent' => 'nullable|string|max:255',
            'sponsor' => 'nullable|string|max:255',
            'minister' => 'nullable|string|max:255',
            'tribe' => 'nullable|string|max:255',
            'clan' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            
            // Comprehensive Baptism Record Fields
            'birth_village' => 'nullable|string|max:255',
            'county' => 'nullable|string|max:255',
            'baptism_location' => 'nullable|string|max:255',
            'baptized_by' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'small_christian_community' => 'nullable|string|max:255',
            
            // Optional Sacrament Fields
            'eucharist_location' => 'nullable|string|max:255',
            'eucharist_date' => 'nullable|date',
            'confirmation_location' => 'nullable|string|max:255',
            'confirmation_register_number' => 'nullable|string|max:50',
            'confirmation_number' => 'nullable|string|max:50',
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
              ->orWhere('email', 'like', "%{$query}%")
              ->orWhere('id', 'like', "%{$query}%");
        })
        ->select('id', 'first_name', 'middle_name', 'last_name', 'phone', 'email', 'church_group', 'local_church', 'date_of_birth', 'membership_status')
        ->limit(20)
        ->get();

        return response()->json(['members' => $members]);
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
            'local_churches' => ['St James Kangemi', 'St Veronica Pembe Tatu', 'Our Lady of Consolata Cathedral', 'St Peter Kiawara', 'Sacred Heart Kandara'],
            'church_groups' => [
                ['value' => 'PMC', 'label' => 'PMC (Pontifical Missionary Childhood)'],
                ['value' => 'Youth', 'label' => 'Youth'],
                ['value' => 'C.W.A', 'label' => 'C.W.A (Catholic Women Association)'],
                ['value' => 'CMA', 'label' => 'CMA (Catholic Men Association)'],
                ['value' => 'Choir', 'label' => 'Choir'],
                ['value' => 'Catholic Action', 'label' => 'Catholic Action'],
                ['value' => 'Pioneer', 'label' => 'Pioneer']
            ],
            'education_levels' => [
                ['value' => 'none', 'label' => 'No Formal Education'],
                ['value' => 'primary', 'label' => 'Primary Education'],
                ['value' => 'kcpe', 'label' => 'KCPE'],
                ['value' => 'secondary', 'label' => 'Secondary Education'],
                ['value' => 'kcse', 'label' => 'KCSE'],
                ['value' => 'certificate', 'label' => 'Certificate'],
                ['value' => 'diploma', 'label' => 'Diploma'],
                ['value' => 'degree', 'label' => 'Degree'],
                ['value' => 'masters', 'label' => 'Masters'],
                ['value' => 'phd', 'label' => 'PhD']
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
            'church_group' => 'required|in:PMC,Youth,C.W.A,CMA,Choir,Catholic Action,Pioneer',
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
            'occupation' => !empty($row['occupation']) ? trim($row['occupation']) : 'not_employed',
            'residence' => !empty($row['residence']) ? trim($row['residence']) : null,
            'sponsor' => !empty($row['sponsor']) ? trim($row['sponsor']) : null,
            'parent' => !empty($row['parent']) ? trim($row['parent']) : null,
            'minister' => !empty($row['minister']) ? trim($row['minister']) : null,
            'tribe' => !empty($row['tribe']) ? trim($row['tribe']) : null,
            'clan' => !empty($row['clan']) ? trim($row['clan']) : null,
            'education_level' => !empty($row['education_level']) ? trim($row['education_level']) : 'none',
            'matrimony_status' => !empty($row['matrimony_status']) ? trim($row['matrimony_status']) : 'single',
            'baptism_date' => !empty($row['baptism_date']) ? 
                \Carbon\Carbon::parse($row['baptism_date'])->format('Y-m-d') : null,
            'confirmation_date' => !empty($row['confirmation_date']) ? 
                \Carbon\Carbon::parse($row['confirmation_date'])->format('Y-m-d') : null,
            'is_differently_abled' => !empty($row['is_differently_abled']) ? 
                filter_var($row['is_differently_abled'], FILTER_VALIDATE_BOOLEAN) : false,
            'disability_description' => !empty($row['disability_description']) ? 
                trim($row['disability_description']) : null,
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
            $totalMembers = Member::count();
            $currentMonth = now();
            
            // Get detailed status breakdown with proper null handling
            $statusStats = Member::selectRaw('COALESCE(membership_status, "unknown") as membership_status, COUNT(*) as count')
                ->groupBy('membership_status')
                ->pluck('count', 'membership_status')
                ->toArray();

            // Calculate active members percentage with fallbacks
            $activeMembers = $statusStats['active'] ?? 0;
            $inactiveMembers = $statusStats['inactive'] ?? 0;
            $transferredMembers = $statusStats['transferred'] ?? 0;
            $deceasedMembers = $statusStats['deceased'] ?? 0;
            
            // Get new members this month
            $newThisMonth = Member::whereMonth('created_at', $currentMonth->month)
                ->whereYear('created_at', $currentMonth->year)
                ->count();

            // Get gender breakdown with case-insensitive handling
            $genderStats = Member::selectRaw('UPPER(COALESCE(gender, "Unknown")) as gender, COUNT(*) as count')
                ->groupBy('gender')
                ->pluck('count', 'gender')
                ->toArray();

            // Get church breakdown with null handling
            $churchStats = Member::selectRaw('COALESCE(local_church, "Unknown") as local_church, COUNT(*) as count')
                ->whereNotNull('local_church')
                ->where('local_church', '!=', '')
                ->groupBy('local_church')
                ->pluck('count', 'local_church')
                ->toArray();

            // Get group breakdown with null handling
            $groupStats = Member::selectRaw('COALESCE(church_group, "Unknown") as church_group, COUNT(*) as count')
                ->whereNotNull('church_group')
                ->where('church_group', '!=', '')
                ->groupBy('church_group')
                ->pluck('count', 'church_group')
                ->toArray();

            // Debug logging
            Log::info('Stats calculation successful', [
                'total_members' => $totalMembers,
                'active_members' => $activeMembers,
                'church_count' => count($churchStats),
                'group_count' => count($groupStats),
            ]);

            return [
                'total_members' => $totalMembers,
                'active_members' => $activeMembers,
                'new_this_month' => $newThisMonth,
                'by_church' => $churchStats,
                'by_group' => $groupStats,
                'by_status' => [
                    'active' => $activeMembers,
                    'inactive' => $inactiveMembers,
                    'transferred' => $transferredMembers,
                    'deceased' => $deceasedMembers,
                ],
                'by_gender' => $genderStats,
                'statistics' => [
                    'total_members' => $totalMembers,
                    'active_members' => $activeMembers,
                    'inactive_members' => $inactiveMembers,
                    'transferred_members' => $transferredMembers,
                    'deceased_members' => $deceasedMembers,
                    'active_percentage' => $totalMembers > 0 ? round(($activeMembers / $totalMembers) * 100, 1) : 0,
                    'new_this_month' => $newThisMonth,
                    'male_members' => $genderStats['MALE'] ?? 0,
                    'female_members' => $genderStats['FEMALE'] ?? 0,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get stats: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'total_members' => 0,
                'active_members' => 0,
                'new_this_month' => 0,
                'by_church' => [],
                'by_group' => [],
                'by_status' => [
                    'active' => 0,
                    'inactive' => 0,
                    'transferred' => 0,
                    'deceased' => 0,
                ],
                'by_gender' => [],
                'statistics' => [
                    'total_members' => 0,
                    'active_members' => 0,
                    'inactive_members' => 0,
                    'transferred_members' => 0,
                    'deceased_members' => 0,
                    'active_percentage' => 0,
                    'new_this_month' => 0,
                    'male_members' => 0,
                    'female_members' => 0,
                ],
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
                    ['value' => 'C.W.A', 'label' => 'C.W.A (Catholic Women Association)'],
                    ['value' => 'CMA', 'label' => 'CMA (Catholic Men Association)'],
                    ['value' => 'Choir', 'label' => 'Choir'],
                    ['value' => 'Catholic Action', 'label' => 'Catholic Action'],
                    ['value' => 'Pioneer', 'label' => 'Pioneer'],
                ],
                'education_levels' => [
                    ['value' => 'none', 'label' => 'No Formal Education'],
                    ['value' => 'primary', 'label' => 'Primary Education'],
                    ['value' => 'kcpe', 'label' => 'KCPE'],
                    ['value' => 'secondary', 'label' => 'Secondary Education'],
                    ['value' => 'kcse', 'label' => 'KCSE'],
                    ['value' => 'certificate', 'label' => 'Certificate'],
                    ['value' => 'diploma', 'label' => 'Diploma'],
                    ['value' => 'degree', 'label' => 'Degree'],
                    ['value' => 'masters', 'label' => 'Masters'],
                    ['value' => 'phd', 'label' => 'PhD'],
                ],
                'membership_statuses' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                    ['value' => 'transferred', 'label' => 'Transferred'],
                    ['value' => 'deceased', 'label' => 'Deceased'],
                ],
                'genders' => [
                    ['value' => 'Male', 'label' => 'Male'],
                    ['value' => 'Female', 'label' => 'Female'],
                ],
                'tribes' => Member::select('tribe')
                    ->whereNotNull('tribe')
                    ->where('tribe', '!=', '')
                    ->distinct()
                    ->orderBy('tribe')
                    ->pluck('tribe')
                    ->map(fn($tribe) => ['value' => $tribe, 'label' => $tribe])
                    ->toArray(),
                'small_christian_communities' => Member::select('small_christian_community')
                    ->whereNotNull('small_christian_community')
                    ->where('small_christian_community', '!=', '')
                    ->distinct()
                    ->orderBy('small_christian_community')
                    ->pluck('small_christian_community')
                    ->map(fn($community) => ['value' => $community, 'label' => $community])
                    ->toArray(),
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
                    ['value' => 'Male', 'label' => 'Male'],
                    ['value' => 'Female', 'label' => 'Female'],
                ],
                'families' => [],
            ];
        }
    }

    /**
     * Quick status toggle for members
     */
    public function quickStatusToggle(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'status' => 'required|in:active,inactive,transferred,deceased'
        ]);

        try {
            $member = Member::findOrFail($request->member_id);
            $oldStatus = $member->membership_status;
            
            $member->update([
                'membership_status' => $request->status
            ]);

            // Log the activity
            Log::info('Member status changed', [
                'member_id' => $member->id,
                'member_name' => $member->full_name,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'changed_by' => Auth::id(),
            ]);

            // For AJAX requests, return JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Member status updated to {$request->status} successfully!",
                    'member' => [
                        'id' => $member->id,
                        'full_name' => $member->full_name,
                        'membership_status' => $member->membership_status,
                    ]
                ]);
            }

            // For Inertia requests, redirect back with flash message
            return redirect()->back()->with('success', "Member status updated to {$request->status} successfully!");

        } catch (\Exception $e) {
            Log::error('Failed to update member status', [
                'member_id' => $request->member_id,
                'status' => $request->status,
                'error' => $e->getMessage()
            ]);

            // For AJAX requests, return JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update member status. Please try again.'
                ], 500);
            }

            // For Inertia requests, redirect back with error
            return redirect()->back()->with('error', 'Failed to update member status. Please try again.');
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
                'family_name', 'is_differently_abled', 'disability_description', 'baptism_date',
                'confirmation_date', 'matrimony_status', 'notes'
            ]);

            // Sample data with proper formatting
            fputcsv($file, [
                'John', 'Mwangi', 'Doe', '1990-01-15', 'Male', // Fixed: Use 'Male' instead of 'male'
                '+254712345678', 'john.doe@email.com', '12345678', 'Kangemi', 'CMA',
                'active', '2024-01-01', 'Kangemi Estate House 123', 'employed',
                'Doe Family', 'false', '', '2010-05-20',
                '2015-08-15', 'married', 'Sample member record'
            ]);
            
            fputcsv($file, [
                'Mary', 'Wanjiku', 'Smith', '1985-05-20', 'Female', // Fixed: Use 'Female' instead of 'female'
                '+254798765432', 'mary.smith@email.com', '87654321', 'Cathedral', 'C.W.A',
                'active', '2024-01-01', 'Cathedral Area Apt 45', 'self_employed',
                'Smith Family', 'true', 'Mobility assistance required', '2005-03-10',
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
            // Ensure proper capitalization of gender value
            $gender = ucfirst(strtolower($filters['gender']));
            $query->where('gender', $gender);
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

    /**
     * Download baptism certificate for a member
     */
    public function downloadBaptismCertificate(Member $member)
    {
        if (!$member->baptism_date) {
            return back()->with('error', 'Member has no baptism record to generate certificate.');
        }

        $certificateData = $member->getBaptismCertificateData();
        
        // Here you would generate a PDF certificate
        // For now, return JSON data for testing
        return response()->json([
            'certificate_data' => $certificateData,
            'message' => 'Baptism certificate data ready for download'
        ]);
    }

    public function downloadMarriageCertificate(Member $member)
    {
        // More flexible validation - check if member has any marriage data
        $hasMarriageData = $member->matrimony_status === 'married' || 
                          $member->marriage_date || 
                          $member->spouse_name || 
                          $member->husband_name || 
                          $member->wife_name ||
                          $member->marriage_certificate_number;

        if (!$hasMarriageData) {
            return back()->with('error', 'Member has no marriage record to generate certificate.');
        }

        try {
            // Debug: Log what data we have
            Log::info('Marriage certificate data for member ' . $member->id, [
                'matrimony_status' => $member->matrimony_status,
                'marriage_date' => $member->marriage_date,
                'spouse_name' => $member->spouse_name,
                'husband_name' => $member->husband_name,
                'wife_name' => $member->wife_name,
                'marriage_certificate_number' => $member->marriage_certificate_number,
            ]);

            // Create marriageRecord object from member data
            $marriageRecord = (object) [
                'id' => $member->id,
                'certificate_number' => $member->marriage_certificate_number ?? $member->certificate_number,
                'marriage_location' => $member->marriage_location ?? 'Sacred Heart Kandara Parish',
                'sub_county' => $member->sub_county ?? 'Kandara',
                'county' => $member->county ?? 'Murang\'a',
                'marriage_date' => $member->marriage_date,
                'entry_number' => $member->marriage_entry_number,
                'husband_name' => $member->husband_name ?? $member->full_name,
                'husband_age' => $member->husband_age ?? $member->age,
                'husband_residence' => $member->husband_residence ?? $member->address,
                'husband_marital_status' => 'Single',
                'husband_county' => $member->husband_county ?? $member->county,
                'husband_occupation' => $member->husband_occupation ?? $member->occupation,
                'husband_father_name' => $member->husband_father_name ?? $member->father_name,
                'husband_mother_name' => $member->husband_mother_name ?? $member->mother_name,
                'husband_father_occupation' => $member->husband_father_occupation,
                'husband_mother_occupation' => $member->husband_mother_occupation,
                'husband_father_residence' => $member->husband_father_residence,
                'husband_mother_residence' => $member->husband_mother_residence,
                'wife_name' => $member->wife_name ?? $member->spouse_name,
                'wife_age' => $member->wife_age,
                'wife_residence' => $member->wife_residence ?? $member->address,
                'wife_marital_status' => 'Single',
                'wife_county' => $member->wife_county ?? $member->county,
                'wife_occupation' => $member->wife_occupation ?? $member->spouse_occupation,
                'wife_father_name' => $member->wife_father_name,
                'wife_mother_name' => $member->wife_mother_name,
                'wife_father_occupation' => $member->wife_father_occupation,
                'wife_mother_occupation' => $member->wife_mother_occupation,
                'wife_father_residence' => $member->wife_father_residence,
                'wife_mother_residence' => $member->wife_mother_residence,
            ];

            // Prepare data for certificate
            $data = [
                'marriageRecord' => $marriageRecord,
                'member' => $member,
                'parish_name' => config('app.parish_name', 'Sacred Heart Kandara Parish'),
                'generated_at' => now()
            ];

            // Generate PDF using Dompdf
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('certificates.marriage-certificate', $data);
            $pdf->setPaper('A4', 'portrait');

            $filename = 'marriage-certificate-' . Str::slug($member->full_name) . '-' . now()->format('Y-m-d') . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate marriage certificate: ' . $e->getMessage());
        }
    }

    /**
     * Download baptism card for a member  
     */
    public function downloadBaptismCard(Member $member)
    {
        try {
            // Prepare data for baptism card
            $data = [
                'member' => $member,
                'parish_name' => config('app.parish_name', 'Sacred Heart Kandara Parish'),
                'generated_at' => now()
            ];

            // Generate PDF using Dompdf
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('certificates.baptism-card', $data);
            $pdf->setPaper('A5', 'landscape');

            $filename = 'baptism-card-' . Str::slug($member->full_name) . '-' . now()->format('Y-m-d') . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate baptism card: ' . $e->getMessage());
        }
    }

    /**
     * Update member status with real-time stats refresh
     */
    public function updateStatus(Request $request, Member $member)
    {
        $validated = $request->validate([
            'membership_status' => 'required|string|in:active,inactive,transferred,deceased',
        ]);

        try {
            $oldStatus = $member->membership_status;
            $member->update(['membership_status' => $validated['membership_status']]);

            // Clear relevant cache to ensure fresh stats
            $this->clearMemberCache();

            // Get fresh stats for immediate response
            $freshStats = $this->getStats();

            // Log the status change
            Log::info("Member status updated", [
                'member_id' => $member->id,
                'member_name' => $member->full_name,
                'old_status' => $oldStatus,
                'new_status' => $validated['membership_status'],
                'updated_by' => auth()->user()->name ?? 'System'
            ]);

            return response()->json([
                'success' => true,
                'message' => "Member status updated to {$validated['membership_status']} successfully!",
                'member' => [
                    'id' => $member->id,
                    'membership_status' => $member->membership_status,
                    'updated_at' => $member->updated_at->format('Y-m-d H:i:s')
                ],
                'stats' => $freshStats // Include fresh stats in response
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update member status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update member status. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get updated stats for real-time updates (API endpoint)
     */
    public function getStatsApi()
    {
        try {
            $stats = $this->getStats();
            return response()->json([
                'success' => true, 
                'stats' => $stats,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get member stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update member statuses
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'exists:members,id',
            'membership_status' => 'required|string|in:active,inactive,transferred,deceased',
        ]);

        try {
            $updatedCount = Member::whereIn('id', $validated['member_ids'])
                ->update(['membership_status' => $validated['membership_status']]);

            // Clear cache and get fresh stats
            $this->clearMemberCache();
            $freshStats = $this->getStats();

            Log::info('Bulk status update completed', [
                'updated_count' => $updatedCount,
                'new_status' => $validated['membership_status'],
                'updated_by' => auth()->user()->name ?? 'System'
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updatedCount} members to {$validated['membership_status']} status",
                'updated_count' => $updatedCount,
                'stats' => $freshStats
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk status update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update member statuses. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
