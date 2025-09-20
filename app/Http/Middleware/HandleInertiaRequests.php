<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $this->getUserRoles($user),
                    'permissions' => $this->getUserPermissions($user),
                    'is_super_admin' => $this->userHasRole($user, 'super-admin'),
                    'is_admin' => $this->userHasAnyRole($user, ['super-admin', 'admin']),
                    'last_login_at' => $user->last_login_at?->format('Y-m-d H:i:s'),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'app' => [
                'name' => config('app.name', 'Parish Management System'),
                'url' => config('app.url'),
                'timezone' => config('app.timezone'),
            ],
        ];
    }

    private function getUserRoles($user): array
    {
        try {
            // Use direct database query to avoid recursion
            $roles = \Illuminate\Support\Facades\DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->where('model_has_roles.model_id', $user->id)
                ->pluck('roles.name')
                ->toArray();
            
            return $roles;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Direct role query failed in HandleInertiaRequests', ['error' => $e->getMessage()]);
            return $user->email === 'admin@parish.com' ? ['super-admin'] : [];
        }
    }

    private function userHasRole($user, $role): bool
    {
        try {
            // Use direct database query to avoid recursion
            return \Illuminate\Support\Facades\DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->where('model_has_roles.model_id', $user->id)
                ->where('roles.name', $role)
                ->exists();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Direct role check failed in HandleInertiaRequests', ['error' => $e->getMessage()]);
            return $user->email === 'admin@parish.com' && $role === 'super-admin';
        }
    }

    private function userHasAnyRole($user, $roles): bool
    {
        try {
            // Use direct database query to avoid recursion and Array to string conversion
            $checkRoles = is_array($roles) ? $roles : [$roles];
            
            return \Illuminate\Support\Facades\DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->where('model_has_roles.model_id', $user->id)
                ->whereIn('roles.name', $checkRoles)
                ->exists();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Direct role check failed in userHasAnyRole', ['error' => $e->getMessage()]);
            
            // Fallback: Check by email for super admin
            if ($user->email === 'admin@parish.com') {
                $checkRoles = is_array($roles) ? $roles : [$roles];
                return in_array('super-admin', $checkRoles) || in_array('admin', $checkRoles);
            }
            
            return false;
        }
    }

    private function getUserPermissions($user): array
    {
        $isSuperAdmin = $user->email === 'admin@parish.com';
        
        try {
            return [
                'can_manage_users' => $this->userHasRole($user, 'super-admin'),
                'can_manage_roles' => $this->userHasRole($user, 'super-admin'),
                'can_access_members' => $user->hasPermissionTo('access members'),
                'can_manage_members' => $user->hasPermissionTo('manage members'),
                'can_create_members' => $user->hasPermissionTo('create members'),
                'can_edit_members' => $user->hasPermissionTo('edit members'),
                'can_delete_members' => $user->hasPermissionTo('delete members'),
                'can_export_members' => $user->hasPermissionTo('export members'),
                'can_access_families' => $user->hasPermissionTo('access families'),
                'can_manage_families' => $user->hasPermissionTo('manage families'),
                'can_access_sacraments' => $user->hasPermissionTo('access sacraments'),
                'can_manage_sacraments' => $user->hasPermissionTo('manage sacraments'),
                'can_access_tithes' => $user->hasPermissionTo('access tithes'),
                'can_manage_tithes' => $user->hasPermissionTo('manage tithes'),
                'can_view_financial_reports' => $user->hasPermissionTo('view financial reports'),
                'can_access_community_groups' => $user->hasPermissionTo('access community groups'),
                'can_manage_community_groups' => $user->hasPermissionTo('manage community groups'),
                'can_access_reports' => $user->hasPermissionTo('access reports'),
                'can_access_dashboard' => $user->hasPermissionTo('access dashboard'),
            ];
        } catch (\Exception $e) {
            // Fallback permissions
            return [
                'can_manage_users' => $isSuperAdmin,
                'can_manage_roles' => $isSuperAdmin,
                'can_access_members' => true,
                'can_manage_members' => true,
                'can_create_members' => true,
                'can_edit_members' => true,
                'can_delete_members' => $isSuperAdmin,
                'can_export_members' => true,
                'can_access_families' => true,
                'can_manage_families' => true,
                'can_access_sacraments' => true,
                'can_manage_sacraments' => true,
                'can_access_tithes' => true,
                'can_manage_tithes' => true,
                'can_view_financial_reports' => true,
                'can_access_community_groups' => true,
                'can_manage_community_groups' => true,
                'can_access_reports' => true,
                'can_access_dashboard' => true,
            ];
        }
    }
}
