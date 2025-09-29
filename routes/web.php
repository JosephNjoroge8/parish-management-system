<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\SacramentController;
use App\Http\Controllers\SacramentalRecordsController;
use App\Http\Controllers\TitheController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CommunityGroupController;
use App\Http\Controllers\ReportController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Health check endpoints (before authentication)
Route::get('/health', [\App\Http\Controllers\HealthController::class, 'simple']);
Route::get('/health/detailed', [\App\Http\Controllers\HealthController::class, 'check']);
Route::get('/health/database', [\App\Http\Controllers\HealthController::class, 'database']);
Route::get('/health/cache', [\App\Http\Controllers\HealthController::class, 'cache']);

// Welcome page (public)
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => false, // Disable public registration
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Authenticated routes - ALL dashboard access requires login authentication
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard - requires authentication and redirects non-authenticated users to login
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // API Routes for real-time updates
    Route::prefix('api')->group(function () {
        Route::get('/dashboard/stats', [DashboardController::class, 'getStatsApi'])->name('api.dashboard.stats');
        Route::get('/dashboard/recent-activities', [DashboardController::class, 'getRecentActivitiesApi'])->name('api.dashboard.recent-activities');
        Route::get('/dashboard/alerts', [DashboardController::class, 'getAlertsApi'])->name('api.dashboard.alerts');
        Route::get('/members/stats', [MemberController::class, 'getStatistics'])->name('api.members.stats');
    });
    
    // Profile Routes - also require authentication
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin-only routes (all parish management functionality)
Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    
    // Debug routes
    Route::get('/debug-stats', function () {
        try {
            $members_count = \App\Models\Member::count();
            $families_count = \App\Models\Family::count();
            $active_members = \App\Models\Member::where('membership_status', 'active')->count();
            
            return response()->json([
                'members_count' => $members_count,
                'families_count' => $families_count,
                'active_members' => $active_members,
                'database_status' => 'Connected',
                'tables_exist' => [
                    'members' => \Illuminate\Support\Facades\Schema::hasTable('members'),
                    'families' => \Illuminate\Support\Facades\Schema::hasTable('families'),
                    'users' => \Illuminate\Support\Facades\Schema::hasTable('users'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'database_status' => 'Error'
            ]);
        }
    })->name('debug.stats');
    
    // Debug admin permissions
    Route::get('/debug-admin', function() {
        $user = Auth::user();
        return response()->json([
            'authenticated' => Auth::check(),
            'user' => $user ? [
                'id' => $user->id,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'isSuperAdminByEmail' => $user->isSuperAdminByEmail(),
            ] : null,
            'message' => $user ? 
                ($user->isSuperAdminByEmail() ? 'User IS admin' : 'User is NOT admin') : 
                'No user authenticated'
        ]);
    });

    // ========================================
    // MEMBERS ROUTES
    // ========================================
    Route::prefix('members')->name('members.')->group(function () {
        Route::get('/create', [MemberController::class, 'create'])->name('create');
        Route::get('/search', [MemberController::class, 'search'])->name('search');
        Route::get('/export', [MemberController::class, 'export'])->name('export');
        Route::get('/import-template', [MemberController::class, 'downloadTemplate'])->name('import-template');
        Route::get('/statistics', [MemberController::class, 'getStatistics'])->name('statistics');
        Route::get('/by-church/{church}', [MemberController::class, 'getByChurch'])->name('by-church');
        Route::get('/by-group/{group}', [MemberController::class, 'getByGroup'])->name('by-group');
        Route::get('/by-status/{status}', [MemberController::class, 'getByStatus'])->name('by-status');
        Route::get('/', [MemberController::class, 'index'])->name('index');
        Route::post('/', [MemberController::class, 'store'])->name('store');
        Route::post('/import', [MemberController::class, 'import'])->name('import');
        Route::post('/bulk-delete', [MemberController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/bulk-update', [MemberController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/{member}', [MemberController::class, 'show'])->name('show')->where('member', '[0-9]+');
        Route::get('/{member}/edit', [MemberController::class, 'edit'])->name('edit')->where('member', '[0-9]+');
        Route::put('/{member}', [MemberController::class, 'update'])->name('update')->where('member', '[0-9]+');
        Route::patch('/{member}', [MemberController::class, 'update'])->name('update.patch')->where('member', '[0-9]+');
        Route::delete('/{member}', [MemberController::class, 'destroy'])->name('destroy')->where('member', '[0-9]+');
        Route::post('/{member}/toggle-status', [MemberController::class, 'toggleStatus'])->name('toggle-status')->where('member', '[0-9]+');
        Route::patch('/{member}/update-status', [MemberController::class, 'updateStatus'])->name('update-status')->where('member', '[0-9]+');
        Route::post('/bulk-update-status', [MemberController::class, 'bulkUpdateStatus'])->name('bulk-update-status');
        Route::get('/stats', [MemberController::class, 'getStatsApi'])->name('stats');
        Route::get('/stats/live', [MemberController::class, 'getStatsApi'])->name('stats.live');
        Route::get('/{member}/baptism-certificate', [MemberController::class, 'downloadBaptismCard'])->name('baptism-certificate')->where('member', '[0-9]+');
        Route::get('/{member}/marriage-certificate', [MemberController::class, 'downloadMarriageCertificate'])->name('marriage-certificate')->where('member', '[0-9]+');
    });
    
    // ========================================
    // FAMILIES ROUTES
    // ========================================
    Route::prefix('families')->name('families.')->group(function () {
        Route::get('/create', [FamilyController::class, 'create'])->name('create');
        Route::get('/search', [FamilyController::class, 'search'])->name('search');
        Route::get('/export', [FamilyController::class, 'export'])->name('export');
        Route::get('/statistics', [FamilyController::class, 'getStatistics'])->name('statistics');
        Route::get('/import-template', [FamilyController::class, 'downloadTemplate'])->name('import-template');
        Route::get('/', [FamilyController::class, 'index'])->name('index');
        Route::post('/', [FamilyController::class, 'store'])->name('store');
        Route::post('/import', [FamilyController::class, 'import'])->name('import');
        Route::post('/bulk-delete', [FamilyController::class, 'bulkDelete'])->name('bulk-delete');
        Route::get('/{family}', [FamilyController::class, 'show'])->name('show')->where('family', '[0-9]+');
        Route::get('/{family}/edit', [FamilyController::class, 'edit'])->name('edit')->where('family', '[0-9]+');
        Route::get('/{family}/tree', [FamilyController::class, 'familyTree'])->name('tree')->where('family', '[0-9]+');
        Route::put('/{family}', [FamilyController::class, 'update'])->name('update')->where('family', '[0-9]+');
        Route::patch('/{family}', [FamilyController::class, 'update'])->name('update.patch')->where('family', '[0-9]+');
        Route::delete('/{family}', [FamilyController::class, 'destroy'])->name('destroy')->where('family', '[0-9]+');
        Route::post('/{family}/toggle-status', [FamilyController::class, 'toggleStatus'])->name('toggle-status')->where('family', '[0-9]+');
        Route::post('/{family}/add-member', [FamilyController::class, 'addMember'])->name('add-member')->where('family', '[0-9]+');
        Route::delete('/{family}/remove-member', [FamilyController::class, 'removeMember'])->name('remove-member')->where('family', '[0-9]+');
    });
    
    // ========================================
    // SACRAMENTS ROUTES
    // ========================================
    Route::prefix('sacraments')->name('sacraments.')->group(function () {
        Route::get('/create', [SacramentController::class, 'create'])->name('create');
        Route::get('/search', [SacramentController::class, 'search'])->name('search');
        Route::get('/export', [SacramentController::class, 'export'])->name('export');
        Route::get('/statistics', [SacramentController::class, 'getStatistics'])->name('statistics');
        Route::get('/members/{member}/sacraments', [SacramentController::class, 'memberSacraments'])->name('member-sacraments')->where('member', '[0-9]+');
        Route::get('/certificates/{sacrament}/generate', [SacramentController::class, 'generateCertificate'])->name('certificate')->where('sacrament', '[0-9]+');
        Route::get('/', [SacramentController::class, 'index'])->name('index');
        Route::post('/', [SacramentController::class, 'store'])->name('store');
        Route::post('/import', [SacramentController::class, 'import'])->name('import');
        Route::post('/bulk-delete', [SacramentController::class, 'bulkDelete'])->name('bulk-delete');
        Route::get('/{sacrament}', [SacramentController::class, 'show'])->name('show')->where('sacrament', '[0-9]+');
        Route::get('/{sacrament}/edit', [SacramentController::class, 'edit'])->name('edit')->where('sacrament', '[0-9]+');
        Route::put('/{sacrament}', [SacramentController::class, 'update'])->name('update')->where('sacrament', '[0-9]+');
        Route::patch('/{sacrament}', [SacramentController::class, 'update'])->name('update.patch')->where('sacrament', '[0-9]+');
        Route::delete('/{sacrament}', [SacramentController::class, 'destroy'])->name('destroy')->where('sacrament', '[0-9]+');
    });
    
    // ========================================
    // SACRAMENTAL RECORDS ROUTES
    // ========================================
    Route::prefix('sacramental-records')->name('sacramental-records.')->group(function () {
        Route::post('/baptism', [SacramentalRecordsController::class, 'storeBaptismRecord'])->name('store-baptism');
        Route::get('/baptism/{member}', [SacramentalRecordsController::class, 'getBaptismRecord'])->name('get-baptism')->where('member', '[0-9]+');
        Route::post('/marriage', [SacramentalRecordsController::class, 'storeMarriageRecord'])->name('store-marriage');
        Route::get('/marriage', [SacramentalRecordsController::class, 'getMarriageRecord'])->name('get-marriage');
    });

    // ========================================
    // TITHES ROUTES
    // ========================================
    Route::prefix('tithes')->name('tithes.')->group(function () {
        Route::get('/create', [TitheController::class, 'create'])->name('create');
        Route::get('/search', [TitheController::class, 'search'])->name('search');
        Route::get('/export', [TitheController::class, 'export'])->name('export');
        Route::get('/reports', [TitheController::class, 'reports'])->name('reports');
        Route::get('/statistics', [TitheController::class, 'getStatistics'])->name('statistics');
        Route::get('/members/{member}/tithes', [TitheController::class, 'memberTithes'])->name('member-tithes')->where('member', '[0-9]+');
        Route::get('/receipts/{tithe}/generate', [TitheController::class, 'generateReceipt'])->name('receipt')->where('tithe', '[0-9]+');
        Route::get('/', [TitheController::class, 'index'])->name('index');
        Route::post('/', [TitheController::class, 'store'])->name('store');
        Route::post('/import', [TitheController::class, 'import'])->name('import');
        Route::post('/bulk-delete', [TitheController::class, 'bulkDelete'])->name('bulk-delete');
        Route::get('/{tithe}', [TitheController::class, 'show'])->name('show')->where('tithe', '[0-9]+');
        Route::get('/{tithe}/edit', [TitheController::class, 'edit'])->name('edit')->where('tithe', '[0-9]+');
        Route::put('/{tithe}', [TitheController::class, 'update'])->name('update')->where('tithe', '[0-9]+');
        Route::patch('/{tithe}', [TitheController::class, 'update'])->name('update.patch')->where('tithe', '[0-9]+');
        Route::delete('/{tithe}', [TitheController::class, 'destroy'])->name('destroy')->where('tithe', '[0-9]+');
    });
    
    // ========================================
    // ACTIVITIES ROUTES
    // ========================================
    Route::prefix('activities')->name('activities.')->group(function () {
        Route::get('/create', [ActivityController::class, 'create'])->name('create');
        Route::get('/search', [ActivityController::class, 'search'])->name('search');
        Route::get('/export', [ActivityController::class, 'export'])->name('export');
        Route::get('/statistics', [ActivityController::class, 'getStatistics'])->name('statistics');
        Route::get('/recent', [ActivityController::class, 'recent'])->name('recent');
        Route::get('/members/{member}/activities', [ActivityController::class, 'memberActivities'])->name('member-activities')->where('member', '[0-9]+');
        Route::get('/', [ActivityController::class, 'index'])->name('index');
        Route::post('/', [ActivityController::class, 'store'])->name('store');
        Route::post('/import', [ActivityController::class, 'import'])->name('import');
        Route::post('/bulk-delete', [ActivityController::class, 'bulkDelete'])->name('bulk-delete');
        Route::get('/{activity}', [ActivityController::class, 'show'])->name('show')->where('activity', '[0-9]+');
        Route::get('/{activity}/edit', [ActivityController::class, 'edit'])->name('edit')->where('activity', '[0-9]+');
        Route::put('/{activity}', [ActivityController::class, 'update'])->name('update')->where('activity', '[0-9]+');
        Route::patch('/{activity}', [ActivityController::class, 'update'])->name('update.patch')->where('activity', '[0-9]+');
        Route::delete('/{activity}', [ActivityController::class, 'destroy'])->name('destroy')->where('activity', '[0-9]+');
        Route::post('/{activity}/toggle-status', [ActivityController::class, 'toggleStatus'])->name('toggle-status')->where('activity', '[0-9]+');
        Route::post('/{activity}/members', [ActivityController::class, 'addMember'])->name('add-member')->where('activity', '[0-9]+');
        Route::delete('/{activity}/members/{member}', [ActivityController::class, 'removeMember'])->name('remove-member')->where(['activity' => '[0-9]+', 'member' => '[0-9]+']);
    });
    
    // ========================================
    // COMMUNITY GROUPS ROUTES
    // ========================================
    Route::prefix('community-groups')->name('community-groups.')->group(function () {
        Route::get('/', [CommunityGroupController::class, 'index'])->name('index');
        Route::get('/statistics', [CommunityGroupController::class, 'statistics'])->name('statistics');
        Route::get('/{groupName}', [CommunityGroupController::class, 'show'])->name('show')->where('groupName', '[^/]+');
    });
    
    // ========================================
    // REPORTS ROUTES
    // ========================================
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/analytics', [ReportController::class, 'analytics'])->name('analytics');
        Route::get('/dashboard', [ReportController::class, 'dashboard'])->name('dashboard');
        Route::get('/enhanced-statistics', [ReportController::class, 'getEnhancedStatistics'])->name('enhanced-statistics');
        Route::get('/export', [ReportController::class, 'export'])->name('export');
        Route::get('/members', [ReportController::class, 'membersReport'])->name('members');
        Route::get('/families', [ReportController::class, 'familiesReport'])->name('families');
        Route::get('/sacraments', [ReportController::class, 'sacramentsReport'])->name('sacraments');
        Route::get('/activities', [ReportController::class, 'activitiesReport'])->name('activities');
        Route::get('/community-groups', [ReportController::class, 'communityGroupsReport'])->name('community-groups');
        Route::get('/financial', [ReportController::class, 'financialReport'])->name('financial');
        Route::get('/financial/summary', [ReportController::class, 'financialSummary'])->name('financial.summary');
        Route::get('/financial/monthly', [ReportController::class, 'monthlyFinancialReport'])->name('financial.monthly');
        Route::get('/financial/yearly', [ReportController::class, 'yearlyFinancialReport'])->name('financial.yearly');
        Route::get('/members/export', [ReportController::class, 'exportMembersReport'])->name('members.export');
        Route::get('/families/export', [ReportController::class, 'exportFamiliesReport'])->name('families.export');
        Route::get('/sacraments/export', [ReportController::class, 'exportSacramentsReport'])->name('sacraments.export');
        Route::get('/activities/export', [ReportController::class, 'exportActivitiesReport'])->name('activities.export');
        Route::get('/financial/export', [ReportController::class, 'exportFinancialReport'])->name('financial.export');
        Route::post('/custom-export', [ReportController::class, 'customExport'])->name('custom-export');
        
        // Enhanced Export Routes - Matching Frontend Expectations
        Route::get('/export-filtered-members', [ReportController::class, 'exportFilteredMembers'])->name('export-filtered-members');
        Route::post('/export/filtered', [ReportController::class, 'exportFilteredMembers'])->name('export.filtered');
        
        // Category-specific export routes
        Route::get('/export-by-local-church', [ReportController::class, 'exportByLocalChurch'])->name('export-by-local-church');
        Route::get('/export-by-church-group', [ReportController::class, 'exportByChurchGroup'])->name('export-by-church-group');
        Route::get('/export-by-age-group', [ReportController::class, 'exportByAgeGroup'])->name('export-by-age-group');
        Route::get('/export-by-gender', [ReportController::class, 'exportByGender'])->name('export-by-gender');
        Route::get('/export-by-membership-status', [ReportController::class, 'exportByMembershipStatus'])->name('export-by-membership-status');
        Route::get('/export-by-marital-status', [ReportController::class, 'exportByMaritalStatus'])->name('export-by-marital-status');
        Route::get('/export-by-education-level', [ReportController::class, 'exportByEducationLevel'])->name('export-by-education-level');
        Route::get('/export-by-occupation', [ReportController::class, 'exportByOccupation'])->name('export-by-occupation');
        Route::get('/export-by-tribe', [ReportController::class, 'exportByTribe'])->name('export-by-tribe');
        Route::get('/export-by-community', [ReportController::class, 'exportByCommunity'])->name('export-by-community');
        Route::get('/export-by-state', [ReportController::class, 'exportByState'])->name('export-by-state');
        Route::get('/export-by-lga', [ReportController::class, 'exportByLga'])->name('export-by-lga');
        Route::get('/export-by-year-joined', [ReportController::class, 'exportByYearJoined'])->name('export-by-year-joined');
        
        // Sacrament-based exports
        Route::get('/export-baptized-members', [ReportController::class, 'exportBaptizedMembers'])->name('export-baptized-members');
        Route::get('/export-confirmed-members', [ReportController::class, 'exportConfirmedMembers'])->name('export-confirmed-members');
        Route::get('/export-married-members', [ReportController::class, 'exportMarriedMembers'])->name('export-married-members');
        
        // Special exports
        Route::get('/export-members-data', [ReportController::class, 'exportMembersDataRoute'])->name('export-members-data');
        Route::get('/export-comprehensive', [ReportController::class, 'exportComprehensiveReport'])->name('export-comprehensive');
        Route::get('/export-member-directory', [ReportController::class, 'exportMemberDirectory'])->name('export-member-directory');
        
        // Member list endpoints for viewing before download
        Route::get('/members-by-church', [ReportController::class, 'getMembersByLocalChurch'])->name('members-by-church');
        Route::get('/members-by-group', [ReportController::class, 'getMembersByChurchGroup'])->name('members-by-group');
        Route::get('/members-by-age-group', [ReportController::class, 'getMembersByAgeGroup'])->name('members-by-age-group');
        Route::get('/members-by-gender', [ReportController::class, 'getMembersByGender'])->name('members-by-gender');
        Route::get('/active-members', [ReportController::class, 'getActiveMembers'])->name('active-members');
        Route::get('/inactive-members', [ReportController::class, 'getInactiveMembers'])->name('inactive-members');
        Route::get('/transferred-members', [ReportController::class, 'getTransferredMembers'])->name('transferred-members');
        Route::get('/deceased-members', [ReportController::class, 'getDeceasedMembers'])->name('deceased-members');
        Route::get('/all-clear-records', [ReportController::class, 'getAllClearRecords'])->name('all-clear-records');
        Route::get('/filtered-members-list', [ReportController::class, 'getFilteredMembersList'])->name('filtered-members-list');
        Route::get('/member-directory', [ReportController::class, 'getMemberDirectory'])->name('member-directory');
        
        Route::get('/', [ReportController::class, 'index'])->name('index');
    });
});

// Admin panel routes
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show')->where('user', '[0-9]+');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit')->where('user', '[0-9]+');
        Route::put('/{user}', [UserController::class, 'update'])->name('update')->where('user', '[0-9]+');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy')->where('user', '[0-9]+');
        Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status')->where('user', '[0-9]+');
    });

    // Role Management
    Route::resource('roles', RoleController::class)->parameters(['roles' => 'role']);
});

require __DIR__.'/auth.php';