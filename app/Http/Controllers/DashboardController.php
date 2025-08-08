<?php
// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Family;
use App\Models\Sacrament;
use App\Models\Tithe;
use App\Models\CommunityGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();
        
        // Get user permissions for frontend with error handling
        $userPermissions = $this->getUserPermissions($user);
        
        // Get dashboard data with caching for better performance
        $dashboardData = Cache::remember('dashboard_data_' . $user->id, 300, function () use ($user) {
            return [
                'stats' => $this->getStats($user),
                'recentActivities' => $this->getRecentActivities($user),
                'upcomingEvents' => $this->getUpcomingEvents(),
                'analytics' => $this->getAnalytics($user),
                'quickActions' => $this->getQuickActions($user),
                'alerts' => $this->getAlerts($user),
                'parishOverview' => $this->getParishOverview($user),
            ];
        });

        return Inertia::render('Dashboard', array_merge($dashboardData, [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $this->getUserRoles($user),
                'permissions' => $userPermissions,
            ],
            'welcome_message' => $this->getWelcomeMessage(),
        ]));
    }

    private function getParishOverview($user): array
    {
        try {
            return [
                'membership_overview' => [
                    'total_members' => Member::count(),
                    'active_members' => Member::where('membership_status', 'active')->count(),
                    'new_this_month' => Member::whereMonth('created_at', now()->month)
                                            ->whereYear('created_at', now()->year)
                                            ->count(),
                    'by_church' => Member::groupBy('local_church')
                                        ->selectRaw('local_church, COUNT(*) as count')
                                        ->pluck('count', 'local_church')
                                        ->toArray(),
                    'by_age_group' => [
                        'children' => Member::where('member_type', 'child')->count(),
                        'youth' => Member::where('member_type', 'youth')->count(),
                        'adults' => Member::where('member_type', 'adult')->count(),
                    ],
                    'by_gender' => [
                        'male' => Member::where('gender', 'male')->count(),
                        'female' => Member::where('gender', 'female')->count(),
                    ],
                ],
                'family_overview' => [
                    'total_families' => Family::count(),
                    'families_with_members' => Family::has('members')->count(),
                    'average_family_size' => round(
                        Member::whereNotNull('family_id')->count() / max(Family::count(), 1), 
                        1
                    ),
                    'by_parish_section' => Family::groupBy('parish_section')
                                                ->selectRaw('parish_section, COUNT(*) as count')
                                                ->whereNotNull('parish_section')
                                                ->pluck('count', 'parish_section')
                                                ->toArray(),
                ],
                'community_engagement' => [
                    'active_groups' => Schema::hasTable('community_groups') ? CommunityGroup::where('is_active', true)->count() : 0,
                    'total_group_members' => Schema::hasTable('group_members') ? DB::table('group_members')->count() : 0,
                    'participation_rate' => $this->calculateGroupParticipationRate(),
                    'by_group_type' => Schema::hasTable('community_groups') ? CommunityGroup::groupBy('group_type')
                                                   ->selectRaw('group_type, COUNT(*) as count')
                                                   ->pluck('count', 'group_type')
                                                   ->toArray() : [],
                ],
                'sacramental_life' => [
                    'this_year_sacraments' => Sacrament::whereYear('sacrament_date', now()->year)->count(),
                    'by_type' => Sacrament::groupBy('sacrament_type')
                                         ->selectRaw('sacrament_type, COUNT(*) as count')
                                         ->pluck('count', 'sacrament_type')
                                         ->toArray(),
                    'recent_baptisms' => Sacrament::where('sacrament_type', 'baptism')
                                                 ->whereMonth('sacrament_date', now()->month)
                                                 ->count(),
                    'recent_confirmations' => Sacrament::where('sacrament_type', 'confirmation')
                                                      ->whereMonth('sacrament_date', now()->month)
                                                      ->count(),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Parish overview error: ' . $e->getMessage());
            return [];
        }
    }

    private function getUserPermissions($user): array
    {
        try {
            return [
                'can_manage_users' => $this->userHasRole($user, 'super-admin'),
                'can_access_members' => $this->userHasPermission($user, 'access members'),
                'can_manage_members' => $this->userHasPermission($user, 'manage members'),
                'can_create_members' => $this->userHasPermission($user, 'create members'),
                'can_edit_members' => $this->userHasPermission($user, 'edit members'),
                'can_delete_members' => $this->userHasPermission($user, 'delete members'),
                'can_export_members' => $this->userHasPermission($user, 'export members'),
                'can_access_families' => $this->userHasPermission($user, 'access families'),
                'can_manage_families' => $this->userHasPermission($user, 'manage families'),
                'can_access_sacraments' => $this->userHasPermission($user, 'access sacraments'),
                'can_manage_sacraments' => $this->userHasPermission($user, 'manage sacraments'),
                'can_access_tithes' => $this->userHasPermission($user, 'access tithes'),
                'can_manage_tithes' => $this->userHasPermission($user, 'manage tithes'),
                'can_access_reports' => $this->userHasPermission($user, 'access reports'),
                'can_view_financial_reports' => $this->userHasPermission($user, 'view financial reports'),
                'can_access_community_groups' => $this->userHasPermission($user, 'access community groups'),
                'can_manage_community_groups' => $this->userHasPermission($user, 'manage community groups'),
                'can_access_activities' => $this->userHasPermission($user, 'access activities'),
                'can_manage_activities' => $this->userHasPermission($user, 'manage activities'),
            ];
        } catch (\Exception $e) {
            return [
                'can_manage_users' => $user->email === 'admin@parish.com',
                'can_access_members' => true,
                'can_manage_members' => true,
                'can_create_members' => true,
                'can_edit_members' => true,
                'can_delete_members' => false,
                'can_export_members' => true,
                'can_access_families' => true,
                'can_manage_families' => true,
                'can_access_sacraments' => true,
                'can_manage_sacraments' => true,
                'can_access_tithes' => true,
                'can_manage_tithes' => true,
                'can_access_reports' => true,
                'can_view_financial_reports' => true,
                'can_access_community_groups' => true,
                'can_manage_community_groups' => true,
                'can_access_activities' => true,
                'can_manage_activities' => true,
            ];
        }
    }

    private function getUserRoles($user): array
    {
        try {
            if (method_exists($user, 'roles')) {
                return $user->roles->pluck('name')->toArray();
            }
            return [];
        } catch (\Exception $e) {
            return $user->email === 'admin@parish1.com' ? ['super-admin'] : ['viewer'];
        }
    }

    private function userHasPermission($user, $permission): bool
    {
        try {
            if (method_exists($user, 'hasPermissionTo')) {
                return $user->hasPermissionTo($permission);
            }
            return true;
        } catch (\Exception $e) {
            return true;
        }
    }

    private function userHasRole($user, $role): bool
    {
        try {
            if (method_exists($user, 'hasRole')) {
                return $user->hasRole($role);
            }
            return $user->email === 'admin@parish1.com' && $role === 'super-admin';
        } catch (\Exception $e) {
            return $user->email === 'admin@parish1.com' && $role === 'super-admin';
        }
    }

    private function getStats($user): array
    {
        try {
            $currentMonth = now()->month;
            $currentYear = now()->year;
            $lastMonth = now()->subMonth()->month;

            $stats = [];

            // Member stats - always get stats regardless of permissions for debugging
            $totalMembers = Member::count();
            $activeMembers = Member::where('membership_status', 'active')->count();
            
            Log::info("Dashboard Stats Debug - Total Members: {$totalMembers}, Active: {$activeMembers}");
            
            $stats['total_members'] = $totalMembers;
            $stats['active_members'] = $activeMembers;
            $stats['new_members_this_month'] = Member::whereMonth('created_at', $currentMonth)
                                                    ->whereYear('created_at', $currentYear)
                                                    ->count();
            $stats['member_growth_rate'] = $this->calculateGrowthRate(
                Member::whereMonth('created_at', $currentMonth)->whereYear('created_at', $currentYear)->count(),
                Member::whereMonth('created_at', $lastMonth)->whereYear('created_at', $currentYear)->count()
            );

            // Family stats - always get stats
            $stats['total_families'] = Family::count();
            $stats['active_families'] = Family::has('members')->count(); // Families with members
            $stats['new_families_this_month'] = Family::whereMonth('created_at', $currentMonth)
                                                      ->whereYear('created_at', $currentYear)
                                                      ->count();

            // Financial stats - always get stats
            $stats['total_tithes_this_month'] = Tithe::whereMonth('date_given', $currentMonth)
                                                      ->whereYear('date_given', $currentYear)
                                                      ->sum('amount') ?? 0;
            $stats['total_tithes_this_year'] = Tithe::whereYear('date_given', $currentYear)
                                                     ->sum('amount') ?? 0;
            $stats['tithe_contributors_this_month'] = Tithe::whereMonth('date_given', $currentMonth)
                                                           ->whereYear('date_given', $currentYear)
                                                           ->distinct('member_id')
                                                           ->count();
            $stats['average_tithe_amount'] = round(Tithe::whereMonth('date_given', $currentMonth)
                                                        ->whereYear('date_given', $currentYear)
                                                        ->avg('amount') ?? 0, 2);

            // Sacrament stats - always get stats
            $stats['sacraments_this_month'] = Sacrament::whereMonth('sacrament_date', $currentMonth)
                                                      ->whereYear('sacrament_date', $currentYear)
                                                      ->count();
            $stats['sacraments_this_year'] = Sacrament::whereYear('sacrament_date', $currentYear)->count();
            
            $stats['baptisms_this_year'] = Sacrament::where('sacrament_type', 'baptism')
                                                   ->whereYear('sacrament_date', $currentYear)
                                                   ->count();
            $stats['confirmations_this_year'] = Sacrament::where('sacrament_type', 'confirmation')
                                                        ->whereYear('sacrament_date', $currentYear)
                                                        ->count();
            $stats['marriages_this_year'] = Sacrament::where('sacrament_type', 'marriage')
                                                    ->whereYear('sacrament_date', $currentYear)
                                                    ->count();

            // Community engagement stats - check if table exists
            if (Schema::hasTable('community_groups')) {
                $stats['active_community_groups'] = CommunityGroup::where('is_active', true)->count();
                $stats['total_community_groups'] = CommunityGroup::count();
            } else {
                $stats['active_community_groups'] = 0;
                $stats['total_community_groups'] = 0;
            }
            
            if (Schema::hasTable('group_members')) {
                $stats['total_group_members'] = DB::table('group_members')->count();
                $stats['group_participation_rate'] = $this->calculateGroupParticipationRate();
            } else {
                $stats['total_group_members'] = 0;
                $stats['group_participation_rate'] = 0;
            }

            // Activity stats - always get stats
            if (Schema::hasTable('activities')) {
                $stats['total_activities'] = DB::table('activities')->count();
                $stats['active_activities'] = DB::table('activities')->where('status', 'active')->count();
                $stats['upcoming_activities'] = DB::table('activities')
                    ->where('start_date', '>', now()->toDateString())
                    ->count();
                $stats['activities_this_month'] = DB::table('activities')
                    ->whereMonth('start_date', $currentMonth)
                    ->whereYear('start_date', $currentYear)
                    ->count();
            } else {
                $stats['total_activities'] = 0;
                $stats['active_activities'] = 0;
                $stats['upcoming_activities'] = 0;
                $stats['activities_this_month'] = 0;
            }

            // Admin stats - always get stats
            $stats['total_users'] = User::count();
            if (Schema::hasColumn('users', 'is_active')) {
                $stats['active_users'] = User::where('is_active', true)->count();
            } else {
                $stats['active_users'] = User::count();
            }

            // Gender distribution
            $stats['gender_distribution'] = [
                'male' => Member::where('gender', 'male')->count(),
                'female' => Member::where('gender', 'female')->count(),
            ];

            // Age group distribution
            $stats['age_distribution'] = [
                'adult' => Member::where('member_type', 'adult')->count(),
                'youth' => Member::where('member_type', 'youth')->count(),
                'child' => Member::where('member_type', 'child')->count(),
            ];

            // Marital status distribution
            $stats['marital_distribution'] = [
                'single' => Member::where('marital_status', 'single')->count(),
                'married' => Member::where('marital_status', 'married')->count(),
                'divorced' => Member::where('marital_status', 'divorced')->count(),
                'widowed' => Member::where('marital_status', 'widowed')->count(),
            ];

            return $stats;
        } catch (\Exception $e) {
            Log::error('Dashboard stats error: ' . $e->getMessage());
            return $this->getDefaultStats();
        }
    }

    private function getRecentActivities($user): array
    {
        $activities = [];

        try {
            if ($this->userHasPermission($user, 'access members')) {
                $recentMembers = Member::with('family')
                                      ->latest()
                                      ->limit(3)
                                      ->get();

                foreach ($recentMembers as $member) {
                    $memberName = trim($member->first_name . ' ' . $member->last_name) 
                        ?: 'Member #' . $member->id;
                        
                    $activities[] = [
                        'id' => 'member_' . $member->id,
                        'type' => 'member_registration',
                        'title' => 'New member: ' . $memberName,
                        'description' => $member->family ? 'Family: ' . $member->family->family_name : 'Individual registration',
                        'time' => $member->created_at->diffForHumans(),
                        'icon' => 'user-plus',
                        'color' => 'green',
                        'link' => route('members.show', $member->id),
                    ];
                }
            }

            if ($this->userHasPermission($user, 'access tithes')) {
                $recentTithes = Tithe::with('member')
                                     ->where('amount', '>', 1000)
                                     ->latest('date_given')
                                     ->limit(3)
                                     ->get();

                foreach ($recentTithes as $tithe) {
                    $memberName = $tithe->member 
                        ? trim($tithe->member->first_name . ' ' . $tithe->member->last_name)
                        : 'Anonymous';
                        
                    $activities[] = [
                        'id' => 'tithe_' . $tithe->id,
                        'type' => 'tithe',
                        'title' => 'Tithe: KES ' . number_format($tithe->amount, 2),
                        'description' => 'From: ' . ($memberName ?: 'Anonymous donor'),
                        'time' => Carbon::parse($tithe->date_given)->diffForHumans(),
                        'icon' => 'dollar-sign',
                        'color' => 'emerald',
                        'link' => route('tithes.show', $tithe->id),
                    ];
                }
            }

            if ($this->userHasPermission($user, 'access sacraments')) {
                $recentSacraments = Sacrament::with('member')
                                           ->latest('sacrament_date')
                                           ->limit(2)
                                           ->get();

                foreach ($recentSacraments as $sacrament) {
                    $memberName = $sacrament->member 
                        ? trim($sacrament->member->first_name . ' ' . $sacrament->member->last_name)
                        : 'Unknown member';
                        
                    $activities[] = [
                        'id' => 'sacrament_' . $sacrament->id,
                        'type' => 'sacrament',
                        'title' => ucfirst($sacrament->sacrament_type) . ' administered',
                        'description' => 'For: ' . $memberName,
                        'time' => Carbon::parse($sacrament->sacrament_date)->diffForHumans(),
                        'icon' => 'star',
                        'color' => 'purple',
                        'link' => route('sacraments.show', $sacrament->id),
                    ];
                }
            }

            // Sort by most recent and limit to 6
            if (!empty($activities)) {
                usort($activities, function ($a, $b) {
                    return strtotime($b['time']) - strtotime($a['time']);
                });
            }

            return array_slice($activities, 0, 6);

        } catch (\Exception $e) {
            return [
                [
                    'id' => 'welcome',
                    'type' => 'system',
                    'title' => 'Welcome to Parish Management System',
                    'description' => 'Start by adding members and families',
                    'time' => 'Just now',
                    'icon' => 'home',
                    'color' => 'blue',
                ]
            ];
        }
    }

    private function getUpcomingEvents(): array
    {
        try {
            // Get upcoming activities from your activities table
            $upcomingActivities = DB::table('activities')
                ->where('start_date', '>', now()->toDateString())
                ->where('status', 'active')
                ->orderBy('start_date')
                ->limit(5)
                ->get();

            $events = [];
            foreach ($upcomingActivities as $activity) {
                $events[] = [
                    'id' => 'activity_' . $activity->id,
                    'name' => $activity->title,
                    'date' => Carbon::parse($activity->start_date)->format('M d, Y') . 
                             ($activity->start_time ? ' at ' . Carbon::parse($activity->start_time)->format('g:i A') : ''),
                    'location' => $activity->location ?: 'Parish',
                    'type' => $activity->activity_type ?: 'event',
                    'description' => $activity->description ?: '',
                ];
            }

            // Add default recurring events if no activities found
            if (empty($events)) {
                $events = [
                    [
                        'id' => 'sunday_mass',
                        'name' => 'Sunday Mass',
                        'date' => 'Every Sunday 8:00 AM',
                        'location' => 'Main Cathedral',
                        'type' => 'mass',
                        'description' => 'Weekly Sunday service',
                    ],
                    [
                        'id' => 'youth_meeting',
                        'name' => 'Youth Meeting',
                        'date' => 'Every Friday 6:00 PM',
                        'location' => 'Parish Hall',
                        'type' => 'meeting',
                        'description' => 'Weekly youth gathering',
                    ],
                ];
            }

            return $events;
        } catch (\Exception $e) {
            return [
                [
                    'id' => 'sunday_mass',
                    'name' => 'Sunday Mass',
                    'date' => 'Every Sunday 8:00 AM',
                    'location' => 'Main Cathedral',
                    'type' => 'mass',
                    'description' => 'Weekly Sunday service',
                ],
            ];
        }
    }

    private function getAnalytics($user): array
    {
        $analytics = [];

        try {
            if ($this->userHasPermission($user, 'access members')) {
                $analytics['membershipTrends'] = $this->getMembershipTrends();
            }

            if ($this->userHasPermission($user, 'view financial reports')) {
                $analytics['financialTrends'] = $this->getFinancialTrends();
            }

            return $analytics;
        } catch (\Exception $e) {
            return [
                'membershipTrends' => [],
                'financialTrends' => [],
            ];
        }
    }

    private function getQuickActions($user): array
    {
        $actions = [];

        try {
            if ($this->userHasPermission($user, 'manage members')) {
                $actions[] = [
                    'name' => 'Add New Member',
                    'description' => 'Register a new parish member',
                    'icon' => 'user-plus',
                    'color' => 'blue',
                    'link' => route('members.create'),
                ];
            }

            if ($this->userHasPermission($user, 'manage families')) {
                $actions[] = [
                    'name' => 'Register Family',
                    'description' => 'Add a new family to the parish',
                    'icon' => 'users',
                    'color' => 'green',
                    'link' => route('families.create'),
                ];
            }

            if ($this->userHasPermission($user, 'manage tithes')) {
                $actions[] = [
                    'name' => 'Record Tithe',
                    'description' => 'Add tithe/offering record',
                    'icon' => 'dollar-sign',
                    'color' => 'emerald',
                    'link' => route('tithes.create'),
                ];
            }

            if ($this->userHasPermission($user, 'manage sacraments')) {
                $actions[] = [
                    'name' => 'Add Sacrament',
                    'description' => 'Record sacrament administration',
                    'icon' => 'star',
                    'color' => 'purple',
                    'link' => route('sacraments.create'),
                ];
            }

            if ($this->userHasPermission($user, 'access reports')) {
                $actions[] = [
                    'name' => 'View Reports',
                    'description' => 'Generate parish reports',
                    'icon' => 'chart-bar',
                    'color' => 'indigo',
                    'link' => route('reports.index'),
                ];
            }

            if ($this->userHasPermission($user, 'manage community groups')) {
                $actions[] = [
                    'name' => 'Manage Groups',
                    'description' => 'Community group management',
                    'icon' => 'user-group',
                    'color' => 'orange',
                    'link' => route('community-groups.index'),
                ];
            }

            if ($this->userHasRole($user, 'super-admin')) {
                $actions[] = [
                    'name' => 'User Management',
                    'description' => 'Manage system users',
                    'icon' => 'cog',
                    'color' => 'gray',
                    'link' => route('admin.users.index'),
                ];
            }

            return $actions;
        }  catch (\Exception $e) {
            return [
                [
                    'name' => 'View Members',
                    'description' => 'Browse parish members',
                    'icon' => 'users',
                    'color' => 'blue',
                    'link' => route('members.index'),
                ],
                [
                    'name' => 'View Families',
                    'description' => 'Browse parish families',
                    'icon' => 'home',
                    'color' => 'green',
                    'link' => route('families.index'),
                ]
            ];
        }
    }

    private function getAlerts($user): array
    {
        $alerts = [];

        try {
            if ($this->userHasPermission($user, 'access members')) {
                $membersWithoutFamilies = Member::whereNull('family_id')->count();
                if ($membersWithoutFamilies > 0) {
                    $alerts[] = [
                        'type' => 'warning',
                        'title' => 'Members without families',
                        'message' => "{$membersWithoutFamilies} members are not assigned to any family",
                        'action' => 'Review members',
                        'link' => route('members.index'),
                    ];
                }

                // Check for inactive members
                $inactiveMembers = Member::where('membership_status', 'inactive')->count();
                if ($inactiveMembers > 0) {
                    $alerts[] = [
                        'type' => 'info',
                        'title' => 'Inactive members',
                        'message' => "{$inactiveMembers} members are marked as inactive",
                        'action' => 'Review status',
                        'link' => route('members.index'),
                    ];
                }
            }

            if ($this->userHasPermission($user, 'view financial reports')) {
                $thisMonthTithes = Tithe::whereMonth('date_given', now()->month)
                                       ->whereYear('date_given', now()->year)
                                       ->sum('amount');
                $lastMonthTithes = Tithe::whereMonth('date_given', now()->subMonth()->month)
                                       ->whereYear('date_given', now()->year)
                                       ->sum('amount');

                if ($thisMonthTithes < ($lastMonthTithes * 0.8) && $lastMonthTithes > 0) {
                    $alerts[] = [
                        'type' => 'info',
                        'title' => 'Tithe collection down',
                        'message' => 'This month\'s collection is 20% lower than last month',
                        'action' => 'View financial report',
                        'link' => route('reports.financial'),
                    ];
                }
            }

            return $alerts;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getWelcomeMessage(): string
    {
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        
        return "{$greeting}! Welcome to Parish Management System.";
    }

    // Helper methods
    private function calculateGrowthRate($current, $previous): float
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 2);
    }

    private function calculateGroupParticipationRate(): float
    {
        try {
            $totalActiveMembers = Member::where('membership_status', 'active')->count();
            
            if (Schema::hasTable('group_members')) {
                $membersInGroups = DB::table('group_members')
                                    ->distinct('member_id')
                                    ->count();
            } else {
                $membersInGroups = 0;
            }
            
            return $totalActiveMembers > 0 ? round(($membersInGroups / $totalActiveMembers) * 100, 2) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getMembershipTrends(): array
    {
        try {
            return Member::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                        ->where('created_at', '>=', now()->subMonths(6))
                        ->groupBy('month')
                        ->orderBy('month')
                        ->get()
                        ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getFinancialTrends(): array
    {
        try {
            return Tithe::selectRaw('DATE_FORMAT(date_given, "%Y-%m") as month, SUM(amount) as total')
                       ->where('date_given', '>=', now()->subMonths(6))
                       ->groupBy('month')
                       ->orderBy('month')
                       ->get()
                       ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getDefaultStats(): array
    {
        return [
            'total_members' => 0,
            'active_members' => 0,
            'new_members_this_month' => 0,
            'member_growth_rate' => 0,
            'total_families' => 0,
            'active_families' => 0,
            'new_families_this_month' => 0,
            'total_tithes_this_month' => 0,
            'total_tithes_this_year' => 0,
            'tithe_contributors_this_month' => 0,
            'average_tithe_amount' => 0,
            'sacraments_this_month' => 0,
            'sacraments_this_year' => 0,
            'baptisms_this_year' => 0,
            'confirmations_this_year' => 0,
            'marriages_this_year' => 0,
            'active_community_groups' => 0,
            'total_community_groups' => 0,
            'total_group_members' => 0,
            'group_participation_rate' => 0,
            'total_activities' => 0,
            'active_activities' => 0,
            'upcoming_activities' => 0,
            'activities_this_month' => 0,
            'total_users' => 0,
            'active_users' => 0,
            'gender_distribution' => ['male' => 0, 'female' => 0],
            'age_distribution' => ['adult' => 0, 'youth' => 0, 'child' => 0],
            'marital_distribution' => ['single' => 0, 'married' => 0, 'divorced' => 0, 'widowed' => 0],
        ];
    }
}