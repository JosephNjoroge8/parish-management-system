<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionServiceProvider extends ServiceProvider
{
    private $permissionsCached = false;
    private $userPermissions = [];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Only initialize if we have database connection and tables exist
        if ($this->canInitializePermissions()) {
            $this->initializeDefaultPermissions();
            $this->sharePermissionsWithSpecificViews();
        } else {
            // Provide default permissions if system not ready
            $this->shareDefaultPermissions();
        }
    }

    private function canInitializePermissions(): bool
    {
        try {
            // Check database connection
            DB::connection()->getPdo();
            
            // Check if required tables exist
            $requiredTables = ['users', 'permissions', 'roles', 'model_has_permissions', 'model_has_roles', 'role_has_permissions'];
            
            foreach ($requiredTables as $table) {
                if (!Schema::hasTable($table)) {
                    Log::info("Permission system not ready: Table '{$table}' does not exist");
                    return false;
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::info('Permission system not ready: ' . $e->getMessage());
            return false;
        }
    }

    private function initializeDefaultPermissions(): void
    {
        try {
            $this->createPermissionsIfNotExist();
            $this->createRolesIfNotExist();
            $this->assignPermissionsToRoles();
        } catch (\Exception $e) {
            Log::error('Error initializing default permissions: ' . $e->getMessage());
        }
    }

    private function sharePermissionsWithSpecificViews(): void
    {
        // Only share with specific views that need permissions, not all views
        $viewsNeedingPermissions = [
            'members.*',
            'families.*', 
            'sacraments.*',
            'tithes.*',
            'activities.*',
            'reports.*',
            'community-groups.*',
            'users.*',
            'dashboard',
            'layouts.app'
        ];

        foreach ($viewsNeedingPermissions as $viewPattern) {
            View::composer($viewPattern, function ($view) {
                if (!$this->permissionsCached) {
                    $this->userPermissions = $this->getUserPermissions();
                    $this->permissionsCached = true;
                }
                $view->with('userPermissions', $this->userPermissions);
            });
        }
    }

    private function shareDefaultPermissions(): void
    {
        // Share basic permissions when system not ready
        View::share('userPermissions', $this->getDefaultPermissions(true));
    }

    private function getUserPermissions(): array
    {
        try {
            // Return guest permissions if not authenticated
            if (!Auth::check()) {
                return $this->getDefaultPermissions(false);
            }

            $user = Auth::user();
            if (!$user) {
                return $this->getDefaultPermissions(false);
            }

            // Check if user model has Spatie permission support
            if (!$this->userSupportsPermissions($user)) {
                Log::warning('User model does not support Spatie permissions');
                return $this->getDefaultPermissions(true); // Give full access as fallback
            }

            // Try to get user permissions with fallbacks
            return $this->getUserPermissionsWithFallback($user);

        } catch (\Exception $e) {
            Log::error('Error getting user permissions: ' . $e->getMessage());
            return $this->getDefaultPermissions(true); // Default to full access on error
        }
    }

    private function userSupportsPermissions($user): bool
    {
        $requiredMethods = ['roles', 'permissions', 'can', 'hasRole', 'assignRole'];
        
        foreach ($requiredMethods as $method) {
            if (!method_exists($user, $method)) {
                return false;
            }
        }
        
        return true;
    }

    private function getUserPermissionsWithFallback($user): array
    {
        try {
            // Strategy 1: Try using Spatie's permission system
            if ($this->userHasAnyRoleOrPermission($user)) {
                return $this->buildSpatieLaravelPermissions($user);
            }

            // Strategy 2: Auto-assign super-admin role if user has no roles
            $this->ensureUserHasRole($user);
            return $this->buildSpatieLaravelPermissions($user);

        } catch (\Exception $e) {
            Log::error('Error in getUserPermissionsWithFallback: ' . $e->getMessage());
            return $this->getDefaultPermissions(true);
        }
    }

    private function userHasAnyRoleOrPermission($user): bool
    {
        try {
            // Simple database check - most reliable
            $hasRoles = DB::table('model_has_roles')
                         ->where('model_type', get_class($user))
                         ->where('model_id', $user->id)
                         ->exists();

            $hasPermissions = DB::table('model_has_permissions')
                            ->where('model_type', get_class($user))
                            ->where('model_id', $user->id)
                            ->exists();

            return $hasRoles || $hasPermissions;

        } catch (\Exception $e) {
            Log::error('Error checking user roles/permissions: ' . $e->getMessage());
            return false;
        }
    }

    private function buildSpatieLaravelPermissions($user): array
    {
        $permissions = [];
        
        $permissionList = [
            'can_manage_users' => 'manage users',
            'can_access_members' => 'access members',
            'can_manage_members' => 'manage members',
            'can_delete_members' => 'delete members',
            'can_export_members' => 'export members',
            'can_access_families' => 'access families',
            'can_manage_families' => 'manage families',
            'can_delete_families' => 'delete families',
            'can_access_sacraments' => 'access sacraments',
            'can_manage_sacraments' => 'manage sacraments',
            'can_delete_sacraments' => 'delete sacraments',
            'can_access_tithes' => 'access tithes',
            'can_manage_tithes' => 'manage tithes',
            'can_delete_tithes' => 'delete tithes',
            'can_access_activities' => 'access activities',
            'can_manage_activities' => 'manage activities',
            'can_delete_activities' => 'delete activities',
            'can_access_reports' => 'access reports',
            'can_view_financial_reports' => 'view financial reports',
            'can_export_reports' => 'export reports',
            'can_access_community_groups' => 'access community groups',
            'can_manage_community_groups' => 'manage community groups',
        ];

        foreach ($permissionList as $key => $permission) {
            try {
                $permissions[$key] = $user->can($permission);
            } catch (\Exception $e) {
                $permissions[$key] = true; // Default to true on error
            }
        }

        return $permissions;
    }

    private function ensureUserHasRole($user): void
    {
        try {
            // Check if user already has roles
            $hasRoles = DB::table('model_has_roles')
                         ->where('model_type', get_class($user))
                         ->where('model_id', $user->id)
                         ->exists();

            if (!$hasRoles) {
                // Find or create super-admin role
                $superAdminRole = Role::firstOrCreate([
                    'name' => 'super-admin',
                    'guard_name' => 'web'
                ]);

                // Assign role to user
                $user->assignRole($superAdminRole);
                
                Log::info("Auto-assigned super-admin role to user ID: {$user->id}");
            }

        } catch (\Exception $e) {
            Log::error('Error ensuring user has role: ' . $e->getMessage());
        }
    }

    private function createPermissionsIfNotExist(): void
    {
        $permissions = [
            'manage users',
            'access members', 'manage members', 'delete members', 'export members',
            'access families', 'manage families', 'delete families',
            'access sacraments', 'manage sacraments', 'delete sacraments',
            'access tithes', 'manage tithes', 'delete tithes',
            'access activities', 'manage activities', 'delete activities',
            'access community groups', 'manage community groups',
            'access reports', 'export reports', 'view financial reports',
        ];

        foreach ($permissions as $permission) {
            try {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web'
                ]);
            } catch (\Exception $e) {
                Log::error("Error creating permission '{$permission}': " . $e->getMessage());
            }
        }
    }

    private function createRolesIfNotExist(): void
    {
        $roles = [
            'super-admin' => 'Super Administrator',
            'admin' => 'Administrator', 
            'secretary' => 'Secretary',
            'treasurer' => 'Treasurer'
        ];

        foreach ($roles as $roleName => $description) {
            try {
                Role::firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => 'web'
                ]);
            } catch (\Exception $e) {
                Log::error("Error creating role '{$roleName}': " . $e->getMessage());
            }
        }
    }

    private function assignPermissionsToRoles(): void
    {
        try {
            // Super Admin gets all permissions
            $superAdmin = Role::where('name', 'super-admin')->first();
            if ($superAdmin && $superAdmin->permissions()->count() === 0) {
                $allPermissions = Permission::where('guard_name', 'web')->pluck('name')->toArray();
                $superAdmin->givePermissionTo($allPermissions);
            }

            // Admin permissions
            $admin = Role::where('name', 'admin')->first();
            if ($admin && $admin->permissions()->count() === 0) {
                $adminPermissions = [
                    'access members', 'manage members', 'export members',
                    'access families', 'manage families',
                    'access sacraments', 'manage sacraments',
                    'access activities', 'manage activities',
                    'access community groups', 'manage community groups',
                    'access reports', 'export reports',
                ];
                $admin->givePermissionTo($adminPermissions);
            }

            // Secretary permissions
            $secretary = Role::where('name', 'secretary')->first();
            if ($secretary && $secretary->permissions()->count() === 0) {
                $secretaryPermissions = [
                    'access members', 'manage members',
                    'access families', 'manage families',
                    'access sacraments', 'manage sacraments',
                    'access activities', 'manage activities',
                ];
                $secretary->givePermissionTo($secretaryPermissions);
            }

            // Treasurer permissions
            $treasurer = Role::where('name', 'treasurer')->first();
            if ($treasurer && $treasurer->permissions()->count() === 0) {
                $treasurerPermissions = [
                    'access members',
                    'access tithes', 'manage tithes',
                    'access reports', 'view financial reports',
                ];
                $treasurer->givePermissionTo($treasurerPermissions);
            }

        } catch (\Exception $e) {
            Log::error('Error assigning permissions to roles: ' . $e->getMessage());
        }
    }

    private function getDefaultPermissions(bool $authenticated): array
    {
        $permissionKeys = [
            'can_manage_users',
            'can_access_members', 'can_manage_members', 'can_delete_members', 'can_export_members',
            'can_access_families', 'can_manage_families', 'can_delete_families',
            'can_access_sacraments', 'can_manage_sacraments', 'can_delete_sacraments',
            'can_access_tithes', 'can_manage_tithes', 'can_delete_tithes',
            'can_access_activities', 'can_manage_activities', 'can_delete_activities',
            'can_access_reports', 'can_view_financial_reports', 'can_export_reports',
            'can_access_community_groups', 'can_manage_community_groups'
        ];

        // Authenticated users get all permissions by default, guests get none
        return array_fill_keys($permissionKeys, $authenticated);
    }
}
