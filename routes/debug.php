<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

// Simple member creation test route
Route::post('/debug-member-create', function(Request $request) {
    Log::info('Debug member creation attempt', [
        'data' => $request->all(),
        'user' => auth()->user() ? auth()->user()->email : 'not authenticated'
    ]);
    
    try {
        // Test basic member creation
        $member = \App\Models\Member::create([
            'first_name' => $request->input('first_name', 'Test'),
            'last_name' => $request->input('last_name', 'User'),
            'gender' => $request->input('gender', 'Male'),
            'membership_status' => 'active',
            'local_church' => 'Sacred Heart Kandara',
            'church_group' => 'Catholic Action',
            'membership_date' => now()->format('Y-m-d')
        ]);
        
        Log::info('Debug member created successfully', ['member_id' => $member->id]);
        
        return response()->json([
            'success' => true,
            'member' => $member,
            'message' => 'Test member created successfully',
            'redirect_url' => route('members.show', $member->id)
        ]);
        
    } catch (\Exception $e) {
        Log::error('Debug member creation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 422);
    }
})->middleware(['parish']);

// Check if routes are working
Route::get('/debug-routes', function() {
    return response()->json([
        'members_store_route_exists' => Route::has('members.store'),
        'current_user' => auth()->user() ? auth()->user()->email : 'not authenticated',
        'csrf_token' => csrf_token(),
        'session_id' => session()->getId()
    ]);
})->middleware(['parish']);