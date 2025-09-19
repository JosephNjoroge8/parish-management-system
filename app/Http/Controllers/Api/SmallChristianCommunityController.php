<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SmallChristianCommunityController extends Controller
{
    /**
     * Search for small Christian communities
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $limit = min((int) $request->input('limit', 10), 50); // Max 50 results
        
        if (strlen(trim($query)) < 2) {
            return response()->json(['data' => []]);
        }
        
        // Get distinct small Christian communities that match the search query
        $communities = Member::select('small_christian_community')
            ->where('small_christian_community', 'like', "%{$query}%")
            ->whereNotNull('small_christian_community')
            ->where('small_christian_community', '!=', '')
            ->distinct()
            ->orderBy('small_christian_community')
            ->limit($limit)
            ->pluck('small_christian_community')
            ->toArray();
        
        return response()->json(['data' => $communities]);
    }
    
    /**
     * Get all small Christian communities
     */
    public function index(Request $request): JsonResponse
    {
        $communities = Member::select('small_christian_community')
            ->selectRaw('COUNT(*) as member_count')
            ->whereNotNull('small_christian_community')
            ->where('small_christian_community', '!=', '')
            ->groupBy('small_christian_community')
            ->orderBy('small_christian_community')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->small_christian_community,
                    'member_count' => $item->member_count
                ];
            });
        
        return response()->json(['data' => $communities]);
    }
    
    /**
     * Get members by small Christian community
     */
    public function members(Request $request, string $community): JsonResponse
    {
        $members = Member::where('small_christian_community', $community)
            ->select('id', 'first_name', 'middle_name', 'last_name', 'phone', 'email', 'church_group', 'membership_status')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
        
        return response()->json([
            'data' => $members,
            'community' => $community,
            'total_members' => $members->count()
        ]);
    }
}
