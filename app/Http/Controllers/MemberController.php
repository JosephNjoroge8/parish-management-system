<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Family;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller
{
    public function index(Request $request): Response
    {
        // Check what columns exist to avoid errors
        $hasStatus = Schema::hasColumn('members', 'status');
        $hasMembershipStatus = Schema::hasColumn('members', 'membership_status');
        
        // Determine which status column to use
        $statusColumn = $hasMembershipStatus ? 'membership_status' : ($hasStatus ? 'status' : null);

        $query = Member::with('family');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('family')) {
            $query->where('family_id', $request->get('family'));
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->get('gender'));
        }

        // Fix: Use the correct status column
        if ($request->filled('status') && $statusColumn) {
            $query->where($statusColumn, $request->get('status'));
        }

        // Add ordering
        $query->orderBy('first_name')->orderBy('last_name');

        // Paginate results
        $members = $query->paginate(12)->withQueryString();

        // Transform the data to match the frontend interface
        $members->getCollection()->transform(function ($member) use ($statusColumn) {
            // Calculate age if date_of_birth exists
            $age = null;
            if ($member->date_of_birth) {
                $age = \Carbon\Carbon::parse($member->date_of_birth)->age;
            }

            // Get status from the correct column
            $status = 'active'; // default
            if ($statusColumn && isset($member->{$statusColumn})) {
                $status = $member->{$statusColumn};
            }

            return [
                'id' => $member->id,
                'first_name' => $member->first_name,
                'middle_name' => $member->middle_name,
                'last_name' => $member->last_name,
                'phone' => $member->phone,
                'email' => $member->email,
                'gender' => $member->gender,
                'age' => $age,
                'date_of_birth' => $member->date_of_birth,
                'member_type' => $member->member_type ?? 'adult',
                'status' => $status, // Always provide status for frontend
                'family' => $member->family ? [
                    'id' => $member->family->id,
                    'family_name' => $member->family->family_name ?? $member->family->name
                ] : null,
            ];
        });

        // Get families for filter dropdown
        $families = Family::orderBy('family_name')->get(['id', 'family_name']);

        return Inertia::render('Members/Index', [
            'members' => $members,
            'families' => $families,
            'filters' => $request->only(['search', 'family', 'gender', 'status']),
        ]);
    }

    public function create()
    {
        try {
            // Ensure we always return an array
            $families = Family::orderBy('family_name')
                ->select('id', 'family_name')
                ->get()
                ->toArray(); // Convert to array to ensure consistency
        } catch (\Exception $e) {
            Log::error('Error fetching families for member creation: ' . $e->getMessage());
            $families = []; // Return empty array on error
        }
        
        return Inertia::render('Members/Create', [
            'families' => $families,
        ]);
    }

    public function store(Request $request)
    {
        // Check which status column to use
        $hasStatus = Schema::hasColumn('members', 'status');
        $hasMembershipStatus = Schema::hasColumn('members', 'membership_status');
        $statusColumn = $hasMembershipStatus ? 'membership_status' : ($hasStatus ? 'status' : null);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'id_number' => 'nullable|string|max:20|unique:members',
            'address' => 'nullable|string|max:500',
            'occupation' => 'nullable|string|max:255',
            'marital_status' => 'required|in:single,married,divorced,widowed',
            'family_id' => 'nullable|exists:families,id',
            'relationship_to_head' => 'nullable|string|max:255',
            'member_type' => 'required|in:adult,youth,child',
            'status' => 'nullable|in:active,inactive,transferred,deceased',
        ]);

        // Map status to correct column
        if ($statusColumn && isset($validated['status'])) {
            $validated[$statusColumn] = $validated['status'];
            unset($validated['status']); // Remove the mapped status
        }

        // Set default values
        $validated['membership_date'] = now()->format('Y-m-d');
        if ($statusColumn) {
            $validated[$statusColumn] = $validated[$statusColumn] ?? 'active';
        }

        Member::create($validated);

        return redirect()->route('members.index')
            ->with('success', 'Member created successfully.');
    }

    public function show(Member $member)
    {
        $member->load('family');
        
        // Transform for frontend
        $memberData = [
            'id' => $member->id,
            'first_name' => $member->first_name,
            'middle_name' => $member->middle_name,
            'last_name' => $member->last_name,
            'phone' => $member->phone,
            'email' => $member->email,
            'gender' => $member->gender,
            'date_of_birth' => $member->date_of_birth,
            'member_type' => $member->member_type ?? 'adult',
            'status' => $member->membership_status ?? $member->status ?? 'active',
            'family' => $member->family,
            'address' => $member->address,
            'occupation' => $member->occupation,
            'marital_status' => $member->marital_status,
            'membership_date' => $member->membership_date,
        ];

        return Inertia::render('Members/Show', [
            'member' => $memberData,
        ]);
    }

    public function edit(Member $member)
    {
        $families = Family::orderBy('family_name')->get(['id', 'family_name']);
        
        // Transform for frontend
        $memberData = [
            'id' => $member->id,
            'first_name' => $member->first_name,
            'middle_name' => $member->middle_name,
            'last_name' => $member->last_name,
            'phone' => $member->phone,
            'email' => $member->email,
            'gender' => $member->gender,
            'date_of_birth' => $member->date_of_birth,
            'member_type' => $member->member_type ?? 'adult',
            'status' => $member->membership_status ?? $member->status ?? 'active',
            'family_id' => $member->family_id,
            'address' => $member->address,
            'occupation' => $member->occupation,
            'marital_status' => $member->marital_status,
            'relationship_to_head' => $member->relationship_to_head,
        ];

        return Inertia::render('Members/Edit', [
            'member' => $memberData,
            'families' => $families,
        ]);
    }

    public function update(Request $request, Member $member)
    {
        // Check which status column to use
        $hasStatus = Schema::hasColumn('members', 'status');
        $hasMembershipStatus = Schema::hasColumn('members', 'membership_status');
        $statusColumn = $hasMembershipStatus ? 'membership_status' : ($hasStatus ? 'status' : null);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'id_number' => 'nullable|string|max:20|unique:members,id_number,' . $member->id,
            'address' => 'nullable|string|max:500',
            'occupation' => 'nullable|string|max:255',
            'marital_status' => 'required|in:single,married,divorced,widowed',
            'family_id' => 'nullable|exists:families,id',
            'relationship_to_head' => 'nullable|string|max:255',
            'member_type' => 'required|in:adult,youth,child',
            'status' => 'nullable|in:active,inactive,transferred,deceased',
        ]);

        // Map status to correct column
        if ($statusColumn && isset($validated['status'])) {
            $validated[$statusColumn] = $validated['status'];
            unset($validated['status']); // Remove the mapped status
        }

        $member->update($validated);

        return redirect()->route('members.index')
            ->with('success', 'Member updated successfully.');
    }

    public function destroy(Member $member)
    {
        $member->delete();

        return redirect()->route('members.index')
            ->with('success', 'Member deleted successfully.');
    }
}
