<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserControllerOptimized extends Controller
{
    /**
     * Cache duration in minutes
     */
    private const CACHE_DURATION = 60; // 1 hour
    
    /**
     * Display a listing of users with optimized queries and caching.
     */
    public function index(Request $request): Response
    {
        try {
            // Create cache key based on filters
            $cacheKey = $this->generateCacheKey('users_index', $request->only(['search', 'role', 'status']));
            
            // Try to get from cache first
            $cachedData = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($request) {
                return $this->loadUsersData($request);
            });
            
            // Get current user permissions (cached separately)
            $userPermissions = $this->getUserPermissions();
            
            return Inertia::render('Admin/Users/Index', array_merge($cachedData, [
                'can' => $userPermissions
            ]));
            
        } catch (\Exception $e) {
            Log::error('Error loading users index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Inertia::render('Admin/Users/Index', [
                'users' => collect([]),
                'filters' => [],
                'roles' => collect([]),
                'can' => [
                    'create_user' => false,
                    'edit_user' => false,
                    'delete_user' => false,
                ],
                'error' => 'Unable to load users. Please refresh the page.'
            ]);
        }
    }
    
    /**
     * Load users data with optimized queries
     */
    private function loadUsersData(Request $request): array
    {
        // Use optimized query with minimal data transfer
        $query = User::select([
            'id', 'name', 'email', 'phone', 'is_active', 
            'created_at', 'updated_at', 'last_login_at', 'created_by'
        ])
        ->where('id', '!=', Auth::id())
        ->with([
            'roles:id,name', // Only load required role fields
            'createdBy:id,name' // Only load required creator fields
        ]);

        // Apply filters efficiently
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Optimize ordering and pagination
        $query->orderBy('created_at', 'desc');
        $users = $query->paginate(15)->withQueryString();

        // Cache roles separately for better performance
        $roles = Cache::remember('user_roles_list', self::CACHE_DURATION * 2, function () {
            return Role::select(['id', 'name'])->get();
        });

        return [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
            'roles' => $roles,
        ];
    }
    
    /**
     * Get user permissions with caching
     */
    private function getUserPermissions(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [
                'create_user' => false,
                'edit_user' => false,
                'delete_user' => false,
            ];
        }
        
        // Cache user permissions for 30 minutes
        $cacheKey = "user_permissions_{$user->id}";
        
        return Cache::remember($cacheKey, 30, function () use ($user) {
            try {
                // Use optimized direct database query
                $hasAdminRole = DB::table('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_has_roles.model_type', 'App\\Models\\User')
                    ->where('model_has_roles.model_id', $user->id)
                    ->whereIn('roles.name', ['super-admin', 'admin'])
                    ->exists();
                
                $isAuthorized = $hasAdminRole || $user->email === 'admin@parish.com';
                
                return [
                    'create_user' => $isAuthorized,
                    'edit_user' => $isAuthorized,
                    'delete_user' => $isAuthorized,
                ];
                
            } catch (\Exception $e) {
                Log::warning('Error getting user permissions', ['error' => $e->getMessage()]);
                return [
                    'create_user' => false,
                    'edit_user' => false,
                    'delete_user' => false,
                ];
            }
        });
    }
    
    /**
     * Get assignable roles with caching and optimization
     */
    private function getAssignableRoles()
    {
        $user = Auth::user();
        if (!$user) {
            Log::warning('No authenticated user found when getting assignable roles');
            return collect([]);
        }
        
        // Cache assignable roles per user
        $cacheKey = "assignable_roles_{$user->id}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($user) {
            try {
                // Get all roles efficiently
                $allRoles = Role::with('permissions:id,name')->get();
                
                // Check if user is super admin using optimized query
                $isSuperAdmin = DB::table('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_has_roles.model_type', 'App\\Models\\User')
                    ->where('model_has_roles.model_id', $user->id)
                    ->where('roles.name', 'super-admin')
                    ->exists();
                
                if ($isSuperAdmin || $user->email === 'admin@parish.com') {
                    return $allRoles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => ucwords(str_replace('-', ' ', $role->name)),
                            'clearance_level' => $this->getClearanceLevel($role->name),
                            'permissions_count' => $role->permissions ? $role->permissions->count() : 0,
                        ];
                    });
                }

                // Filter roles based on clearance level for non-super admins
                return $allRoles->filter(function ($role) {
                    return $this->canAssignRole($role->name);
                })->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => ucwords(str_replace('-', ' ', $role->name)),
                        'clearance_level' => $this->getClearanceLevel($role->name),
                        'permissions_count' => $role->permissions ? $role->permissions->count() : 0,
                    ];
                });
                
            } catch (\Exception $e) {
                Log::error('Error in getAssignableRoles method', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return collect([]);
            }
        });
    }
    
    /**
     * Check if user can assign role with caching
     */
    private function canAssignRole(string $roleName): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        
        // Cache role assignment permissions
        $cacheKey = "can_assign_role_{$user->id}_{$roleName}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($user, $roleName) {
            try {
                // Check if user is super admin using optimized query
                $isSuperAdmin = DB::table('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_has_roles.model_type', 'App\\Models\\User')
                    ->where('model_has_roles.model_id', $user->id)
                    ->where('roles.name', 'super-admin')
                    ->exists();
                
                if ($isSuperAdmin || $user->email === 'admin@parish.com') {
                    return true;
                }

                // Get user's roles efficiently
                $userRoles = DB::table('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_has_roles.model_type', 'App\\Models\\User')
                    ->where('model_has_roles.model_id', $user->id)
                    ->pluck('roles.name')
                    ->toArray();

                // Calculate user's highest clearance level
                $userLevel = 0;
                foreach ($userRoles as $roleName) {
                    $userLevel = max($userLevel, $this->getClearanceLevel($roleName));
                }

                // User can only assign roles with lower clearance level
                $targetLevel = $this->getClearanceLevel($roleName);
                return $userLevel > $targetLevel;
                
            } catch (\Exception $e) {
                Log::warning('Error checking role assignment permissions', [
                    'user_id' => $user->id,
                    'role' => $roleName,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        });
    }
    
    /**
     * Get clearance level for a role (cached)
     */
    private function getClearanceLevel(string $roleName): int
    {
        static $clearanceLevels = [
            'super-admin' => 10,
            'admin' => 8,
            'manager' => 6,
            'editor' => 4,
            'contributor' => 3,
            'user' => 2,
            'viewer' => 1,
        ];
        
        return $clearanceLevels[$roleName] ?? 0;
    }
    
    /**
     * Generate cache key with prefix
     */
    private function generateCacheKey(string $prefix, array $params = []): string
    {
        $keyParams = array_filter($params); // Remove empty values
        ksort($keyParams); // Sort for consistent keys
        
        $paramString = empty($keyParams) ? '' : '_' . md5(serialize($keyParams));
        return "parish_cache_{$prefix}{$paramString}";
    }
    
    /**
     * Clear user-related caches
     */
    private function clearUserCaches(): void
    {
        $patterns = [
            'parish_cache_users_index*',
            'user_roles_list',
            'user_permissions_*',
            'assignable_roles_*',
            'can_assign_role_*'
        ];
        
        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
    
    /**
     * Store user with cache invalidation
     */
    public function store(Request $request): RedirectResponse
    {
        // ... existing store logic ...
        
        // Clear caches after creating user
        $this->clearUserCaches();
        
        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }
    
    /**
     * Update user with cache invalidation
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        // ... existing update logic ...
        
        // Clear caches after updating user
        $this->clearUserCaches();
        
        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }
    
    /**
     * Delete user with cache invalidation
     */
    public function destroy(User $user): RedirectResponse
    {
        // ... existing destroy logic ...
        
        // Clear caches after deleting user
        $this->clearUserCaches();
        
        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}