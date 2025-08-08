<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CommunityGroupController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'members_count');
        $direction = $request->get('direction', 'desc');

        $statistics = $this->getGroupStatistics($search, $sort, $direction);

        return Inertia::render('CommunityGroups/Index', [
            'statistics' => $statistics,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ]
        ]);
    }

    public function show(Request $request, $groupName)
    {
        // Decode the group name from URL and handle special characters
        $groupName = urldecode($groupName);
        $groupName = str_replace(['-', '_'], [' ', ' '], $groupName);
        
        // Handle specific group name mappings
        $groupMappings = [
            'CWA' => 'C.W.A',
            'C-W-A' => 'C.W.A',
            'Young-Parents' => 'Young Parents',
            'young-parents' => 'Young Parents',
        ];
        
        if (isset($groupMappings[$groupName])) {
            $groupName = $groupMappings[$groupName];
        }
        
        // Verify the group exists in our database
        $groupExists = Member::where('church_group', $groupName)->exists();
        
        if (!$groupExists) {
            // Try to find a similar group name
            $similarGroup = Member::select('church_group')
                ->whereNotNull('church_group')
                ->where('church_group', '!=', '')
                ->where('church_group', 'like', '%' . $groupName . '%')
                ->first();
                
            if ($similarGroup) {
                $groupName = $similarGroup->church_group;
            } else {
                abort(404, "Church group '{$groupName}' not found.");
            }
        }
        
        // Get members for this group with pagination and search
        $search = $request->get('search', '');
        $query = Member::where('church_group', $groupName);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        $members = $query->orderBy('last_name', 'asc')
                        ->orderBy('first_name', 'asc')
                        ->paginate(20)
                        ->withQueryString();

        $groupStats = $this->getGroupDetails($groupName);

        return Inertia::render('CommunityGroups/Show', [
            'group' => $groupStats,
            'members' => $members,
            'filters' => [
                'search' => $search,
            ]
        ]);
    }

    public function statistics()
    {
        return response()->json($this->getGroupStatistics());
    }

    private function getGroupStatistics($search = '', $sort = 'members_count', $direction = 'desc')
    {
        // Get all unique church groups from members table
        $groupsQuery = Member::select('church_group', DB::raw('count(*) as members_count'))
            ->whereNotNull('church_group')
            ->where('church_group', '!=', '')
            ->groupBy('church_group');

        if ($search) {
            $groupsQuery->having('church_group', 'like', '%' . $search . '%');
        }

        $groups = $groupsQuery->get();

        $groupsBreakdown = [];
        $totalMembers = 0;
        $groupsWithMembers = 0;
        $mostPopularGroup = '';
        $maxMembers = 0;

        foreach ($groups as $group) {
            $groupDetails = $this->getGroupDetails($group->church_group);
            
            if ($groupDetails['members_count'] > 0) {
                $groupsWithMembers++;
                $totalMembers += $groupDetails['members_count'];
                
                if ($groupDetails['members_count'] > $maxMembers) {
                    $maxMembers = $groupDetails['members_count'];
                    $mostPopularGroup = $group->church_group;
                }
            }

            $groupsBreakdown[] = $groupDetails;
        }

        // Sort groups
        $groupsBreakdown = collect($groupsBreakdown)->sortBy([
            [$sort, $direction]
        ])->values()->all();

        return [
            'total_groups' => count($groups),
            'total_members' => $totalMembers,
            'groups_with_members' => $groupsWithMembers,
            'average_members_per_group' => $groupsWithMembers > 0 ? round($totalMembers / $groupsWithMembers, 1) : 0,
            'most_popular_group' => $mostPopularGroup,
            'groups_breakdown' => $groupsBreakdown,
        ];
    }

    private function getGroupDetails($groupName)
    {
        $members = Member::where('church_group', $groupName);
        
        $totalMembers = $members->count();
        $activeMembers = $members->where('membership_status', 'active')->count();
        $inactiveMembers = $totalMembers - $activeMembers;
        
        $latestMember = $members->orderBy('created_at', 'desc')->first();

        return [
            'id' => md5($groupName),
            'name' => $groupName,
            'slug' => $this->createGroupSlug($groupName),
            'icon' => $this->getGroupIcon($groupName),
            'color' => $this->getGroupColor($groupName),
            'description' => $this->getGroupDescription($groupName),
            'members_count' => $totalMembers,
            'active_members' => $activeMembers,
            'inactive_members' => $inactiveMembers,
            'latest_member_joined' => $latestMember ? $latestMember->created_at : null,
            'created_date' => $members->orderBy('created_at', 'asc')->first()?->created_at,
        ];
    }

    private function createGroupSlug($groupName)
    {
        // Create a URL-safe slug for the group name
        $slug = strtolower($groupName);
        $slug = str_replace([' ', '.', '(', ')', ','], ['-', '', '', '', ''], $slug);
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    private function getGroupIcon($groupName)
    {
        $icons = [
            'PMC' => '👶',
            'Youth' => '🎯',
            'Young Parents' => '👨‍👩‍👧‍👦',
            'C.W.A' => '👩‍💼',
            'CMA' => '👨‍💼',
            'Choir' => '🎵',
        ];

        return $icons[$groupName] ?? '👥';
    }

    private function getGroupColor($groupName)
    {
        $colors = [
            'PMC' => 'bg-pink-500',
            'Youth' => 'bg-blue-500',
            'Young Parents' => 'bg-green-500',
            'C.W.A' => 'bg-purple-500',
            'CMA' => 'bg-indigo-500',
            'Choir' => 'bg-yellow-500',
        ];

        return $colors[$groupName] ?? 'bg-gray-500';
    }

    private function getGroupDescription($groupName)
    {
        $descriptions = [
            'PMC' => 'Pontifical Missionary Childhood - Children\'s mission group',
            'Youth' => 'Young adults and teenagers ministry',
            'Young Parents' => 'Parents with young children fellowship group',
            'C.W.A' => 'Catholic Women Association - Women fellowship',
            'CMA' => 'Catholic Men Association - Men fellowship',
            'Choir' => 'Parish music ministry and worship team',
        ];

        return $descriptions[$groupName] ?? 'Church ministry group';
    }
}
