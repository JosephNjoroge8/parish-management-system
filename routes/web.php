<?php
// routes/web.php
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\SacramentController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CommunityGroupController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Members Routes
    Route::prefix('members')->name('members.')->group(function () {
        Route::get('/', [MemberController::class, 'index'])->name('index');
        Route::get('/create', [MemberController::class, 'create'])->name('create');
        Route::post('/', [MemberController::class, 'store'])->name('store');
        Route::get('/{member}', [MemberController::class, 'show'])->name('show');
        Route::get('/{member}/edit', [MemberController::class, 'edit'])->name('edit');
        Route::put('/{member}', [MemberController::class, 'update'])->name('update');
        Route::delete('/{member}', [MemberController::class, 'destroy'])->name('destroy');
        Route::post('/{member}/toggle-status', [MemberController::class, 'toggleStatus'])->name('toggle-status');
    });
    
    // Families Routes
    Route::prefix('families')->name('families.')->group(function () {
        Route::get('/', [FamilyController::class, 'index'])->name('index');
        Route::get('/create', [FamilyController::class, 'create'])->name('create');
        Route::post('/', [FamilyController::class, 'store'])->name('store');
        Route::get('/{family}', [FamilyController::class, 'show'])->name('show');
        Route::get('/{family}/edit', [FamilyController::class, 'edit'])->name('edit');
        Route::put('/{family}', [FamilyController::class, 'update'])->name('update');
        Route::delete('/{family}', [FamilyController::class, 'destroy'])->name('destroy');
        Route::post('/{family}/toggle-status', [FamilyController::class, 'toggleStatus'])->name('toggle-status');
    });
    
    // Sacraments Routes
    Route::prefix('sacraments')->name('sacraments.')->group(function () {
        Route::get('/', [SacramentController::class, 'index'])->name('index');
        Route::get('/create', [SacramentController::class, 'create'])->name('create');
        Route::post('/', [SacramentController::class, 'store'])->name('store');
        Route::get('/{sacrament}', [SacramentController::class, 'show'])->name('show');
        Route::get('/{sacrament}/edit', [SacramentController::class, 'edit'])->name('edit');
        Route::put('/{sacrament}', [SacramentController::class, 'update'])->name('update');
        Route::delete('/{sacrament}', [SacramentController::class, 'destroy'])->name('destroy');
    });
    
    // Activities Routes
    Route::prefix('activities')->name('activities.')->group(function () {
        Route::get('/', [ActivityController::class, 'index'])->name('index');
        Route::get('/create', [ActivityController::class, 'create'])->name('create');
        Route::post('/', [ActivityController::class, 'store'])->name('store');
        Route::get('/{activity}', [ActivityController::class, 'show'])->name('show');
        Route::get('/{activity}/edit', [ActivityController::class, 'edit'])->name('edit');
        Route::put('/{activity}', [ActivityController::class, 'update'])->name('update');
        Route::delete('/{activity}', [ActivityController::class, 'destroy'])->name('destroy');
        Route::post('/{activity}/toggle-status', [ActivityController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{activity}/members', [ActivityController::class, 'addMember'])->name('add-member');
        Route::delete('/{activity}/members/{member}', [ActivityController::class, 'removeMember'])->name('remove-member');
    });
    
    // Community Groups Routes
    Route::prefix('community-groups')->name('community-groups.')->group(function () {
        Route::get('/', [CommunityGroupController::class, 'index'])->name('index');
        Route::get('/create', [CommunityGroupController::class, 'create'])->name('create');
        Route::post('/', [CommunityGroupController::class, 'store'])->name('store');
        Route::get('/{communityGroup}', [CommunityGroupController::class, 'show'])->name('show');
        Route::get('/{communityGroup}/edit', [CommunityGroupController::class, 'edit'])->name('edit');
        Route::put('/{communityGroup}', [CommunityGroupController::class, 'update'])->name('update');
        Route::delete('/{communityGroup}', [CommunityGroupController::class, 'destroy'])->name('destroy');
        Route::post('/{communityGroup}/toggle-status', [CommunityGroupController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{communityGroup}/members', [CommunityGroupController::class, 'addMember'])->name('add-member');
        Route::delete('/{communityGroup}/members/{member}', [CommunityGroupController::class, 'removeMember'])->name('remove-member');
    });
    
    // Reports Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/members', [ReportController::class, 'members'])->name('members');
        Route::get('/families', [ReportController::class, 'families'])->name('families');
        Route::get('/sacraments', [ReportController::class, 'sacraments'])->name('sacraments');
        Route::get('/activities', [ReportController::class, 'activities'])->name('activities');
        Route::get('/community-groups', [ReportController::class, 'communityGroups'])->name('community-groups');
        Route::post('/export', [ReportController::class, 'export'])->name('export');
    });
});

require __DIR__.'/auth.php';