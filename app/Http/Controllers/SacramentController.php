<?php

// app/Http/Controllers/SacramentController.php
namespace App\Http\Controllers;

use App\Models\Sacrament;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SacramentController extends Controller
{
    public function create(Request $request)
    {
        $memberId = $request->get('member_id');
        
        // Get all members with basic info for dropdown - only select existing columns
        $members = Member::select('id', 'first_name', 'last_name', 'middle_name', 'id_number')
            ->where('membership_status', 'active')
            ->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'first_name' => $member->first_name,
                    'last_name' => $member->last_name,
                    'member_number' => $member->id_number ?? $member->id, // Use id_number or fallback to id
                    'id_number' => $member->id_number, // Include id_number for alias
                ];
            });

        return Inertia::render('Sacraments/Create', [
            'members' => $members,
            'member_id' => $memberId,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|string', // Accept string (id_number or system id)
            'sacrament_type' => 'required|string|in:baptism,eucharist,confirmation,reconciliation,anointing,marriage,holy_orders',
            'date_administered' => 'required|date|before_or_equal:today',
            'administered_by' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'certificate_number' => 'nullable|string|max:255',
            'witness_1' => 'nullable|string|max:255',
            'witness_2' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Find member by id_number first, then fallback to system id
        $member = Member::where('id_number', $validated['member_id'])->first();
        
        if (!$member) {
            // If not found by id_number, try by system id (for members without id_number)
            $member = Member::where('id', $validated['member_id'])->first();
        }

        if (!$member) {
            return back()->withErrors(['member_id' => 'Selected member not found.']);
        }

        try {
            // Check for duplicate sacrament (except reconciliation which can be repeated)
            if ($validated['sacrament_type'] !== 'reconciliation') {
                $existingSacrament = Sacrament::where('member_id', $member->id)
                    ->where('sacrament_type', $validated['sacrament_type'])
                    ->first();
                
                if ($existingSacrament) {
                    return back()->withErrors([
                        'sacrament_type' => "This member already has a {$validated['sacrament_type']} record."
                    ])->withInput();
                }
            }

            // Map form fields to database fields
            $sacramentData = [
                'member_id' => $member->id,
                'sacrament_type' => $validated['sacrament_type'],
                'sacrament_date' => $validated['date_administered'], // Map to correct DB field
                'celebrant' => $validated['administered_by'], // Map to correct DB field
                'location' => $validated['location'],
                'certificate_number' => $validated['certificate_number'],
                'witness_1' => $validated['witness_1'],
                'witness_2' => $validated['witness_2'],
                'notes' => $validated['notes'],
                'recorded_by' => Auth::id(), // Add the user who created the record
            ];

            $sacrament = Sacrament::create($sacramentData);

            return redirect()->route('sacraments.index')
                ->with('success', "Sacramental record created successfully for {$member->first_name} {$member->last_name}.");
        } catch (\Exception $e) {
            // Log the actual error for debugging
            Log::error('Failed to create sacramental record: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'member_id' => $member->id ?? 'unknown',
                'data' => $validated
            ]);
            
            return back()->withErrors(['error' => 'Failed to create sacramental record. Please try again.'])
                        ->withInput();
        }
    }

    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $sacramentType = $request->get('sacrament_type', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');

        $query = Sacrament::with(['member:id,first_name,last_name,id_number']);

        // Apply search filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('celebrant', 'like', "%{$search}%") // Fixed: was 'administered_by'
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('certificate_number', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($memberQuery) use ($search) {
                      $memberQuery->where('first_name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%")
                                  ->orWhere('id_number', 'like', "%{$search}%")
                                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                  });
            });
        }

        if ($sacramentType) {
            $query->where('sacrament_type', $sacramentType);
        }

        if ($dateFrom) {
            $query->where('sacrament_date', '>=', $dateFrom); // Fixed: was 'date_administered'
        }

        if ($dateTo) {
            $query->where('sacrament_date', '<=', $dateTo); // Fixed: was 'date_administered'
        }

        // Apply sorting
        $query->orderBy($sort, $direction);

        $sacraments = $query->paginate(20)->through(function ($sacrament) {
            // Add null safety check for member relationship
            if (!$sacrament->member) {
                return [
                    'id' => $sacrament->id,
                    'member_id_display' => 'Unknown',
                    'member_name' => 'Member Not Found',
                    'member_id_type' => 'Unknown',
                    'member' => [
                        'id' => null,
                        'first_name' => 'Unknown',
                        'last_name' => 'Member',
                        'id_number' => null,
                        'member_number' => 'Unknown',
                    ],
                    'sacrament_type' => $sacrament->sacrament_type,
                    'date_administered' => $sacrament->sacrament_date,
                    'administered_by' => $sacrament->celebrant,
                    'location' => $sacrament->location,
                    'certificate_number' => $sacrament->certificate_number,
                    'witness_1' => $sacrament->witness_1,
                    'witness_2' => $sacrament->witness_2,
                    'notes' => $sacrament->notes,
                    'created_at' => $sacrament->created_at,
                ];
            }

            return [
                'id' => $sacrament->id,
                'member_id_display' => $sacrament->member->id_number ?? $sacrament->member->id,
                'member_name' => $sacrament->member->first_name . ' ' . $sacrament->member->last_name,
                'member_id_type' => $sacrament->member->id_number ? 'ID Number' : 'System ID',
                // Add the member object that frontend expects
                'member' => [
                    'id' => $sacrament->member->id,
                    'first_name' => $sacrament->member->first_name,
                    'last_name' => $sacrament->member->last_name,
                    'id_number' => $sacrament->member->id_number,
                    'member_number' => $sacrament->member->id_number ?? $sacrament->member->id,
                ],
                'sacrament_type' => $sacrament->sacrament_type,
                'date_administered' => $sacrament->sacrament_date, // Fixed: map from sacrament_date
                'administered_by' => $sacrament->celebrant, // Fixed: map from celebrant
                'location' => $sacrament->location,
                'certificate_number' => $sacrament->certificate_number,
                'witness_1' => $sacrament->witness_1,
                'witness_2' => $sacrament->witness_2,
                'notes' => $sacrament->notes,
                'created_at' => $sacrament->created_at,
            ];
        });

        return Inertia::render('Sacraments/Index', [
            'sacraments' => $sacraments,
            'filters' => [
                'search' => $search,
                'sacrament_type' => $sacramentType,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'sacrament_types' => [
                'baptism' => 'Baptism',
                'eucharist' => 'First Holy Communion',
                'confirmation' => 'Confirmation',
                'reconciliation' => 'Reconciliation',
                'anointing' => 'Anointing of the Sick',
                'marriage' => 'Marriage',
                'holy_orders' => 'Holy Orders',
            ],
        ]);
    }

    public function show(Sacrament $sacrament)
    {
        $sacrament->load(['member:id,first_name,last_name,id_number,middle_name,date_of_birth,gender,phone,email']);

        return Inertia::render('Sacraments/Show', [
            'sacrament' => [
                'id' => $sacrament->id,
                'member_id_display' => $sacrament->member->id_number ?? $sacrament->member->id,
                'member' => [
                    'id' => $sacrament->member->id,
                    'name' => $sacrament->member->first_name . ' ' . $sacrament->member->last_name,
                    'full_name' => trim($sacrament->member->first_name . ' ' . ($sacrament->member->middle_name ?? '') . ' ' . $sacrament->member->last_name),
                    'id_number' => $sacrament->member->id_number,
                    'date_of_birth' => $sacrament->member->date_of_birth,
                    'gender' => $sacrament->member->gender,
                    'phone' => $sacrament->member->phone,
                    'email' => $sacrament->member->email,
                ],
                'sacrament_type' => $sacrament->sacrament_type,
                'date_administered' => $sacrament->sacrament_date, // Fixed: map from sacrament_date
                'administered_by' => $sacrament->celebrant, // Fixed: map from celebrant
                'location' => $sacrament->location,
                'certificate_number' => $sacrament->certificate_number,
                'witness_1' => $sacrament->witness_1,
                'witness_2' => $sacrament->witness_2,
                'notes' => $sacrament->notes,
                'created_at' => $sacrament->created_at,
                'updated_at' => $sacrament->updated_at,
            ]
        ]);
    }

    public function edit(Sacrament $sacrament)
    {
        $sacrament->load(['member:id,first_name,last_name,id_number']);

        // Get all members for dropdown
        $members = Member::select('id', 'first_name', 'last_name', 'middle_name', 'id_number')
            ->where('membership_status', 'active')
            ->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'first_name' => $member->first_name,
                    'last_name' => $member->last_name,
                    'member_number' => $member->id_number ?? $member->id,
                    'id_number' => $member->id_number,
                ];
            });

        return Inertia::render('Sacraments/Edit', [
            'sacrament' => [
                'id' => $sacrament->id,
                'member_id' => $sacrament->member->id_number ?? $sacrament->member->id, // Use alias for form
                'member' => [
                    'id' => $sacrament->member->id,
                    'first_name' => $sacrament->member->first_name,
                    'last_name' => $sacrament->member->last_name,
                    'id_number' => $sacrament->member->id_number,
                    'member_number' => $sacrament->member->id_number ?? $sacrament->member->id,
                ],
                'sacrament_type' => $sacrament->sacrament_type,
                'date_administered' => $sacrament->sacrament_date, // Fixed: map from sacrament_date
                'administered_by' => $sacrament->celebrant, // Fixed: map from celebrant
                'location' => $sacrament->location,
                'certificate_number' => $sacrament->certificate_number,
                'witness_1' => $sacrament->witness_1,
                'witness_2' => $sacrament->witness_2,
                'notes' => $sacrament->notes,
            ],
            'members' => $members,
        ]);
    }

    public function update(Request $request, Sacrament $sacrament)
    {
        $validated = $request->validate([
            'member_id' => 'required|string',
            'sacrament_type' => 'required|string|in:baptism,eucharist,confirmation,reconciliation,anointing,marriage,holy_orders',
            'date_administered' => 'required|date|before_or_equal:today',
            'administered_by' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'certificate_number' => 'nullable|string|max:255',
            'witness_1' => 'nullable|string|max:255',
            'witness_2' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Find member by id_number first, then fallback to system id
        $member = Member::where('id_number', $validated['member_id'])->first();
        
        if (!$member) {
            $member = Member::where('id', $validated['member_id'])->first();
        }

        if (!$member) {
            return back()->withErrors(['member_id' => 'Selected member not found.']);
        }

        try {
            // Map form fields to database fields
            $updateData = [
                'member_id' => $member->id,
                'sacrament_type' => $validated['sacrament_type'],
                'sacrament_date' => $validated['date_administered'], // Map to correct DB field
                'celebrant' => $validated['administered_by'], // Map to correct DB field
                'location' => $validated['location'],
                'certificate_number' => $validated['certificate_number'],
                'witness_1' => $validated['witness_1'],
                'witness_2' => $validated['witness_2'],
                'notes' => $validated['notes'],
            ];

            $sacrament->update($updateData);

            return redirect()->route('sacraments.index')
                ->with('success', 'Sacramental record updated successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update sacramental record. Please try again.'])
                        ->withInput();
        }
    }

    public function destroy(Sacrament $sacrament)
    {
        try {
            $memberName = $sacrament->member->first_name . ' ' . $sacrament->member->last_name;
            $sacrament->delete();

            return redirect()->route('sacraments.index')
                ->with('success', "Sacramental record for {$memberName} deleted successfully.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete sacramental record. Please try again.']);
        }
    }

    public function memberSacraments(Member $member)
    {
        $sacraments = $member->sacraments()->orderBy('sacrament_date')->get();
        
        return response()->json($sacraments);
    }
}