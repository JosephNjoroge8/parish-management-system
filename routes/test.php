<?php

use App\Models\Member;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Test route to create a member directly (bypassing middleware)
Route::post('/test-create-member', function (Request $request) {
    try {
        $memberData = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'gender' => 'Male',
            'local_church' => 'Sacred Heart Kandara',
            'church_group' => 'Catholic Action',
            'membership_status' => 'active',
            'matrimony_status' => 'single',
            'occupation' => 'not_employed',
            'education_level' => 'none',
            'membership_date' => now()->format('Y-m-d'),
        ];

        $member = Member::create($memberData);

        return response()->json([
            'success' => true,
            'message' => 'Test member created successfully!',
            'member_id' => $member->id,
            'member' => $member
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Test route to check authentication status
Route::get('/test-auth', function () {
    return response()->json([
        'authenticated' => auth()->check(),
        'user' => auth()->user(),
        'admin_by_email' => auth()->check() ? auth()->user()->isSuperAdminByEmail() : false
    ]);
});