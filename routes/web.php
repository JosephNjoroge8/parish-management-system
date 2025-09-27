<?php
// filepath: routes/web.php

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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => false, // Disable public registration
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Debug route to test active members export
Route::get('/debug-active-export', function () {
    try {
        $activeMembers = \App\Models\Member::where('membership_status', 'active')->get();
        
        // Create CSV response
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="active-members-debug.csv"',
        ];

        $callback = function() use ($activeMembers) {
            $file = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($file, ['ID', 'First Name', 'Last Name', 'Email', 'Status']);

            // Write data
            foreach ($activeMembers as $member) {
                fputcsv($file, [
                    $member->id,
                    $member->first_name,
                    $member->last_name,
                    $member->email,
                    $member->membership_status
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

// Debug route to test active members export
Route::get('/test-active-export', function () {
    try {
        $query = \App\Models\Member::where('membership_status', 'active');
        $count = $query->count();
        
        if ($count > 0) {
            return response()->json([
                'status' => 'success',
                'count' => $count,
                'sample_data' => $query->limit(3)->get(['id', 'first_name', 'last_name', 'membership_status'])->toArray(),
                'message' => 'Active members found successfully'
            ]);
        } else {
            return response()->json([
                'status' => 'empty',
                'count' => 0,
                'message' => 'No active members found'
            ]);
        }
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
});



// Main authenticated routes using the parish middleware group
Route::middleware('parish')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Debug route for testing statistics
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
    
    // System health check route (admin only)
    Route::get('/debug/system-health', [App\Http\Controllers\DebugController::class, 'systemHealth'])
        ->middleware(['auth', 'admin'])
        ->name('debug.system-health');
    
    // Cache clearing route (admin only)
    Route::post('/debug/clear-caches', [App\Http\Controllers\DebugController::class, 'clearCaches'])
        ->middleware(['auth', 'admin'])
        ->name('debug.clear-caches');
    


    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // ========================================
    // MEMBERS ROUTES - FIXED AND OPTIMIZED
    // ========================================
    Route::middleware('custom.permission:access members')->prefix('members')->name('members.')->group(function () {
        
        // 1. STATIC ROUTES FIRST (before any parameters)
        Route::get('/create', [MemberController::class, 'create'])
            ->middleware('custom.permission:manage members')
            ->name('create');
            
        Route::get('/search', [MemberController::class, 'search'])->name('search');
        Route::get('/export', [MemberController::class, 'export'])
            ->middleware('custom.permission:export members')
            ->name('export');
        Route::get('/import-template', [MemberController::class, 'downloadTemplate'])
            ->middleware('custom.permission:manage members')
            ->name('import-template');
        Route::get('/statistics', [MemberController::class, 'getStatistics'])->name('statistics');
        
        // 2. SPECIFIC WORD-BASED ROUTES (before generic parameters)
        Route::get('/by-church/{church}', [MemberController::class, 'getByChurch'])->name('by-church');
        Route::get('/by-group/{group}', [MemberController::class, 'getByGroup'])->name('by-group');
        Route::get('/by-status/{status}', [MemberController::class, 'getByStatus'])->name('by-status');
        
        // 3. LIST ROUTE (no parameters)
        Route::get('/', [MemberController::class, 'index'])->name('index');
        
        // 4. POST ROUTES (no GET conflicts)
        Route::post('/', [MemberController::class, 'store'])
            ->middleware('custom.permission:manage members')
            ->name('store');
        Route::post('/import', [MemberController::class, 'import'])
            ->middleware('custom.permission:manage members')
            ->name('import');
        Route::post('/bulk-delete', [MemberController::class, 'bulkDelete'])
            ->middleware('custom.permission:delete members')
            ->name('bulk-delete');
        Route::post('/bulk-update', [MemberController::class, 'bulkUpdate'])
            ->middleware('custom.permission:manage members')
            ->name('bulk-update');
        
        // 5. DYNAMIC ROUTES LAST (catch-all parameters)
        Route::get('/{member}', [MemberController::class, 'show'])->name('show')
            ->where('member', '[0-9]+'); // Only numeric IDs
        Route::get('/{member}/edit', [MemberController::class, 'edit'])
            ->middleware('custom.permission:manage members')
            ->name('edit')
            ->where('member', '[0-9]+');
        Route::put('/{member}', [MemberController::class, 'update'])
            ->middleware('custom.permission:manage members')
            ->name('update')
            ->where('member', '[0-9]+');
        Route::patch('/{member}', [MemberController::class, 'update'])
            ->middleware('custom.permission:manage members')
            ->name('update.patch')
            ->where('member', '[0-9]+');
        Route::delete('/{member}', [MemberController::class, 'destroy'])
            ->middleware('custom.permission:delete members')
            ->name('destroy')
            ->where('member', '[0-9]+');
        Route::post('/{member}/toggle-status', [MemberController::class, 'toggleStatus'])
            ->middleware('custom.permission:manage members')
            ->name('toggle-status')
            ->where('member', '[0-9]+');
        Route::get('/{member}/baptism-certificate', [App\Http\Controllers\BaptismRecordController::class, 'downloadBaptismCertificate'])
            ->middleware('custom.permission:manage members')
            ->name('baptism-certificate')
            ->where('member', '[0-9]+');
        Route::get('/{member}/marriage-certificate', [App\Http\Controllers\MarriageRecordController::class, 'findMemberMarriageCertificate'])
            ->middleware('custom.permission:manage members')
            ->name('marriage-certificate')
            ->where('member', '[0-9]+');
    });
    
    // ========================================
    // FAMILIES ROUTES - FIXED AND OPTIMIZED
    // ========================================
    Route::middleware('custom.permission:access families')->prefix('families')->name('families.')->group(function () {
        
        // 1. STATIC ROUTES FIRST
        Route::get('/create', [FamilyController::class, 'create'])
            ->middleware('custom.permission:manage families')
            ->name('create');
        Route::get('/search', [FamilyController::class, 'search'])->name('search');
        Route::get('/export', [FamilyController::class, 'export'])
            ->middleware('custom.permission:manage families')
            ->name('export');
        Route::get('/statistics', [FamilyController::class, 'getStatistics'])->name('statistics');
        Route::get('/import-template', [FamilyController::class, 'downloadTemplate'])
            ->middleware('custom.permission:manage families')
            ->name('import-template');
        
        // 2. LIST ROUTE
        Route::get('/', [FamilyController::class, 'index'])->name('index');
        
        // 3. POST ROUTES
        Route::post('/', [FamilyController::class, 'store'])
            ->middleware('custom.permission:manage families')
            ->name('store');
        Route::post('/import', [FamilyController::class, 'import'])
            ->middleware('custom.permission:manage families')
            ->name('import');
        Route::post('/bulk-delete', [FamilyController::class, 'bulkDelete'])
            ->middleware('custom.permission:delete families')
            ->name('bulk-delete');
        
        // 4. DYNAMIC ROUTES LAST
        Route::get('/{family}', [FamilyController::class, 'show'])->name('show')
            ->where('family', '[0-9]+');
        Route::get('/{family}/edit', [FamilyController::class, 'edit'])
            ->middleware('custom.permission:manage families')
            ->name('edit')
            ->where('family', '[0-9]+');
        Route::get('/{family}/tree', [FamilyController::class, 'familyTree'])
            ->name('tree')
            ->where('family', '[0-9]+');
        Route::put('/{family}', [FamilyController::class, 'update'])
            ->middleware('custom.permission:manage families')
            ->name('update')
            ->where('family', '[0-9]+');
        Route::patch('/{family}', [FamilyController::class, 'update'])
            ->middleware('custom.permission:manage families')
            ->name('update.patch')
            ->where('family', '[0-9]+');
        Route::delete('/{family}', [FamilyController::class, 'destroy'])
            ->middleware('custom.permission:delete families')
            ->name('destroy')
            ->where('family', '[0-9]+');
        Route::post('/{family}/toggle-status', [FamilyController::class, 'toggleStatus'])
            ->middleware('custom.permission:manage families')
            ->name('toggle-status')
            ->where('family', '[0-9]+');
        
        // Family-Member relationship routes
        Route::post('/{family}/add-member', [FamilyController::class, 'addMember'])
            ->middleware('custom.permission:manage families')
            ->name('add-member')
            ->where('family', '[0-9]+');
        Route::delete('/{family}/remove-member', [FamilyController::class, 'removeMember'])
            ->middleware('custom.permission:manage families')
            ->name('remove-member')
            ->where('family', '[0-9]+');
    });
    
    // ========================================
    // SACRAMENTS ROUTES - FIXED AND OPTIMIZED
    // ========================================
    Route::middleware('custom.permission:access sacraments')->prefix('sacraments')->name('sacraments.')->group(function () {
        
        // 1. STATIC ROUTES FIRST
        Route::get('/create', [SacramentController::class, 'create'])
            ->middleware('custom.permission:manage sacraments')
            ->name('create');
        Route::get('/search', [SacramentController::class, 'search'])->name('search');
        Route::get('/export', [SacramentController::class, 'export'])
            ->middleware('custom.permission:manage sacraments')
            ->name('export');
        Route::get('/statistics', [SacramentController::class, 'getStatistics'])->name('statistics');
        
        // 2. SPECIFIC ROUTES
        Route::get('/members/{member}/sacraments', [SacramentController::class, 'memberSacraments'])
            ->name('member-sacraments')
            ->where('member', '[0-9]+');
        Route::get('/certificates/{sacrament}/generate', [SacramentController::class, 'generateCertificate'])
            ->middleware('custom.permission:manage sacraments')
            ->name('certificate')
            ->where('sacrament', '[0-9]+');
        
        // 3. LIST ROUTE
        Route::get('/', [SacramentController::class, 'index'])->name('index');
        
        // 4. POST ROUTES
        Route::post('/', [SacramentController::class, 'store'])
            ->middleware('custom.permission:manage sacraments')
            ->name('store');
        Route::post('/import', [SacramentController::class, 'import'])
            ->middleware('custom.permission:manage sacraments')
            ->name('import');
        Route::post('/bulk-delete', [SacramentController::class, 'bulkDelete'])
            ->middleware('custom.permission:delete sacraments')
            ->name('bulk-delete');
        
        // 5. DYNAMIC ROUTES LAST
        Route::get('/{sacrament}', [SacramentController::class, 'show'])->name('show')
            ->where('sacrament', '[0-9]+');
        Route::get('/{sacrament}/edit', [SacramentController::class, 'edit'])
            ->middleware('custom.permission:manage sacraments')
            ->name('edit')
            ->where('sacrament', '[0-9]+');
        Route::put('/{sacrament}', [SacramentController::class, 'update'])
            ->middleware('custom.permission:manage sacraments')
            ->name('update')
            ->where('sacrament', '[0-9]+');
        Route::patch('/{sacrament}', [SacramentController::class, 'update'])
            ->middleware('custom.permission:manage sacraments')
            ->name('update.patch')
            ->where('sacrament', '[0-9]+');
        Route::delete('/{sacrament}', [SacramentController::class, 'destroy'])
            ->middleware('custom.permission:delete sacraments')
            ->name('destroy')
            ->where('sacrament', '[0-9]+');
    });
    
    // ========================================
    // SACRAMENTAL RECORDS ROUTES
    // ========================================
    Route::middleware('custom.permission:access sacraments')->prefix('sacramental-records')->name('sacramental-records.')->group(function () {
        // Baptism Records
        Route::post('/baptism', [SacramentalRecordsController::class, 'storeBaptismRecord'])
            ->middleware('custom.permission:manage sacraments')
            ->name('store-baptism');
        Route::get('/baptism/{member}', [SacramentalRecordsController::class, 'getBaptismRecord'])
            ->name('get-baptism')
            ->where('member', '[0-9]+');
            
        // Marriage Records
        Route::post('/marriage', [SacramentalRecordsController::class, 'storeMarriageRecord'])
            ->middleware('custom.permission:manage sacraments')
            ->name('store-marriage');
        Route::get('/marriage', [SacramentalRecordsController::class, 'getMarriageRecord'])
            ->name('get-marriage');
    });

    // ========================================
    // TITHES ROUTES - FIXED AND OPTIMIZED
    // ========================================
    Route::middleware('custom.permission:access tithes')->prefix('tithes')->name('tithes.')->group(function () {
        
        // 1. STATIC ROUTES FIRST
        Route::get('/create', [TitheController::class, 'create'])
            ->middleware('custom.permission:manage tithes')
            ->name('create');
        Route::get('/search', [TitheController::class, 'search'])->name('search');
        Route::get('/export', [TitheController::class, 'export'])
            ->middleware('custom.permission:manage tithes')
            ->name('export');
        Route::get('/reports', [TitheController::class, 'reports'])
            ->middleware('custom.permission:view financial reports')
            ->name('reports');
        Route::get('/statistics', [TitheController::class, 'getStatistics'])->name('statistics');
        
        // 2. SPECIFIC ROUTES
        Route::get('/members/{member}/tithes', [TitheController::class, 'memberTithes'])
            ->name('member-tithes')
            ->where('member', '[0-9]+');
        Route::get('/receipts/{tithe}/generate', [TitheController::class, 'generateReceipt'])
            ->middleware('custom.permission:manage tithes')
            ->name('receipt')
            ->where('tithe', '[0-9]+');
        
        // 3. LIST ROUTE
        Route::get('/', [TitheController::class, 'index'])->name('index');
        
        // 4. POST ROUTES
        Route::post('/', [TitheController::class, 'store'])
            ->middleware('custom.permission:manage tithes')
            ->name('store');
        Route::post('/import', [TitheController::class, 'import'])
            ->middleware('custom.permission:manage tithes')
            ->name('import');
        Route::post('/bulk-delete', [TitheController::class, 'bulkDelete'])
            ->middleware('custom.permission:delete tithes')
            ->name('bulk-delete');
        
        // 5. DYNAMIC ROUTES LAST
        Route::get('/{tithe}', [TitheController::class, 'show'])->name('show')
            ->where('tithe', '[0-9]+');
        Route::get('/{tithe}/edit', [TitheController::class, 'edit'])
            ->middleware('custom.permission:manage tithes')
            ->name('edit')
            ->where('tithe', '[0-9]+');
        Route::put('/{tithe}', [TitheController::class, 'update'])
            ->middleware('custom.permission:manage tithes')
            ->name('update')
            ->where('tithe', '[0-9]+');
        Route::patch('/{tithe}', [TitheController::class, 'update'])
            ->middleware('custom.permission:manage tithes')
            ->name('update.patch')
            ->where('tithe', '[0-9]+');
        Route::delete('/{tithe}', [TitheController::class, 'destroy'])
            ->middleware('custom.permission:delete tithes')
            ->name('destroy')
            ->where('tithe', '[0-9]+');
    });
    
    // ========================================
    // ACTIVITIES ROUTES - FIXED AND OPTIMIZED
    // ========================================
    Route::middleware('custom.permission:access activities')->prefix('activities')->name('activities.')->group(function () {
        
        // 1. STATIC ROUTES FIRST
        Route::get('/create', [ActivityController::class, 'create'])
            ->middleware('custom.permission:manage activities')
            ->name('create');
        Route::get('/search', [ActivityController::class, 'search'])->name('search');
        Route::get('/export', [ActivityController::class, 'export'])
            ->middleware('custom.permission:manage activities')
            ->name('export');
        Route::get('/statistics', [ActivityController::class, 'getStatistics'])->name('statistics');
        Route::get('/recent', [ActivityController::class, 'recent'])->name('recent');
        
        // 2. SPECIFIC ROUTES
        Route::get('/members/{member}/activities', [ActivityController::class, 'memberActivities'])
            ->name('member-activities')
            ->where('member', '[0-9]+');
        
        // 3. LIST ROUTE
        Route::get('/', [ActivityController::class, 'index'])->name('index');
        
        // 4. POST ROUTES
        Route::post('/', [ActivityController::class, 'store'])
            ->middleware('custom.permission:manage activities')
            ->name('store');
        Route::post('/import', [ActivityController::class, 'import'])
            ->middleware('custom.permission:manage activities')
            ->name('import');
        Route::post('/bulk-delete', [ActivityController::class, 'bulkDelete'])
            ->middleware('custom.permission:delete activities')
            ->name('bulk-delete');
        
        // 5. DYNAMIC ROUTES LAST
        Route::get('/{activity}', [ActivityController::class, 'show'])->name('show')
            ->where('activity', '[0-9]+');
        Route::get('/{activity}/edit', [ActivityController::class, 'edit'])
            ->middleware('custom.permission:manage activities')
            ->name('edit')
            ->where('activity', '[0-9]+');
        Route::put('/{activity}', [ActivityController::class, 'update'])
            ->middleware('custom.permission:manage activities')
            ->name('update')
            ->where('activity', '[0-9]+');
        Route::patch('/{activity}', [ActivityController::class, 'update'])
            ->middleware('custom.permission:manage activities')
            ->name('update.patch')
            ->where('activity', '[0-9]+');
        Route::delete('/{activity}', [ActivityController::class, 'destroy'])
            ->middleware('custom.permission:delete activities')
            ->name('destroy')
            ->where('activity', '[0-9]+');
        Route::post('/{activity}/toggle-status', [ActivityController::class, 'toggleStatus'])
            ->middleware('custom.permission:manage activities')
            ->name('toggle-status')
            ->where('activity', '[0-9]+');
        
        // Activity-Member relationship routes
        Route::post('/{activity}/members', [ActivityController::class, 'addMember'])
            ->middleware('custom.permission:manage activities')
            ->name('add-member')
            ->where('activity', '[0-9]+');
        Route::delete('/{activity}/members/{member}', [ActivityController::class, 'removeMember'])
            ->middleware('custom.permission:manage activities')
            ->name('remove-member')
            ->where(['activity' => '[0-9]+', 'member' => '[0-9]+']);
    });
    
    // ========================================
    // COMMUNITY GROUPS ROUTES - FIXED AND OPTIMIZED
    // ========================================
    Route::middleware(['auth', 'verified'])->group(function () {
    // Community Groups Routes - Using group names not IDs
    Route::prefix('community-groups')->name('community-groups.')->group(function () {
        // Static routes first
        Route::get('/', [CommunityGroupController::class, 'index'])->name('index');
        Route::get('/statistics', [CommunityGroupController::class, 'statistics'])->name('statistics');
        
        // Dynamic route for group names (no numeric constraint)
        Route::get('/{groupName}', [CommunityGroupController::class, 'show'])
            ->name('show')
            ->where('groupName', '[^/]+'); // Allow any characters except forward slash
    });
});
    
    // ========================================
    // REPORTS ROUTES - FIXED AND OPTIMIZED
    // ========================================
    Route::middleware('custom.permission:access reports')->prefix('reports')->name('reports.')->group(function () {
        
        // 1. STATIC ROUTES FIRST
        Route::get('/analytics', [ReportController::class, 'analytics'])->name('analytics');
        Route::get('/dashboard', [ReportController::class, 'dashboard'])->name('dashboard');
        Route::get('/enhanced-statistics', [ReportController::class, 'getEnhancedStatistics'])->name('enhanced-statistics');
        Route::get('/export', [ReportController::class, 'export'])
            ->middleware('custom.permission:export reports')
            ->name('export');
        
        // 2. SPECIFIC REPORT ROUTES
        Route::get('/members', [ReportController::class, 'membersReport'])->name('members');
        Route::get('/families', [ReportController::class, 'familiesReport'])->name('families');
        Route::get('/sacraments', [ReportController::class, 'sacramentsReport'])->name('sacraments');
        Route::get('/activities', [ReportController::class, 'activitiesReport'])->name('activities');
        Route::get('/community-groups', [ReportController::class, 'communityGroupsReport'])->name('community-groups');
        Route::get('/church-statistics', [ReportController::class, 'churchStatistics'])->name('church-statistics');
        Route::get('/group-analysis', [ReportController::class, 'groupAnalysis'])->name('group-analysis');
        Route::get('/membership-trends', [ReportController::class, 'membershipTrends'])->name('membership-trends');
        
        // 3. FINANCIAL REPORTS (with specific permission)
        Route::middleware('custom.permission:view financial reports')->group(function () {
            Route::get('/financial', [ReportController::class, 'financialReport'])->name('financial');
            Route::get('/financial/summary', [ReportController::class, 'financialSummary'])->name('financial.summary');
            Route::get('/financial/monthly', [ReportController::class, 'monthlyFinancialReport'])->name('financial.monthly');
            Route::get('/financial/yearly', [ReportController::class, 'yearlyFinancialReport'])->name('financial.yearly');
        });
        
        // 4. EXPORT ROUTES (with specific permission)
        Route::middleware('custom.permission:export reports')->group(function () {
            Route::get('/members/export', [ReportController::class, 'exportMembersReport'])->name('members.export');
            Route::get('/families/export', [ReportController::class, 'exportFamiliesReport'])->name('families.export');
            Route::get('/sacraments/export', [ReportController::class, 'exportSacramentsReport'])->name('sacraments.export');
            Route::get('/activities/export', [ReportController::class, 'exportActivitiesReport'])->name('activities.export');
            Route::get('/financial/export', [ReportController::class, 'exportFinancialReport'])->name('financial.export');
            Route::post('/custom-export', [ReportController::class, 'customExport'])->name('custom-export');
            
            // ========================================
            // MEMBER EXPORT ROUTES - MOVED HERE
            // ========================================
            Route::get('/export-members-data', [ReportController::class, 'exportMembersDataRoute'])->name('export-members-data');
            Route::get('/export-by-local-church', [ReportController::class, 'exportByLocalChurch'])->name('export-by-local-church');
            Route::get('/export-by-church-group', [ReportController::class, 'exportByChurchGroup'])->name('export-by-church-group');
            Route::get('/export-by-age-group', [ReportController::class, 'exportByAgeGroup'])->name('export-by-age-group');
            Route::get('/export-by-gender', [ReportController::class, 'exportByGender'])->name('export-by-gender');
            Route::get('/export-by-membership-status', [ReportController::class, 'exportByMembershipStatus'])->name('export-by-membership-status');
            Route::get('/export-by-marital-status', [ReportController::class, 'exportByMaritalStatus'])->name('export-by-marital-status');
            Route::get('/export-by-state', [ReportController::class, 'exportByState'])->name('export-by-state');
            Route::get('/export-by-lga', [ReportController::class, 'exportByLga'])->name('export-by-lga');
            Route::get('/export-by-education-level', [ReportController::class, 'exportByEducationLevel'])->name('export-by-education-level');
            Route::get('/export-by-occupation', [ReportController::class, 'exportByOccupation'])->name('export-by-occupation');
            Route::get('/export-by-year-joined', [ReportController::class, 'exportByYearJoined'])->name('export-by-year-joined');
        });
        
        // 5. MEMBER-SPECIFIC REPORTS
        Route::get('/members/{member}/profile', [ReportController::class, 'memberProfile'])
            ->name('member-profile')
            ->where('member', '[0-9]+');
        
        // 6. MEMBER STATISTICS BY CHURCH AND STATUS
        Route::get('/member-statistics', [ReportController::class, 'memberStatistics'])
            ->name('member-statistics');
        
        // 7. LIST ROUTE
        Route::get('/', [ReportController::class, 'index'])->name('index');
    });

    // ========================================
    // API ROUTES - ORGANIZED AND SECURE
    // ========================================
    Route::prefix('api')->name('api.')->group(function () {
        
        // Member API endpoints
        Route::middleware('custom.permission:access members')->group(function () {
            Route::get('/members/search', [MemberController::class, 'search'])->name('members.search');
            Route::get('/members/statistics', [MemberController::class, 'getStatistics'])->name('members.statistics');
            Route::get('/members/by-church/{church}', [MemberController::class, 'getByChurch'])->name('members.by-church');
            Route::get('/members/by-group/{group}', [MemberController::class, 'getByGroup'])->name('members.by-group');
            Route::get('/members/by-status/{status}', [MemberController::class, 'getByStatus'])->name('members.by-status');
        });
        
        // Family API endpoints
        Route::middleware('custom.permission:access families')->group(function () {
            Route::get('/families/search', [FamilyController::class, 'search'])->name('families.search');
            Route::get('/families/statistics', [FamilyController::class, 'getStatistics'])->name('families.statistics');
            Route::get('/families/{family}/members', function($family) {
                try {
                    return response()->json(
                        \App\Models\Family::with('members')->findOrFail($family)->members
                    );
                } catch (\Exception $e) {
                    return response()->json(['error' => 'Family not found'], 404);
                }
            })->name('families.members')->where('family', '[0-9]+');
        });
        
        // Sacrament API endpoints
        Route::middleware('custom.permission:access sacraments')->group(function () {
            Route::get('/sacraments/search', [SacramentController::class, 'search'])->name('sacraments.search');
            Route::get('/sacraments/statistics', [SacramentController::class, 'getStatistics'])->name('sacraments.statistics');
            Route::get('/members/{member}/sacraments', [SacramentController::class, 'memberSacraments'])
                ->name('members.sacraments')
                ->where('member', '[0-9]+');
                
            // Sacramental Records API endpoints
            Route::post('/baptism-records', [SacramentalRecordsController::class, 'storeBaptismRecord'])
                ->middleware('custom.permission:manage sacraments')
                ->name('baptism-records.store');
            Route::get('/baptism-records/{member}', [SacramentalRecordsController::class, 'getBaptismRecord'])
                ->name('baptism-records.get')
                ->where('member', '[0-9]+');
            Route::post('/marriage-records', [SacramentalRecordsController::class, 'storeMarriageRecord'])
                ->middleware('custom.permission:manage sacraments')
                ->name('marriage-records.store');
            Route::get('/marriage-records', [SacramentalRecordsController::class, 'getMarriageRecord'])
                ->name('marriage-records.get');
        });
        
        // Tithe API endpoints
        Route::middleware('custom.permission:access tithes')->group(function () {
            Route::get('/tithes/search', [TitheController::class, 'search'])->name('tithes.search');
            Route::get('/tithes/statistics', [TitheController::class, 'getStatistics'])->name('tithes.statistics');
            Route::get('/members/{member}/tithes', [TitheController::class, 'memberTithes'])
                ->name('members.tithes')
                ->where('member', '[0-9]+');
        });
        
        // Activity API endpoints
        Route::middleware('custom.permission:access activities')->group(function () {
            Route::get('/activities/search', [ActivityController::class, 'search'])->name('activities.search');
            Route::get('/activities/statistics', [ActivityController::class, 'getStatistics'])->name('activities.statistics');
            Route::get('/activities/recent', [ActivityController::class, 'recent'])->name('activities.recent');
            Route::get('/members/{member}/activities', [ActivityController::class, 'memberActivities'])
                ->name('members.activities')
                ->where('member', '[0-9]+');
        });
        
        // Community Groups API endpoints
        Route::middleware('custom.permission:access members')->group(function () {
            Route::get('/community-groups/statistics', [CommunityGroupController::class, 'statistics'])->name('community-groups.statistics');
            Route::get('/small-christian-communities/search', [\App\Http\Controllers\Api\SmallChristianCommunityController::class, 'search'])->name('small-christian-communities.search');
            Route::get('/small-christian-communities', [\App\Http\Controllers\Api\SmallChristianCommunityController::class, 'index'])->name('small-christian-communities.index');
            Route::get('/small-christian-communities/{community}/members', [\App\Http\Controllers\Api\SmallChristianCommunityController::class, 'members'])->name('small-christian-communities.members');
            Route::get('/families/search', [\App\Http\Controllers\Api\FamilyController::class, 'search'])->name('families.search');
            Route::get('/families/{family}/head', [\App\Http\Controllers\Api\FamilyController::class, 'getFamilyHead'])->name('families.head')->where('family', '[0-9]+');
        });
        
        // Validation endpoints (available to all authenticated users)
        Route::get('/validate/email', function() {
            $email = request('email');
            $id = request('id');
            $table = request('table', 'members'); // Default to members table
            
            try {
                $model = match($table) {
                    'families' => \App\Models\Family::class,
                    'users' => \App\Models\User::class,
                    default => \App\Models\Member::class
                };
                
                $exists = $model::where('email', $email)
                    ->when($id, function($query, $id) {
                        return $query->where('id', '!=', $id);
                    })
                    ->exists();
                    
                return response()->json(['available' => !$exists]);
            } catch (\Exception $e) {
                return response()->json(['available' => true, 'error' => $e->getMessage()]);
            }
        })->name('validate.email');
        
        Route::get('/validate/phone', function() {
            $phone = request('phone');
            $id = request('id');
            $table = request('table', 'members');
            
            try {
                $model = match($table) {
                    'families' => \App\Models\Family::class,
                    default => \App\Models\Member::class
                };
                
                $exists = $model::where('phone', $phone)
                    ->when($id, function($query, $id) {
                        return $query->where('id', '!=', $id);
                    })
                    ->exists();
                    
                return response()->json(['available' => !$exists]);
            } catch (\Exception $e) {
                return response()->json(['available' => true, 'error' => $e->getMessage()]);
            }
        })->name('validate.phone');
        
        Route::get('/validate/id-number', function() {
            $idNumber = request('id_number');
            $id = request('id');
            
            try {
                $exists = \App\Models\Member::where('id_number', $idNumber)
                    ->when($id, function($query, $id) {
                        return $query->where('id', '!=', $id);
                    })
                    ->exists();
                    
                return response()->json(['available' => !$exists]);
            } catch (\Exception $e) {
                return response()->json(['available' => true, 'error' => $e->getMessage()]);
            }
        })->name('validate.id-number');
        
        Route::get('/validate/family-code', function() {
            $familyCode = request('family_code');
            $id = request('id');
            
            try {
                $exists = \App\Models\Family::where('family_code', $familyCode)
                    ->when($id, function($query, $id) {
                        return $query->where('id', '!=', $id);
                    })
                    ->exists();
                    
                return response()->json(['available' => !$exists]);
            } catch (\Exception $e) {
                return response()->json(['available' => true, 'error' => $e->getMessage()]);
            }
        })->name('validate.family-code');
        
        // Data endpoints (available to all authenticated users)
        Route::get('/churches', function() {
            return response()->json([
                'Kangemi',
                'Pembe Tatu',
                'Cathedral',
                'Kiawara',
                'Kandara'
            ]);
        })->name('churches');
        
        Route::get('/church-groups', function() {
            return response()->json([
                ['value' => 'PMC', 'label' => 'PMC (Pontifical Missionary Childhood)'],
                ['value' => 'Youth', 'label' => 'Youth'],
                ['value' => 'Young Parents', 'label' => 'Young Parents'],
                ['value' => 'C.W.A', 'label' => 'C.W.A (Catholic Women Association)'],
                ['value' => 'CMA', 'label' => 'CMA (Catholic Men Association)'],
                ['value' => 'Choir', 'label' => 'Choir']
            ]);
        })->name('church-groups');
        
        Route::get('/sacrament-types', function() {
            return response()->json([
                'Baptism',
                'Confirmation',
                'First Communion',
                'Matrimony',
                'Holy Orders',
                'Anointing of the Sick'
            ]);
        })->name('sacrament-types');
        
        Route::get('/membership-statuses', function() {
            return response()->json([
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
                ['value' => 'transferred', 'label' => 'Transferred'],
                ['value' => 'deceased', 'label' => 'Deceased']
            ]);
        })->name('membership-statuses');
    });

    // ========================================
    // QUICK ACTIONS - OPTIMIZED AND SECURE
    // ========================================
    Route::prefix('quick')->name('quick.')->group(function () {
        
        // Member quick actions
        Route::middleware('custom.permission:access members')->group(function () {
            Route::post('/member-search', [MemberController::class, 'quickSearch'])->name('member-search');
            Route::get('/church-summary/{church}', [MemberController::class, 'getChurchSummary'])
                ->name('church-summary');
            Route::get('/group-summary/{group}', [MemberController::class, 'getGroupSummary'])
                ->name('group-summary');
        });
        
        Route::middleware('custom.permission:manage members')->group(function () {
            Route::post('/member-status-toggle', [MemberController::class, 'quickStatusToggle'])
                ->name('member-status-toggle');
        });
        
        // Tithe quick actions
        Route::middleware('custom.permission:manage tithes')->group(function () {
            Route::post('/add-tithe', [TitheController::class, 'quickAdd'])->name('add-tithe');
        });
        
        // Sacrament quick actions
        Route::middleware('custom.permission:manage sacraments')->group(function () {
            Route::post('/add-sacrament', [SacramentController::class, 'quickAdd'])->name('add-sacrament');
        });
        
        // Activity quick actions
        Route::middleware('custom.permission:access activities')->group(function () {
            Route::get('/recent-activities', [ActivityController::class, 'recent'])->name('recent-activities');
        });
    });
});

// ========================================
// ADMIN ROUTES - SECURE AND ORGANIZED
// ========================================
Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {

    // Admin Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    // User Registration and Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show')->where('user', '[0-9]+');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit')->where('user', '[0-9]+');
        Route::put('/{user}', [UserController::class, 'update'])->name('update')->where('user', '[0-9]+');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy')->where('user', '[0-9]+');
        Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('toggle-status')
            ->where('user', '[0-9]+');
    });

    // Role Management
    Route::resource('roles', RoleController::class)->parameters(['roles' => 'role']);

    // Performance Dashboard and Monitoring
    Route::prefix('performance')->name('performance.')->group(function () {
        Route::get('/', [\App\Http\Controllers\PerformanceDashboardController::class, 'index'])->name('dashboard');
        Route::post('/clear-cache', [\App\Http\Controllers\PerformanceDashboardController::class, 'clearCache'])->name('clear-cache');
    });

    // Admin Settings
    Route::get('/settings', function () {
        return Inertia::render('Admin/Settings/Index');
    })->name('settings');

    // System Maintenance Routes
    Route::prefix('system')->name('system.')->group(function () {
        Route::get('/backup', function() {
            return Inertia::render('Admin/System/Backup');
        })->name('backup');

        Route::post('/backup/create', function() {
            try {
                // Add your backup logic here
                return redirect()->back()->with('success', 'Backup created successfully');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Backup failed: ' . $e->getMessage());
            }
        })->name('backup.create');

        Route::get('/logs', function() {
            return Inertia::render('Admin/System/Logs');
        })->name('logs');

        Route::get('/cache/clear', function() {
            try {
                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');
                return redirect()->back()->with('success', 'Cache cleared successfully');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Cache clear failed: ' . $e->getMessage());
            }
        })->name('cache.clear');

        Route::post('/migrate-members', function() {
            try {
                // Add your migration logic here
                return redirect()->back()->with('success', 'Member data migration completed');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Migration failed: ' . $e->getMessage());
            }
        })->name('migrate-members');

        Route::get('/optimize', function() {
            try {
                Artisan::call('optimize');
                return redirect()->back()->with('success', 'Application optimized successfully');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Optimization failed: ' . $e->getMessage());
            }
        })->name('optimize');

        Route::get('/storage/link', function() {
            try {
                Artisan::call('storage:link');
                return redirect()->back()->with('success', 'Storage linked successfully');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Storage link failed: ' . $e->getMessage());
            }
        })->name('storage.link');
    });
});

// Enhanced Baptism Records Routes
Route::middleware(['auth', 'verified'])->prefix('baptism-records')->name('baptism-records.')->group(function () {
    Route::get('/', [App\Http\Controllers\BaptismRecordController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\BaptismRecordController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\BaptismRecordController::class, 'store'])->name('store');
    Route::get('/{baptismRecord}', [App\Http\Controllers\BaptismRecordController::class, 'show'])->name('show');
    Route::get('/{baptismRecord}/edit', [App\Http\Controllers\BaptismRecordController::class, 'edit'])->name('edit');
    Route::put('/{baptismRecord}', [App\Http\Controllers\BaptismRecordController::class, 'update'])->name('update');
    Route::delete('/{baptismRecord}', [App\Http\Controllers\BaptismRecordController::class, 'destroy'])->name('destroy');
    
    // Additional baptism record functionality
    Route::post('/filter', [App\Http\Controllers\BaptismRecordController::class, 'filter'])->name('filter');
    Route::get('/{baptismRecord}/certificate', [App\Http\Controllers\BaptismRecordController::class, 'generateCertificate'])->name('certificate');
    Route::get('/member/{memberId}/certificate', [App\Http\Controllers\BaptismRecordController::class, 'downloadBaptismCertificate'])->name('member-certificate');
    Route::get('/statistics/overview', [App\Http\Controllers\BaptismRecordController::class, 'statistics'])->name('statistics');
    Route::post('/import', [App\Http\Controllers\BaptismRecordController::class, 'import'])->name('import');
    Route::get('/export/download', [App\Http\Controllers\BaptismRecordController::class, 'export'])->name('export');
});

// Enhanced Marriage Records Routes
Route::middleware(['auth', 'verified'])->prefix('marriage-records')->name('marriage-records.')->group(function () {
    Route::get('/', [App\Http\Controllers\MarriageRecordController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\MarriageRecordController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\MarriageRecordController::class, 'store'])->name('store');
    Route::get('/{marriageRecord}', [App\Http\Controllers\MarriageRecordController::class, 'show'])->name('show');
    Route::get('/{marriageRecord}/edit', [App\Http\Controllers\MarriageRecordController::class, 'edit'])->name('edit');
    Route::put('/{marriageRecord}', [App\Http\Controllers\MarriageRecordController::class, 'update'])->name('update');
    Route::delete('/{marriageRecord}', [App\Http\Controllers\MarriageRecordController::class, 'destroy'])->name('destroy');
    
    // Additional marriage record functionality
    Route::post('/filter', [App\Http\Controllers\MarriageRecordController::class, 'filter'])->name('filter');
    Route::get('/{marriageRecord}/certificate', [App\Http\Controllers\MarriageRecordController::class, 'generateCertificate'])->name('certificate');
    Route::get('/download/{marriageRecordId}', [App\Http\Controllers\MarriageRecordController::class, 'downloadMarriageCertificate'])->name('download-certificate');
    Route::get('/member/{memberId}/certificate', [App\Http\Controllers\MarriageRecordController::class, 'findMemberMarriageCertificate'])->name('member-certificate');
    Route::get('/statistics/overview', [App\Http\Controllers\MarriageRecordController::class, 'statistics'])->name('statistics');
    Route::post('/export', [App\Http\Controllers\MarriageRecordController::class, 'export'])->name('export');
});

// Enhanced Reports Routes with Comprehensive Filtering
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/statistics', [ReportController::class, 'getEnhancedStatistics'])->name('statistics');
        Route::post('/generate', [ReportController::class, 'generateReport'])->name('generate');
        Route::get('/export', [ReportController::class, 'export'])->name('export');
        Route::get('/export/all', [ReportController::class, 'exportAll'])->name('export.all');
        Route::get('/export/members', [ReportController::class, 'exportMembers'])->name('export.members');
        Route::get('/member-statistics', [ReportController::class, 'memberStatistics'])->name('member-statistics');
        Route::get('/financial', [ReportController::class, 'financialReport'])->name('financial');
        
        // Enhanced Export Routes with Database-Driven Details
        Route::post('/export/filtered-members', [ReportController::class, 'exportFilteredMembers'])->name('export.filtered-members');
        Route::post('/export/by-category', [ReportController::class, 'exportMembersByCategory'])->name('export.by-category');
        Route::get('/export/by-local-church', [ReportController::class, 'exportByLocalChurch'])->name('export.by-local-church');
        Route::get('/export/by-church-group', [ReportController::class, 'exportByChurchGroup'])->name('export.by-church-group');
        Route::get('/export/by-age-group', [ReportController::class, 'exportByAgeGroup'])->name('export.by-age-group');
        Route::get('/export/by-gender', [ReportController::class, 'exportByGender'])->name('export.by-gender');
        Route::get('/export/by-membership-status', [ReportController::class, 'exportByMembershipStatus'])->name('export.by-membership-status');
        Route::get('/export/by-education-level', [ReportController::class, 'exportByEducationLevel'])->name('export.by-education-level');
        Route::get('/export/by-occupation', [ReportController::class, 'exportByOccupation'])->name('export.by-occupation');
        
        // Member Lists Generation Routes
        Route::get('/members/by-local-church', [ReportController::class, 'getMembersByLocalChurch'])->name('members.by-local-church');
        Route::get('/members/by-church-group', [ReportController::class, 'getMembersByChurchGroup'])->name('members.by-church-group');
        Route::get('/members/by-age-group', [ReportController::class, 'getMembersByAgeGroup'])->name('members.by-age-group');
        Route::get('/members/by-gender', [ReportController::class, 'getMembersByGender'])->name('members.by-gender');
        Route::get('/members/active', [ReportController::class, 'getActiveMembers'])->name('members.active');
        Route::get('/members/inactive', [ReportController::class, 'getInactiveMembers'])->name('members.inactive');
        Route::get('/members/transferred', [ReportController::class, 'getTransferredMembers'])->name('members.transferred');
        Route::get('/members/deceased', [ReportController::class, 'getDeceasedMembers'])->name('members.deceased');
        Route::get('/members/all-clear-records', [ReportController::class, 'getAllClearRecords'])->name('members.all-clear-records');
        Route::get('/members/filtered-list', [ReportController::class, 'getFilteredMembersList'])->name('members.filtered-list');
        Route::get('/members/directory', [ReportController::class, 'getMemberDirectory'])->name('members.directory');
    });
});

// Reports Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/statistics', [ReportController::class, 'getStatistics'])->name('statistics');
        Route::get('/enhanced-statistics', [ReportController::class, 'getEnhancedStatistics'])->name('enhanced-statistics');
        Route::get('/export', [ReportController::class, 'export'])->name('export'); // Changed from exportReport to export
        Route::get('/export/all', [ReportController::class, 'exportAll'])->name('export.all');
        Route::get('/export/members', [ReportController::class, 'exportMembers'])->name('export.members');
        Route::post('/export/filtered', [ReportController::class, 'exportFilteredMembers'])->name('export.filtered');
        Route::post('/export/category', [ReportController::class, 'exportMembersByCategory'])->name('export.category');
        
        // ========================================
        // MEMBER LISTS ROUTES
        // ========================================
        Route::get('/members/by-local-church', [ReportController::class, 'getMembersByLocalChurch'])->name('members.by-local-church');
        Route::get('/members/by-church-group', [ReportController::class, 'getMembersByChurchGroup'])->name('members.by-church-group');
        Route::get('/members/by-age-group', [ReportController::class, 'getMembersByAgeGroup'])->name('members.by-age-group');
        Route::get('/members/by-gender', [ReportController::class, 'getMembersByGender'])->name('members.by-gender');
        Route::get('/members/active', [ReportController::class, 'getActiveMembers'])->name('members.active');
        Route::get('/members/inactive', [ReportController::class, 'getInactiveMembers'])->name('members.inactive');
        Route::get('/members/transferred', [ReportController::class, 'getTransferredMembers'])->name('members.transferred');
        Route::get('/members/deceased', [ReportController::class, 'getDeceasedMembers'])->name('members.deceased');
        Route::get('/members/all-clear-records', [ReportController::class, 'getAllClearRecords'])->name('members.all-clear-records');
        Route::get('/members/filtered-list', [ReportController::class, 'getFilteredMembersList'])->name('members.filtered-list');
        Route::get('/members/directory', [ReportController::class, 'getMemberDirectory'])->name('members.directory');
    });
});

require __DIR__.'/auth.php';