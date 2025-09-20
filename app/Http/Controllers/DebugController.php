<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Member;
use App\Models\Family;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DebugController extends Controller
{
    /**
     * Show system health and diagnostics
     */
    public function systemHealth()
    {
        try {
            $health = [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'permissions' => $this->checkPermissions(),
                'roles' => $this->checkRoles(),
                'storage' => $this->checkStorage(),
                'environment' => $this->getEnvironmentInfo(),
            ];

            return response()->json($health);
        } catch (\Exception $e) {
            Log::error('System health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Health check failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            $userCount = \App\Models\User::count();
            $roleCount = \Spatie\Permission\Models\Role::count();
            
            return [
                'status' => 'healthy',
                'users' => $userCount,
                'roles' => $roleCount,
                'connection' => 'active'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkCache()
    {
        try {
            Cache::put('test_key', 'test_value', 60);
            $value = Cache::get('test_key');
            Cache::forget('test_key');
            
            return [
                'status' => $value === 'test_value' ? 'healthy' : 'error',
                'driver' => config('cache.default')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkPermissions()
    {
        try {
            $permissionCount = \Spatie\Permission\Models\Permission::count();
            $rolePermissionCount = DB::table('role_has_permissions')->count();
            
            return [
                'status' => 'healthy',
                'permissions' => $permissionCount,
                'role_permissions' => $rolePermissionCount
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkRoles()
    {
        try {
            $roles = \Spatie\Permission\Models\Role::with('permissions')->get();
            
            return [
                'status' => 'healthy',
                'count' => $roles->count(),
                'roles' => $roles->map(function ($role) {
                    return [
                        'name' => $role->name,
                        'permissions' => $role->permissions->count()
                    ];
                })
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkStorage()
    {
        try {
            $storagePath = storage_path();
            $isWritable = is_writable($storagePath);
            $diskSpace = disk_free_space($storagePath);
            
            return [
                'status' => $isWritable ? 'healthy' : 'error',
                'writable' => $isWritable,
                'free_space' => $diskSpace ? number_format($diskSpace / 1024 / 1024, 2) . ' MB' : 'unknown'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    private function getEnvironmentInfo()
    {
        return [
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ];
    }

    /**
     * Clear all application caches
     */
    public function clearCaches()
    {
        try {
            // Clear Laravel caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
            // Clear permission cache
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            
            Log::info('All caches cleared successfully');
            
            return response()->json([
                'status' => 'success',
                'message' => 'All caches cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Cache clearing failed', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Cache clearing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}