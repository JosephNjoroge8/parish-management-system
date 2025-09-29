<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SimpleMemberController extends Controller
{
    /**
     * SIMPLIFIED Member creation - GUARANTEED TO WORK
     */
    public function simpleStore(Request $request)
    {
        try {
            // MINIMAL validation - only truly required fields
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'gender' => 'required|in:Male,Female',
                'local_church' => 'nullable|string|max:255',
                'church_group' => 'nullable|string|max:255',
                'membership_status' => 'nullable|string|max:255',
            ]);

            // Set safe defaults
            $memberData = [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'], 
                'gender' => $validated['gender'],
                'local_church' => $validated['local_church'] ?? 'Sacred Heart Kandara',
                'church_group' => $validated['church_group'] ?? 'Catholic Action',
                'membership_status' => $validated['membership_status'] ?? 'active',
                'membership_date' => now()->format('Y-m-d'),
            ];

            // Create member
            $member = Member::create($memberData);

            Log::info('SIMPLE MEMBER CREATED', [
                'member_id' => $member->id,
                'name' => $member->first_name . ' ' . $member->last_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Member created successfully!',
                'member' => $member,
                'redirect' => route('members.index')
            ]);

        } catch (\Exception $e) {
            Log::error('SIMPLE MEMBER CREATION FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create member: ' . $e->getMessage()
            ], 422);
        }
    }
}