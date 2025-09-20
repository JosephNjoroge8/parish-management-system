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
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        try {
            // Performance optimization: Use select to limit fields and eager load relationships
            $query = User::select(['id', 'name', 'email', 'phone', 'is_active', 'created_at', 'updated_at', 'last_login_at', 'created_by'])
                ->with([
                    'roles:id,name', // Only load required role fields
                    'createdBy:id,name' // Only load required creator fields
                ])
                ->where('id', '!=', Auth::id());

            // Search functionality with index optimization
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // Role filter with optimized query
            if ($request->filled('role')) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', $request->role);
                });
            }

            // Status filter
            if ($request->filled('status')) {
                $query->where('is_active', $request->status === 'active');
            }

            // Add ordering for consistent pagination
            $query->orderBy('created_at', 'desc');

            // Use efficient pagination
            $users = $query->paginate(15)->withQueryString();

            // Cache roles for better performance
            $roles = cache()->remember('user_roles_list', 300, function () {
                return Role::select(['id', 'name'])->get();
            });

            $user = Auth::user();
            
            // Safe role checking with fallback
            $canCreateUser = false;
            $canEditUser = false;
            $canDeleteUser = false;
            
            if ($user) {
                try {
                    // Use direct database query to avoid recursion
                    $userRoles = \Illuminate\Support\Facades\DB::table('model_has_roles')
                        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                        ->where('model_has_roles.model_type', 'App\\Models\\User')
                        ->where('model_has_roles.model_id', $user->id)
                        ->pluck('roles.name')
                        ->toArray();
                } catch (\Exception $e) {
                    Log::warning('Error getting user roles, using fallback', ['error' => $e->getMessage()]);
                    $userRoles = [];
                }
                $isAuthorized = in_array('super-admin', $userRoles) || in_array('admin', $userRoles) || $user->email === 'admin@parish.com';
                $canCreateUser = $isAuthorized;
                $canEditUser = $isAuthorized;
                $canDeleteUser = $isAuthorized;
            }

            $userPermissions = [
                'create_user' => $canCreateUser,
                'edit_user' => $canEditUser,
                'delete_user' => $canDeleteUser,
            ];

            return Inertia::render('Admin/Users/Index', [
                'users' => $users,
                'filters' => $request->only(['search', 'role', 'status']),
                'roles' => $roles,
                'can' => $userPermissions
            ]);
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
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        try {
            $roles = $this->getAssignableRoles();
            
            return Inertia::render('Admin/Users/Create', [
                'roles' => $roles,
                'can' => [
                    'assign_roles' => true,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading user create page', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Inertia::render('Admin/Users/Create', [
                'roles' => collect([]),
                'can' => [
                    'assign_roles' => true,
                ],
                'error' => 'Unable to load user creation form. Please try again.'
            ]);
        }
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone' => 'nullable|string|max:20',
                'password' => 'required|string|min:8|confirmed',
                'roles' => 'required|array|min:1',
                'roles.*' => 'exists:roles,id',
                'is_active' => 'boolean',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|string|in:male,female,other',
                'address' => 'nullable|string|max:500',
                'occupation' => 'nullable|string|max:255',
                'emergency_contact' => 'nullable|string|max:255',
                'emergency_phone' => 'nullable|string|max:20',
                'notes' => 'nullable|string|max:1000',
            ]);

            // Get role names from IDs for permission checking
            $roleNames = Role::whereIn('id', $validated['roles'])->pluck('name')->toArray();
            
            // Check if current user can assign these roles
            foreach ($roleNames as $roleName) {
                if (!$this->canAssignRole($roleName)) {
                    return back()->withErrors([
                        'roles' => "You do not have permission to assign the role: {$roleName}"
                    ])->withInput();
                }
            }

            // Create user with optimized data
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'is_active' => $validated['is_active'] ?? true,
                'date_of_birth' => $validated['date_of_birth'],
                'gender' => $validated['gender'],
                'address' => $validated['address'],
                'occupation' => $validated['occupation'],
                'emergency_contact' => $validated['emergency_contact'],
                'emergency_phone' => $validated['emergency_phone'],
                'notes' => $validated['notes'],
                'created_by' => Auth::id(),
            ];

            $user = User::create($userData);

            // Assign roles efficiently
            $user->assignRole($roleNames);

            Log::info('User created successfully', [
                'created_user_id' => $user->id,
                'created_by' => Auth::id(),
                'roles_assigned' => $roleNames
            ]);

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User created successfully.');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->except(['password', 'password_confirmation'])
            ]);
            
            return back()
                ->with('error', 'Failed to create user. Please try again.')
                ->withInput();
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): Response
    {
        $user->load(['roles.permissions', 'createdBy', 'createdUsers']);
        
        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'can' => [
                'edit_user' => true, // Simplified for now
                'delete_user' => true,
            ]
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        $user->load('roles');
        
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'roles' => $this->getAssignableRoles(),
            'currentRole' => $user->roles->first()?->name,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name',
            'is_active' => 'boolean',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other',
            'address' => 'nullable|string|max:500',
            'occupation' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check if current user can assign this role
        if (!$this->canAssignRole($validated['role'])) {
            return back()->withErrors([
                'role' => 'You do not have permission to assign this role.'
            ]);
        }

        // Prevent users from deactivating themselves
        if ($user->id === Auth::id() && isset($validated['is_active']) && !$validated['is_active']) {
            return back()->withErrors([
                'is_active' => 'You cannot deactivate your own account.'
            ]);
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'is_active' => $validated['is_active'] ?? $user->is_active,
            'date_of_birth' => $validated['date_of_birth'],
            'gender' => $validated['gender'],
            'address' => $validated['address'],
            'occupation' => $validated['occupation'],
            'emergency_contact' => $validated['emergency_contact'],
            'emergency_phone' => $validated['emergency_phone'],
            'notes' => $validated['notes'],
        ];

        // Update password if provided
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Update role if changed
        $currentRole = $user->roles->first()?->name;
        if ($currentRole !== $validated['role']) {
            $user->syncRoles([$validated['role']]);
            
            Log::info('User role updated', [
                'user_id' => $user->id,
                'old_role' => $currentRole,
                'new_role' => $validated['role'],
                'updated_by' => Auth::id()
            ]);
        }

        Log::info('User updated', [
            'user_id' => $user->id,
            'updated_by' => Auth::id()
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Prevent users from deleting themselves
        if ($user->id === Auth::id()) {
            return back()->withErrors([
                'delete' => 'You cannot delete your own account.'
            ]);
        }

        // Prevent deletion of super admin by email
        if ($user->email === 'admin@parish.com') {
            return back()->withErrors([
                'delete' => 'Cannot delete the main administrator account.'
            ]);
        }

        Log::info('User deleted', [
            'deleted_user_id' => $user->id,
            'deleted_by' => Auth::id()
        ]);

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Get clearance level for a role
     */
    private function getClearanceLevel(string $roleName): int
    {
        $levels = [
            'super-admin' => 5,
            'admin' => 4,
            'manager' => 3,
            'staff' => 2,
            'secretary' => 2,
            'treasurer' => 2,
            'viewer' => 1,
        ];

        return $levels[strtolower($roleName)] ?? 1;
    }

    /**
     * Check if user can assign role based on clearance level
     */
    private function canAssignRole(string $roleName): bool
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            if (!$user) {
                Log::warning('No authenticated user found when checking role assignment permissions');
                return false;
            }

            // Super admin can assign all roles
            try {
                // Use direct database query to avoid recursion
                $userRoles = \Illuminate\Support\Facades\DB::table('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_has_roles.model_type', 'App\\Models\\User')
                    ->where('model_has_roles.model_id', $user->id)
                    ->pluck('roles.name')
                    ->toArray();
            } catch (\Exception $e) {
                Log::warning('Error getting user roles in canAssignRole', ['error' => $e->getMessage()]);
                $userRoles = [];
            }
            if (in_array('super-admin', $userRoles) || $user->email === 'admin@parish.com') {
                return true;
            }

            // Get user's highest clearance level
            $userLevel = 0;
            try {
                foreach ($userRoles as $roleName) {
                    $userLevel = max($userLevel, $this->getClearanceLevel($roleName));
                }
            } catch (\Exception $e) {
                Log::warning('Error getting user roles for clearance calculation', ['error' => $e->getMessage()]);
                return false;
            }

            // User can only assign roles with lower clearance level
            $targetLevel = $this->getClearanceLevel($roleName);
            return $userLevel > $targetLevel;
            
        } catch (\Exception $e) {
            Log::warning('Error getting user roles for clearance check', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get roles that current user can assign to others
     */
    private function getAssignableRoles()
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            if (!$user) {
                Log::warning('No authenticated user found when getting assignable roles');
                return collect([]);
            }

            // Try to get all roles with error handling
            $allRoles = Role::with('permissions')->get();
            
            // Super admin can assign all roles
            try {
                // Use direct database query to avoid recursion
                $userRoles = \Illuminate\Support\Facades\DB::table('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_has_roles.model_type', 'App\\Models\\User')
                    ->where('model_has_roles.model_id', $user->id)
                    ->pluck('roles.name')
                    ->toArray();
            } catch (\Exception $e) {
                Log::warning('Error getting user roles in getAssignableRoles', ['error' => $e->getMessage()]);
                $userRoles = [];
            }
            if (in_array('super-admin', $userRoles) || $user->email === 'admin@parish.com') {
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
            
            // Return a safe fallback
            return collect([]);
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        // Prevent users from deactivating themselves
        if ($user->id === Auth::id()) {
            return back()->withErrors([
                'status' => 'You cannot change your own status.'
            ]);
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        
        Log::info("User {$status}", [
            'user_id' => $user->id,
            'status' => $user->is_active,
            'updated_by' => Auth::id()
        ]);

        return back()->with('success', "User {$status} successfully.");
    }
}
