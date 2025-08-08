<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FamilyController extends Controller
{
    /**
     * Display a listing of the families.
     */
    public function index(Request $request): InertiaResponse
    {
        try {
            $query = Family::query();

            // Load relationships with member counts
            $query->withCount([
                'members',
                'members as active_members_count' => function ($q) {
                    $q->where('membership_status', 'active');
                }
            ]);

            // Apply search filter
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('family_name', 'like', "%{$search}%")
                      ->orWhere('family_code', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('parish_section', 'like', "%{$search}%")
                      ->orWhere('deanery', 'like', "%{$search}%")
                      ->orWhere('parish', 'like', "%{$search}%");
                });
            }

            // Apply parish section filter
            if ($request->filled('parish_section')) {
                $query->where('parish_section', $request->get('parish_section'));
            }

            // Apply deanery filter
            if ($request->filled('deanery')) {
                $query->where('deanery', $request->get('deanery'));
            }

            // Apply parish filter
            if ($request->filled('parish')) {
                $query->where('parish', $request->get('parish'));
            }

            // Apply year filter using created_at (since registration_date doesn't exist)
            if ($request->filled('year')) {
                $year = $request->get('year');
                $query->whereYear('created_at', $year);
            }

            // Apply member count filter
            if ($request->filled('min_members')) {
                $minMembers = (int) $request->get('min_members');
                $query->has('members', '>=', $minMembers);
            }

            if ($request->filled('max_members')) {
                $maxMembers = (int) $request->get('max_members');
                $query->has('members', '<=', $maxMembers);
            }

            // Sorting
            $sortField = $request->get('sort', 'family_name');
            $sortDirection = $request->get('direction', 'asc');
            
            // Ensure we're sorting by valid columns that exist in the database
            $allowedSortFields = [
                'family_name', 
                'family_code', 
                'parish_section', 
                'deanery', 
                'parish', 
                'created_at', 
                'updated_at',
                'members_count'
            ];
            
            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'family_name';
            }

            if (!in_array($sortDirection, ['asc', 'desc'])) {
                $sortDirection = 'asc';
            }

            if ($sortField === 'members_count') {
                $query->orderBy('members_count', $sortDirection);
            } else {
                $query->orderBy($sortField, $sortDirection);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            if (!is_numeric($perPage) || $perPage < 1 || $perPage > 100) {
                $perPage = 15;
            }

            $families = $query->paginate($perPage)->withQueryString();

            // Load head of family relationships for the paginated results
            $families->load(['headOfFamily', 'creator']);

            // Transform the data for frontend
            $families->getCollection()->transform(function ($family) {
                return [
                    'id' => $family->id,
                    'family_name' => $family->family_name ?? 'Unknown Family',
                    'family_code' => $family->family_code,
                    'address' => $family->address,
                    'phone' => $family->phone,
                    'email' => $family->email,
                    'deanery' => $family->deanery,
                    'parish' => $family->parish,
                    'parish_section' => $family->parish_section,
                    'head_of_family_id' => $family->head_of_family_id,
                    'created_by' => $family->created_by,
                    'created_at' => $family->created_at ? $family->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $family->updated_at ? $family->updated_at->format('Y-m-d H:i:s') : null,
                    'members_count' => $family->members_count ?? 0,
                    'active_members_count' => $family->active_members_count ?? 0,
                    'head_of_family' => $family->headOfFamily ? [
                        'id' => $family->headOfFamily->id,
                        'first_name' => $family->headOfFamily->first_name,
                        'middle_name' => $family->headOfFamily->middle_name,
                        'last_name' => $family->headOfFamily->last_name,
                        'full_name' => trim(($family->headOfFamily->first_name ?? '') . ' ' . 
                                          ($family->headOfFamily->middle_name ?? '') . ' ' . 
                                          ($family->headOfFamily->last_name ?? '')),
                        'phone' => $family->headOfFamily->phone,
                        'email' => $family->headOfFamily->email,
                    ] : null,
                    'creator' => $family->creator ? [
                        'id' => $family->creator->id,
                        'name' => $family->creator->name,
                        'email' => $family->creator->email,
                    ] : null,
                ];
            });

            // Calculate statistics
            $stats = [
                'total_families' => Family::count(),
                'families_with_members' => Family::has('members')->count(),
                'families_with_active_members' => Family::whereHas('members', function ($q) {
                    $q->where('membership_status', 'active');
                })->count(),
                'new_this_month' => Family::whereMonth('created_at', now()->month)
                                         ->whereYear('created_at', now()->year)
                                         ->count(),
                'new_this_year' => Family::whereYear('created_at', now()->year)->count(),
                'total_members_in_families' => Member::whereNotNull('family_id')->count(),
                'average_family_size' => round(
                    Member::whereNotNull('family_id')->count() / max(Family::count(), 1), 
                    1
                ),
            ];

            // Get filter options from existing data
            $filterOptions = [
                'parish_sections' => Family::select('parish_section')
                                          ->whereNotNull('parish_section')
                                          ->where('parish_section', '!=', '')
                                          ->distinct()
                                          ->orderBy('parish_section')
                                          ->pluck('parish_section')
                                          ->filter()
                                          ->values()
                                          ->toArray(),
                'deaneries' => Family::select('deanery')
                                    ->whereNotNull('deanery')
                                    ->where('deanery', '!=', '')
                                    ->distinct()
                                    ->orderBy('deanery')
                                    ->pluck('deanery')
                                    ->filter()
                                    ->values()
                                    ->toArray(),
                'parishes' => Family::select('parish')
                                   ->whereNotNull('parish')
                                   ->where('parish', '!=', '')
                                   ->distinct()
                                   ->orderBy('parish')
                                   ->pluck('parish')
                                   ->filter()
                                   ->values()
                                   ->toArray(),
                // Use created_at for years since registration_date doesn't exist
                'years' => Family::select(DB::raw('DISTINCT YEAR(created_at) as year'))
                                ->whereNotNull('created_at')
                                ->orderBy('year', 'desc')
                                ->pluck('year')
                                ->filter()
                                ->values()
                                ->toArray(),
            ];

            // Group families by parish section for stats
            $familiesBySection = Family::select('parish_section', DB::raw('count(*) as count'))
                                      ->whereNotNull('parish_section')
                                      ->where('parish_section', '!=', '')
                                      ->groupBy('parish_section')
                                      ->orderBy('count', 'desc')
                                      ->get()
                                      ->pluck('count', 'parish_section')
                                      ->toArray();

            // Group families by deanery for stats
            $familiesByDeanery = Family::select('deanery', DB::raw('count(*) as count'))
                                      ->whereNotNull('deanery')
                                      ->where('deanery', '!=', '')
                                      ->groupBy('deanery')
                                      ->orderBy('count', 'desc')
                                      ->get()
                                      ->pluck('count', 'deanery')
                                      ->toArray();

            $stats['by_parish_section'] = $familiesBySection;
            $stats['by_deanery'] = $familiesByDeanery;

            return Inertia::render('Families/Index', [
                'families' => $families,
                'stats' => $stats,
                'filters' => $request->only([
                    'search', 
                    'parish_section', 
                    'deanery', 
                    'parish', 
                    'year', 
                    'min_members',
                    'max_members',
                    'sort', 
                    'direction', 
                    'per_page'
                ]),
                'filterOptions' => $filterOptions,
                'success' => session('success'),
                'error' => session('error'),
            ]);

        } catch (\Exception $e) {
            Log::error('Error in FamilyController@index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            // Return a safe fallback response
            return Inertia::render('Families/Index', [
                'families' => ['data' => [], 'links' => [], 'meta' => []],
                'stats' => [
                    'total_families' => 0,
                    'families_with_members' => 0,
                    'families_with_active_members' => 0,
                    'new_this_month' => 0,
                    'new_this_year' => 0,
                    'total_members_in_families' => 0,
                    'average_family_size' => 0,
                    'by_parish_section' => [],
                    'by_deanery' => [],
                ],
                'filters' => [],
                'filterOptions' => [
                    'parish_sections' => [],
                    'deaneries' => [],
                    'parishes' => [],
                    'years' => [],
                ],
                'success' => null,
                'error' => 'An error occurred while loading families: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Show the form for creating a new family.
     */
    public function create(): InertiaResponse
    {
        try {
            // Get all members without families for potential head of family selection
            $availableMembers = Member::whereNull('family_id')
                                    ->select('id', 'first_name', 'middle_name', 'last_name', 'phone', 'email')
                                    ->orderBy('first_name')
                                    ->get()
                                    ->map(function ($member) {
                                        return [
                                            'id' => $member->id,
                                            'name' => trim(($member->first_name ?? '') . ' ' . 
                                                          ($member->middle_name ?? '') . ' ' . 
                                                          ($member->last_name ?? '')),
                                            'phone' => $member->phone,
                                            'email' => $member->email,
                                        ];
                                    });

            return Inertia::render('Families/Create', [
                'availableMembers' => $availableMembers,
                'success' => session('success'),
                'error' => session('error'),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in FamilyController@create', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Inertia::render('Families/Create', [
                'availableMembers' => [],
                'success' => null,
                'error' => 'Unable to load members data: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Store a newly created family in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'family_name' => 'required|string|max:255',
            'family_code' => 'nullable|string|max:50|unique:families,family_code',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:families,email',
            'deanery' => 'nullable|string|max:255',
            'parish' => 'nullable|string|max:255',
            'parish_section' => 'nullable|string|max:255',
            'head_of_family_id' => 'nullable|exists:members,id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            DB::beginTransaction();
            $family = Family::create([
                ...$validator->validated(),
                'created_by' => Auth::id(),
            ]);

            // If a head of family is selected, update their family_id
            if ($request->filled('head_of_family_id')) {
                Member::where('id', $request->head_of_family_id)
                      ->update(['family_id' => $family->id]);
            }

            DB::commit();

            return redirect()->route('families.index')
                           ->with('success', 'Family created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating family', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return redirect()->back()
                           ->with('error', 'Error creating family: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Display the specified family.
     */
    public function show(Family $family): InertiaResponse
    {
        try {
            $family->load([
                'headOfFamily', 
                'members' => function ($query) {
                    $query->orderBy('first_name');
                }, 
                'creator'
            ]);

            // Transform family data
            $familyData = [
                'id' => $family->id,
                'family_name' => $family->family_name,
                'family_code' => $family->family_code,
                'address' => $family->address,
                'phone' => $family->phone,
                'email' => $family->email,
                'deanery' => $family->deanery,
                'parish' => $family->parish,
                'parish_section' => $family->parish_section,
                'head_of_family_id' => $family->head_of_family_id,
                'created_by' => $family->created_by,
                'created_at' => $family->created_at ? $family->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $family->updated_at ? $family->updated_at->format('Y-m-d H:i:s') : null,
                'head_of_family' => $family->headOfFamily ? [
                    'id' => $family->headOfFamily->id,
                    'first_name' => $family->headOfFamily->first_name,
                    'middle_name' => $family->headOfFamily->middle_name,
                    'last_name' => $family->headOfFamily->last_name,
                    'full_name' => trim(($family->headOfFamily->first_name ?? '') . ' ' . 
                                      ($family->headOfFamily->middle_name ?? '') . ' ' . 
                                      ($family->headOfFamily->last_name ?? '')),
                    'phone' => $family->headOfFamily->phone,
                    'email' => $family->headOfFamily->email,
                    'membership_status' => $family->headOfFamily->membership_status,
                ] : null,
                'members' => $family->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'first_name' => $member->first_name,
                        'middle_name' => $member->middle_name,
                        'last_name' => $member->last_name,
                        'full_name' => trim(($member->first_name ?? '') . ' ' . 
                                          ($member->middle_name ?? '') . ' ' . 
                                          ($member->last_name ?? '')),
                        'phone' => $member->phone,
                        'email' => $member->email,
                        'membership_status' => $member->membership_status,
                        'date_of_birth' => $member->date_of_birth,
                        'gender' => $member->gender,
                    ];
                }),
                'creator' => $family->creator ? [
                    'id' => $family->creator->id,
                    'name' => $family->creator->name,
                    'email' => $family->creator->email,
                ] : null,
            ];

            return Inertia::render('Families/Show', [
                'family' => $familyData,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in FamilyController@show', [
                'error' => $e->getMessage(),
                'family_id' => $family->id
            ]);

            return Inertia::render('Families/Show', [
                'family' => [
                    'id' => $family->id,
                    'family_name' => $family->family_name ?? 'Unknown Family',
                    'family_code' => $family->family_code,
                    'address' => $family->address,
                    'phone' => $family->phone,
                    'email' => $family->email,
                    'deanery' => $family->deanery,
                    'parish' => $family->parish,
                    'parish_section' => $family->parish_section,
                    'head_of_family_id' => $family->head_of_family_id,
                    'created_by' => $family->created_by,
                    'created_at' => $family->created_at ? $family->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $family->updated_at ? $family->updated_at->format('Y-m-d H:i:s') : null,
                    'head_of_family' => null,
                    'members' => [],
                    'creator' => null,
                ],
                'error' => 'Error loading family details: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Show the form for editing the specified family.
     */
    public function edit(Family $family): InertiaResponse
    {
        try {
            // Get all members without families (excluding current family members)
            $availableMembers = Member::where(function ($query) use ($family) {
                $query->whereNull('family_id')
                      ->orWhere('family_id', $family->id);
            })
            ->select('id', 'first_name', 'middle_name', 'last_name', 'phone', 'email', 'family_id')
            ->orderBy('first_name')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => trim(($member->first_name ?? '') . ' ' . 
                                  ($member->middle_name ?? '') . ' ' . 
                                  ($member->last_name ?? '')),
                    'phone' => $member->phone,
                    'email' => $member->email,
                    'family_id' => $member->family_id,
                ];
            });

            $family->load(['headOfFamily', 'members']);

            return Inertia::render('Families/Edit', [
                'family' => $family,
                'availableMembers' => $availableMembers,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in FamilyController@edit', [
                'error' => $e->getMessage(),
                'family_id' => $family->id
            ]);

            return Inertia::render('Families/Edit', [
                'family' => $family,
                'availableMembers' => [],
                'error' => 'Error loading family edit form: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the specified family in storage.
     */
    public function update(Request $request, Family $family)
    {
        $rules = [
            'family_name' => 'required|string|max:255',
            'family_code' => 'nullable|string|max:50|unique:families,family_code,' . $family->id,
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:families,email,' . $family->id,
            'deanery' => 'nullable|string|max:255',
            'parish' => 'nullable|string|max:255',
            'parish_section' => 'nullable|string|max:255',
            'head_of_family_id' => 'nullable|exists:members,id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            DB::beginTransaction();

            // Update family
            $family->update($validator->validated());

            // Handle head of family changes
            $oldHeadId = $family->getOriginal('head_of_family_id');
            $newHeadId = $request->head_of_family_id;

            if ($oldHeadId !== $newHeadId) {
                // Set new head of family
                if ($newHeadId) {
                    Member::where('id', $newHeadId)
                          ->update(['family_id' => $family->id]);
                }
            }

            DB::commit();

            return redirect()->route('families.index')
                           ->with('success', 'Family updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating family', [
                'error' => $e->getMessage(),
                'family_id' => $family->id,
                'data' => $request->all()
            ]);
            
            return redirect()->back()
                           ->with('error', 'Error updating family: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Remove the specified family from storage.
     */
    public function destroy(Family $family)
    {
        try {
            DB::beginTransaction();

            // Remove family_id from all members in this family
            Member::where('family_id', $family->id)
                  ->update(['family_id' => null]);

            // Delete the family
            $family->delete();

            DB::commit();
            
            return redirect()->route('families.index')
                           ->with('success', 'Family deleted successfully! All members have been removed from the family.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting family', [
                'error' => $e->getMessage(),
                'family_id' => $family->id
            ]);
            
            return redirect()->back()
                           ->with('error', 'Error deleting family: ' . $e->getMessage());
        }
    }

    /**
     * Add a member to a family.
     */
    public function addMember(Request $request, Family $family)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id'
        ]);

        try {
            $member = Member::findOrFail($request->member_id);
            
            // Check if member is already in a family
            if ($member->family_id) {
                return redirect()->back()
                               ->with('error', 'Member is already part of another family.');
            }

            $member->update(['family_id' => $family->id]);

            return redirect()->back()
                           ->with('success', 'Member added to family successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Error adding member to family: ' . $e->getMessage());
        }
    }

    /**
     * Remove a member from a family.
     */
    public function removeMember(Request $request, Family $family)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id'
        ]);

        try {
            DB::beginTransaction();

            $member = Member::findOrFail($request->member_id);
            
            // Check if this member is the head of family
            if ($family->head_of_family_id === $member->id) {
                $family->update(['head_of_family_id' => null]);
            }

            $member->update(['family_id' => null]);

            DB::commit();

            return redirect()->back()
                           ->with('success', 'Member removed from family successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                           ->with('error', 'Error removing member from family: ' . $e->getMessage());
        }
    }
}