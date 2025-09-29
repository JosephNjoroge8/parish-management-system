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
use App\Helpers\DatabaseCompatibilityHelper;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // Cache timeouts for different data types
    private $cacheTimeout = 300; // 5 minutes for static data
    private $quickCacheTimeout = 60; // 1 minute for dynamic data
    private $permissionCacheTimeout = 3600; // 1 hour for permissions
    
    public function index(): Response
    {
        // Enforce authentication at controller level as well
        if (!Auth::check()) {
            return redirect()->route('login')->with('message', 'Please log in to access the dashboard.');
        }
        
        $user = Auth::user();
        
        // Check if user account is active
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Your account has been deactivated. Please contact the administrator.');
        }
        
        // Log dashboard access for security monitoring
        Log::info('Dashboard accessed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()
        ]);
        
        // Cache user permissions for better performance
        $userPermissions = Cache::remember("user_permissions_{$user->id}", $this->permissionCacheTimeout, function() use ($user) {
            return $this->getUserPermissions($user);
        });
        
        // Get cached dashboard data with smart caching strategy
        $dashboardData = Cache::remember("dashboard_core_{$user->id}", $this->quickCacheTimeout, function() use ($user) {
            return [
                'stats' => $this->getOptimizedStats($user),
                'recentActivities' => $this->getOptimizedRecentActivities($user),
                'upcomingEvents' => $this->getCachedUpcomingEvents(),
                'analytics' => $this->getOptimizedAnalytics($user),
                'quickActions' => $this->getCachedQuickActions($user),
                'alerts' => $this->getOptimizedAlerts($user),
                'parishOverview' => $this->getOptimizedParishOverview($user),
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

    private function getOptimizedParishOverview($user): array
    {
        return Cache::remember("parish_overview", $this->cacheTimeout, function() {
            try {
                // Single optimized query for all member overview data (cross-database compatible)
                $startOfMonth = now()->startOfMonth();
                $memberOverview = DB::table('members')
                    ->selectRaw('
                        COUNT(*) as total_members,
                        SUM(CASE WHEN membership_status = "active" THEN 1 ELSE 0 END) as active_members,
                        SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as new_this_month,
                        SUM(CASE WHEN gender IN ("male", "Male") THEN 1 ELSE 0 END) as male_count,
                        SUM(CASE WHEN gender IN ("female", "Female") THEN 1 ELSE 0 END) as female_count
                    ', [$startOfMonth])
                    ->first();

                // Single query for family overview
                $familyOverview = DB::table('families')
                    ->selectRaw('
                        COUNT(*) as total_families,
                        COUNT(CASE WHEN EXISTS(SELECT 1 FROM members WHERE family_id = families.id) THEN 1 END) as with_members,
                        AVG((SELECT COUNT(*) FROM members WHERE family_id = families.id)) as avg_family_size
                    ')
                    ->first();

                // Get church and group distributions in single queries
                $churchDistribution = DB::table('members')
                    ->select('local_church', DB::raw('COUNT(*) as count'))
                    ->whereNotNull('local_church')
                    ->groupBy('local_church')
                    ->pluck('count', 'local_church')
                    ->toArray();

                $groupDistribution = DB::table('members')
                    ->select('church_group', DB::raw('COUNT(*) as count'))
                    ->whereNotNull('church_group')
                    ->groupBy('church_group')
                    ->pluck('count', 'church_group')
                    ->toArray();

                return [
                    'membership_overview' => [
                        'total_members' => $memberOverview->total_members ?? 0,
                        'active_members' => $memberOverview->active_members ?? 0,
                        'new_this_month' => $memberOverview->new_this_month ?? 0,
                        'by_church' => $churchDistribution,
                        'by_gender' => [
                            'male' => $memberOverview->male_count ?? 0,
                            'female' => $memberOverview->female_count ?? 0,
                        ],
                    ],
                    'family_overview' => [
                        'total_families' => $familyOverview->total_families ?? 0,
                        'families_with_members' => $familyOverview->with_members ?? 0,
                        'average_family_size' => round($familyOverview->avg_family_size ?? 0, 1),
                    ],
                ];
            } catch (\Exception $e) {
                Log::error('Parish overview error: ' . $e->getMessage());
                return [];
            }
        });
    }

    private function getUserPermissions($user): array
    {
        try {
            return [
                // Core user management (Super Admin only)
                'can_manage_users' => $this->userHasRole($user, 'super-admin'),
                'can_manage_roles' => $this->userHasRole($user, 'super-admin'),
                'can_access_admin' => $this->userHasRole($user, ['super-admin', 'admin']),
                
                // Member management
                'can_access_members' => $this->userHasPermission($user, 'access members'),
                'can_manage_members' => $this->userHasPermission($user, 'manage members'),
                'can_delete_members' => $this->userHasPermission($user, 'delete members'),
                'can_export_members' => $this->userHasPermission($user, 'export members'),
                
                // Family management
                'can_access_families' => $this->userHasPermission($user, 'access families'),
                'can_manage_families' => $this->userHasPermission($user, 'manage families'),
                'can_delete_families' => $this->userHasPermission($user, 'delete families'),
                
                // Sacrament management
                'can_access_sacraments' => $this->userHasPermission($user, 'access sacraments'),
                'can_manage_sacraments' => $this->userHasPermission($user, 'manage sacraments'),
                'can_delete_sacraments' => $this->userHasPermission($user, 'delete sacraments'),
                
                // Financial management
                'can_access_tithes' => $this->userHasPermission($user, 'access tithes'),
                'can_manage_tithes' => $this->userHasPermission($user, 'manage tithes'),
                'can_delete_tithes' => $this->userHasPermission($user, 'delete tithes'),
                'can_view_financial_reports' => $this->userHasPermission($user, 'view financial reports'),
                
                // Activities
                'can_access_activities' => $this->userHasPermission($user, 'access activities'),
                'can_manage_activities' => $this->userHasPermission($user, 'manage activities'),
                'can_delete_activities' => $this->userHasPermission($user, 'delete activities'),
                
                // Reports
                'can_access_reports' => $this->userHasPermission($user, 'access reports'),
                'can_export_reports' => $this->userHasPermission($user, 'export reports'),
                
                // Settings
                'can_access_settings' => $this->userHasPermission($user, 'access settings'),
                'can_manage_settings' => $this->userHasPermission($user, 'manage settings'),
                
                // Role information for frontend
                'is_super_admin' => $this->userHasRole($user, 'super-admin'),
                'is_admin' => $this->userHasRole($user, ['super-admin', 'admin']),
                'user_roles' => $this->getUserRoles($user),
            ];
        } catch (\Exception $e) {
            Log::error('Error getting user permissions', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            // Fallback permissions for Super Admin by is_admin flag
            if ($user->is_admin) {
                return [
                    'can_manage_users' => true,
                    'can_manage_roles' => true,
                    'can_access_admin' => true,
                    'can_access_members' => true,
                    'can_manage_members' => true,
                    'can_delete_members' => true,
                    'can_export_members' => true,
                    'can_access_families' => true,
                    'can_manage_families' => true,
                    'can_delete_families' => true,
                    'can_access_sacraments' => true,
                    'can_manage_sacraments' => true,
                    'can_delete_sacraments' => true,
                    'can_access_tithes' => true,
                    'can_manage_tithes' => true,
                    'can_delete_tithes' => true,
                    'can_view_financial_reports' => true,
                    'can_access_activities' => true,
                    'can_manage_activities' => true,
                    'can_delete_activities' => true,
                    'can_access_reports' => true,
                    'can_export_reports' => true,
                    'can_access_settings' => true,
                    'can_manage_settings' => true,
                    'is_super_admin' => true,
                    'is_admin' => true,
                    'user_roles' => ['super-admin'],
                ];
            }
            
            // Basic permissions for regular users
            return [
                'can_manage_users' => false,
                'can_manage_roles' => false,
                'can_access_admin' => false,
                'can_access_members' => true,
                'can_manage_members' => false,
                'can_delete_members' => false,
                'can_export_members' => false,
                'can_access_families' => true,
                'can_manage_families' => false,
                'can_delete_families' => false,
                'can_access_sacraments' => true,
                'can_manage_sacraments' => false,
                'can_delete_sacraments' => false,
                'can_access_tithes' => false,
                'can_manage_tithes' => false,
                'can_delete_tithes' => false,
                'can_view_financial_reports' => false,
                'can_access_activities' => true,
                'can_manage_activities' => false,
                'can_delete_activities' => false,
                'can_access_reports' => false,
                'can_export_reports' => false,
                'can_access_settings' => false,
                'can_manage_settings' => false,
                'is_super_admin' => false,
                'is_admin' => false,
                'user_roles' => ['viewer'],
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
        // Simplified: All authenticated admin users have all permissions
        return $user->is_admin;
    }

    private function userHasRole($user, $role): bool
    {
        // Simplified: All authenticated admin users are considered super-admin
        return $user->is_admin;
    }

    private function getOptimizedStats($user): array
    {
        return Cache::remember("optimized_stats", $this->quickCacheTimeout, function() {
            try {
                // SIMPLIFIED STATS FOR SQLite COMPATIBILITY
                $startOfMonth = now()->startOfMonth();
                                $startOfMonth = now()->startOfMonth();
                $memberStats = DB::table('members')
                    ->selectRaw('
                        COUNT(*) as total_members,
                        SUM(CASE WHEN membership_status = "active" THEN 1 ELSE 0 END) as active_members,
                        SUM(CASE WHEN gender IN ("male", "Male") THEN 1 ELSE 0 END) as male_count,
                        SUM(CASE WHEN gender IN ("female", "Female") THEN 1 ELSE 0 END) as female_count,
                        SUM(CASE WHEN family_id IS NULL THEN 1 ELSE 0 END) as without_families,
                        SUM(CASE WHEN membership_status = "inactive" THEN 1 ELSE 0 END) as inactive_members,
                        SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as new_this_month
                    ', [$startOfMonth])
                    ->first();

                // Simplified family stats
                $familyStats = DB::table('families')
                    ->selectRaw('
                        COUNT(*) as total_families,
                        COUNT(*) as active_families,
                        0 as new_families_this_month
                    ')
                    ->first();

                // Simplified tithe stats
                $titheStats = DB::table('tithes')
                    ->selectRaw('
                        SUM(amount) as total_this_month,
                        SUM(amount) as total_this_year,
                        COUNT(DISTINCT member_id) as contributors_this_month,
                        AVG(amount) as avg_amount
                    ')
                    ->first();

                // Simplified sacrament stats
                $sacramentStats = DB::table('sacraments')
                    ->selectRaw('
                        COUNT(*) as this_month,
                        COUNT(*) as this_year,
                        SUM(CASE WHEN sacrament_type = "baptism" THEN 1 ELSE 0 END) as baptisms,
                        SUM(CASE WHEN sacrament_type = "confirmation" THEN 1 ELSE 0 END) as confirmations,
                        SUM(CASE WHEN sacrament_type IN ("marriage", "matrimony") THEN 1 ELSE 0 END) as marriages
                    ')
                    ->first();

                // Community groups stats (cached table existence check)
                $hasGroupTables = Cache::remember('has_group_tables', 3600, function() {
                    return Schema::hasTable('community_groups') && Schema::hasTable('group_members');
                });

                $groupStats = null;
                $groupMemberCount = 0;
                if ($hasGroupTables) {
                    try {
                        // Check if is_active column exists in community_groups table
                        $columns = Schema::getColumnListing('community_groups');
                        $hasIsActiveColumn = in_array('is_active', $columns);
                        
                        if ($hasIsActiveColumn) {
                            $groupStats = DB::table('community_groups')
                                ->selectRaw('
                                    COUNT(*) as total_groups,
                                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_groups
                                ')
                                ->first();
                        } else {
                            // Fallback query without is_active column
                            $groupStats = DB::table('community_groups')
                                ->selectRaw('
                                    COUNT(*) as total_groups,
                                    COUNT(*) as active_groups
                                ')
                                ->first();
                        }
                        
                        $groupMemberCount = DB::table('group_members')->count();
                    } catch (\Exception $e) {
                        Log::error('Error getting group stats', ['error' => $e->getMessage()]);
                        // Create empty stats object
                        $groupStats = (object)['total_groups' => 0, 'active_groups' => 0];
                        $groupMemberCount = 0;
                    }
                }

                // Get distributions in optimized queries
                $churchDistribution = DB::table('members')
                    ->select('local_church', DB::raw('COUNT(*) as count'))
                    ->whereNotNull('local_church')
                    ->groupBy('local_church')
                    ->pluck('count', 'local_church')
                    ->toArray();

                $groupDistribution = DB::table('members')
                    ->select('church_group', DB::raw('COUNT(*) as count'))
                    ->whereNotNull('church_group')
                    ->groupBy('church_group')
                    ->pluck('count', 'church_group')
                    ->toArray();

                $statusDistribution = DB::table('members')
                    ->select('membership_status', DB::raw('COUNT(*) as count'))
                    ->groupBy('membership_status')
                    ->pluck('count', 'membership_status')
                    ->toArray();

                // Age groups calculated efficiently
                $today = now();
                $ageGroups = DB::table('members')
                    ->whereNotNull('date_of_birth')
                    ->selectRaw('
                        SUM(CASE WHEN date_of_birth > ? THEN 1 ELSE 0 END) as children,
                        SUM(CASE WHEN date_of_birth <= ? AND date_of_birth > ? THEN 1 ELSE 0 END) as youth,
                        SUM(CASE WHEN date_of_birth <= ? AND date_of_birth > ? THEN 1 ELSE 0 END) as adults,
                        SUM(CASE WHEN date_of_birth <= ? THEN 1 ELSE 0 END) as seniors
                    ')
                    ->addBinding([
                        $today->copy()->subYears(18)->toDateString(),
                        $today->copy()->subYears(18)->toDateString(),
                        $today->copy()->subYears(30)->toDateString(),
                        $today->copy()->subYears(30)->toDateString(),
                        $today->copy()->subYears(60)->toDateString(),
                        $today->copy()->subYears(60)->toDateString()
                    ])
                    ->first();

                return [
                    // Member stats
                    'total_members' => $memberStats->total_members ?? 0,
                    'active_members' => $memberStats->active_members ?? 0,
                    'new_members_this_month' => $memberStats->new_this_month ?? 0,
                    'member_growth_rate' => 0,
                    
                    // Family stats
                    'total_families' => $familyStats->total_families ?? 0,
                    'active_families' => $familyStats->active_families ?? 0,
                    'new_families_this_month' => $familyStats->new_families_this_month ?? 0,
                    
                    // Financial stats
                    'total_tithes_this_month' => round($titheStats->total_this_month ?? 0, 2),
                    'total_tithes_this_year' => round($titheStats->total_this_year ?? 0, 2),
                    'tithe_contributors_this_month' => $titheStats->contributors_this_month ?? 0,
                    'average_tithe_amount' => round($titheStats->avg_amount ?? 0, 2),
                    
                    // Sacrament stats
                    'sacraments_this_month' => $sacramentStats->this_month ?? 0,
                    'sacraments_this_year' => $sacramentStats->this_year ?? 0,
                    'baptisms_this_year' => $sacramentStats->baptisms ?? 0,
                    'confirmations_this_year' => $sacramentStats->confirmations ?? 0,
                    'marriages_this_year' => $sacramentStats->marriages ?? 0,
                    
                    // Community stats
                    'active_community_groups' => $groupStats->active_groups ?? 0,
                    'total_community_groups' => $groupStats->total_groups ?? 0,
                    'total_group_members' => $groupMemberCount,
                    'group_participation_rate' => $this->calculateParticipationRate(
                        $groupMemberCount, 
                        $memberStats->active_members ?? 0
                    ),
                    
                    // Demographics
                    'gender_distribution' => [
                        'male' => $memberStats->male_count ?? 0,
                        'female' => $memberStats->female_count ?? 0,
                    ],
                    
                    // Distributions
                    'church_distribution' => $churchDistribution,
                    'group_distribution' => $groupDistribution,
                    'status_distribution' => $statusDistribution,
                    
                    // Age groups
                    'age_groups' => [
                        'children' => $ageGroups->children ?? 0,
                        'youth' => $ageGroups->youth ?? 0,
                        'adults' => $ageGroups->adults ?? 0,
                        'seniors' => $ageGroups->seniors ?? 0,
                    ],
                    
                    // Additional stats
                    'total_users' => User::count(),
                    'active_users' => Schema::hasColumn('users', 'is_active') 
                        ? User::where('is_active', true)->count() 
                        : User::count(),
                ];
                
            } catch (\Exception $e) {
                Log::error('Dashboard optimized stats error: ' . $e->getMessage());
                return $this->getDefaultStats();
            }
        });
    }

    private function getOptimizedRecentActivities($user): array
    {
        return Cache::remember("recent_activities_{$user->id}", $this->quickCacheTimeout, function() use ($user) {
            $activities = [];

            try {
                if ($this->userHasPermission($user, 'access members')) {
                    // Get recent members with optimized query (no Eloquent relationships)
                    $recentMembers = DB::table('members')
                        ->leftJoin('families', 'members.family_id', '=', 'families.id')
                        ->select('members.id', 'members.first_name', 'members.last_name', 'members.created_at', 'families.family_name')
                        ->orderBy('members.created_at', 'desc')
                        ->limit(3)
                        ->get();

                    foreach ($recentMembers as $member) {
                        $memberName = trim($member->first_name . ' ' . $member->last_name) ?: 'Member #' . $member->id;
                        $activities[] = [
                            'id' => 'member_' . $member->id,
                            'type' => 'member_registration',
                            'title' => 'New member: ' . $memberName,
                            'description' => $member->family_name ? 'Family: ' . $member->family_name : 'Individual registration',
                            'time' => Carbon::parse($member->created_at)->diffForHumans(),
                            'icon' => 'user-plus',
                            'color' => 'green',
                            'link' => route('members.show', $member->id),
                        ];
                    }
                }

                if ($this->userHasPermission($user, 'access tithes')) {
                    // Optimized tithe query
                    $recentTithes = DB::table('tithes')
                        ->leftJoin('members', 'tithes.member_id', '=', 'members.id')
                        ->select('tithes.id', 'tithes.amount', 'tithes.date_given', 'members.first_name', 'members.last_name')
                        ->where('tithes.amount', '>', 1000)
                        ->orderBy('tithes.date_given', 'desc')
                        ->limit(3)
                        ->get();

                    foreach ($recentTithes as $tithe) {
                        $memberName = trim($tithe->first_name . ' ' . $tithe->last_name) ?: 'Anonymous';
                        $activities[] = [
                            'id' => 'tithe_' . $tithe->id,
                            'type' => 'tithe',
                            'title' => 'Tithe: KES ' . number_format($tithe->amount, 2),
                            'description' => 'From: ' . $memberName,
                            'time' => Carbon::parse($tithe->date_given)->diffForHumans(),
                            'icon' => 'dollar-sign',
                            'color' => 'emerald',
                            'link' => route('tithes.show', $tithe->id),
                        ];
                    }
                }

                if ($this->userHasPermission($user, 'access sacraments')) {
                    // Optimized sacrament query
                    $recentSacraments = DB::table('sacraments')
                        ->leftJoin('members', 'sacraments.member_id', '=', 'members.id')
                        ->select('sacraments.id', 'sacraments.sacrament_type', 'sacraments.sacrament_date', 'members.first_name', 'members.last_name')
                        ->orderBy('sacraments.sacrament_date', 'desc')
                        ->limit(2)
                        ->get();

                    foreach ($recentSacraments as $sacrament) {
                        $memberName = trim($sacrament->first_name . ' ' . $sacrament->last_name) ?: 'Unknown member';
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

                return array_slice($activities, 0, 6);

            } catch (\Exception $e) {
                Log::error('Recent activities error: ' . $e->getMessage());
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
        });
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

    private function getOptimizedAnalytics($user): array
    {
        return Cache::remember("dashboard_analytics", $this->cacheTimeout, function() use ($user) {
            $analytics = [];

            try {
                if ($this->userHasPermission($user, 'access members')) {
                    $analytics['membershipTrends'] = $this->getOptimizedMembershipTrends();
                }

                if ($this->userHasPermission($user, 'view financial reports')) {
                    $analytics['financialTrends'] = $this->getOptimizedFinancialTrends();
                }

                return $analytics;
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    private function getOptimizedAlerts($user): array
    {
        return Cache::remember("dashboard_alerts_{$user->id}", $this->cacheTimeout, function() use ($user) {
            $alerts = [];

            try {
                if ($this->userHasPermission($user, 'access members')) {
                    // Single query for member alerts
                    $memberAlerts = DB::table('members')
                        ->selectRaw('
                            SUM(CASE WHEN family_id IS NULL THEN 1 ELSE 0 END) as without_families,
                            SUM(CASE WHEN membership_status = "inactive" THEN 1 ELSE 0 END) as inactive
                        ')
                        ->first();

                    if ($memberAlerts->without_families > 0) {
                        $alerts[] = [
                            'type' => 'warning',
                            'title' => 'Members without families',
                            'message' => "{$memberAlerts->without_families} members are not assigned to any family",
                            'action' => 'Review members',
                            'link' => route('members.index'),
                        ];
                    }

                    if ($memberAlerts->inactive > 0) {
                        $alerts[] = [
                            'type' => 'info',
                            'title' => 'Inactive members',
                            'message' => "{$memberAlerts->inactive} members are marked as inactive",
                            'action' => 'Review status',
                            'link' => route('members.index'),
                        ];
                    }
                }

                if ($this->userHasPermission($user, 'view financial reports')) {
                    // Skip complex financial alerts for now - SQLite compatibility
                    // TODO: Implement SQLite-compatible date functions
                }

                return $alerts;
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    private function getCachedQuickActions($user): array
    {
        return Cache::remember("quick_actions_{$user->id}", 3600, function() use ($user) {
            return $this->getQuickActions($user);
        });
    }

    private function getCachedUpcomingEvents(): array
    {
        return Cache::remember("upcoming_events", $this->cacheTimeout, function() {
            return $this->getUpcomingEvents();
        });
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

    // Helper methods (optimized)
    private function calculateGrowthRate($current, $previous): float
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 2);
    }

    private function calculateParticipationRate($groupMembers, $totalActiveMembers): float
    {
        return $totalActiveMembers > 0 ? round(($groupMembers / $totalActiveMembers) * 100, 2) : 0;
    }

    private function getOptimizedMembershipTrends(): array
    {
        // Use DatabaseCompatibilityService for cross-database compatibility
        $dbService = app(\App\Services\DatabaseCompatibilityService::class);
        
        if ($dbService->isSQLite()) {
            return DB::table('members')
                ->selectRaw("strftime('%Y-%m', created_at) as month, COUNT(*) as count")
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->toArray();
        } else {
            return DB::table('members')
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->toArray();
        }
    }

    private function getOptimizedFinancialTrends(): array
    {
        // Use DatabaseCompatibilityHelper for cross-database compatibility
        if (DatabaseCompatibilityHelper::isSqlite()) {
            return DB::table('tithes')
                ->selectRaw("strftime('%Y-%m', date_given) as month, SUM(amount) as total")
                ->where('date_given', '>=', now()->subMonths(6))
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->toArray();
        } else {
            return DB::table('tithes')
                ->selectRaw('DATE_FORMAT(date_given, "%Y-%m") as month, SUM(amount) as total')
                ->where('date_given', '>=', now()->subMonths(6))
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->toArray();
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

    /**
     * Optimized API endpoint for live dashboard statistics
     */
    public function getStatsApi(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            // Use shorter cache for API calls
            $stats = Cache::remember("api_stats", 30, function() use ($user) {
                return $this->getOptimizedStats($user);
            });
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'timestamp' => now()->toISOString(),
                'cached' => true,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Dashboard stats API error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch stats',
                'data' => $this->getDefaultStats(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * API endpoint for recent activities
     */
    public function getRecentActivitiesApi(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $activities = Cache::remember("api_recent_activities_{$user->id}", 60, function() use ($user) {
                return $this->getOptimizedRecentActivities($user);
            });
            
            return response()->json([
                'success' => true,
                'data' => $activities,
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Recent activities API error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch recent activities',
                'data' => [],
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * API endpoint for dashboard alerts
     */
    public function getAlertsApi(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $alerts = Cache::remember("api_alerts_{$user->id}", 120, function() use ($user) {
                return $this->getOptimizedAlerts($user);
            });
            
            return response()->json([
                'success' => true,
                'data' => $alerts,
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Alerts API error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch alerts',
                'data' => [],
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }
}