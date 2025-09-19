<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FamilyController extends Controller
{
    /**
     * Get family head data for inheritance
     */
    public function getFamilyHead(Request $request, int $familyId): JsonResponse
    {
        try {
            $family = Family::findOrFail($familyId);
            
            // Get the head of family or first adult member with tribal data
            $familyHead = Member::where('family_id', $familyId)
                ->where(function($query) {
                    $query->whereNotNull('tribe')
                          ->orWhereNotNull('clan')
                          ->orWhereNotNull('small_christian_community');
                })
                ->orderByRaw('CASE 
                    WHEN tribe IS NOT NULL AND clan IS NOT NULL THEN 1
                    WHEN tribe IS NOT NULL OR clan IS NOT NULL THEN 2
                    ELSE 3
                END')
                ->first();
            
            if (!$familyHead) {
                return response()->json([
                    'message' => 'No family head found with inheritable data',
                    'data' => null
                ], 404);
            }
            
            return response()->json([
                'data' => [
                    'id' => $familyHead->id,
                    'full_name' => $familyHead->full_name,
                    'tribe' => $familyHead->tribe,
                    'clan' => $familyHead->clan,
                    'small_christian_community' => $familyHead->small_christian_community,
                    'local_church' => $familyHead->local_church,
                    'family_name' => $family->family_name ?? null
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Family not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Search families
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $limit = min((int) $request->input('limit', 10), 50);
        
        if (strlen(trim($query)) < 2) {
            // Return first 10 families if no search query
            $families = Family::with(['members' => function($q) {
                $q->select('id', 'family_id', 'first_name', 'middle_name', 'last_name')
                  ->orderBy('date_of_birth');
            }])
            ->limit($limit)
            ->get();
        } else {
            // Search by family name or family code
            $families = Family::where(function($q) use ($query) {
                $q->where('family_name', 'like', "%{$query}%");
                if (property_exists($q->getModel(), 'family_code')) {
                    $q->orWhere('family_code', 'like', "%{$query}%");
                }
            })
            ->with(['members' => function($q) {
                $q->select('id', 'family_id', 'first_name', 'middle_name', 'last_name')
                  ->orderBy('date_of_birth');
            }])
            ->limit($limit)
            ->get();
        }
        
        $formattedFamilies = $families->map(function ($family) {
            $headOfFamily = $family->members->first();
            
            return [
                'id' => $family->id,
                'family_name' => $family->family_name,
                'family_code' => $family->family_code ?? null,
                'head_of_family_name' => $headOfFamily ? $headOfFamily->full_name : null,
                'address' => $family->address ?? null,
                'phone' => $family->phone ?? null,
                'email' => $family->email ?? null,
                'members_count' => $family->members->count(),
            ];
        });
        
        return response()->json(['data' => $formattedFamilies]);
    }
}
