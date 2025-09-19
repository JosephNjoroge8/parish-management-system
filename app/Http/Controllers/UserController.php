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
        $query = User::with(['roles', 'createdBy'])
            ->where('id', '!=', Auth::id());

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
            'roles' => Role::all(['id', 'name']),
            'can' => [
                'create_user' => true, // Simplified for now
                'edit_user' => true,
                'delete_user' => true,
            ]
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => $this->getAssignableRoles(),
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
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

        $user = User::create([
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
        ]);

        // Assign role
        $user->assignRole($validated['role']);

        Log::info('User created', [
            'created_user_id' => $user->id,
            'created_by' => Auth::id(),
            'role_assigned' => $validated['role']
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
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
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Super admin can assign all roles
        if ($user && ($user->hasRole('super-admin') || $user->email === 'admin@parish.com')) {
            return true;
        }

        if (!$user) {
            return false;
        }

        // Get user's highest clearance level
        $userLevel = 0;
        try {
            foreach ($user->roles as $role) {
                $userLevel = max($userLevel, $this->getClearanceLevel($role->name));
            }
        } catch (\Exception $e) {
            Log::warning('Error getting user roles for clearance check', ['error' => $e->getMessage()]);
            return false;
        }

        // User can only assign roles with lower clearance level
        $targetLevel = $this->getClearanceLevel($roleName);
        return $userLevel > $targetLevel;
    }

    /**
     * Get roles that current user can assign to others
     */
    private function getAssignableRoles()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $allRoles = Role::all();
        
        // Super admin can assign all roles
        if ($user && ($user->hasRole('super-admin') || $user->email === 'admin@parish.com')) {
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

        if (!$user) {
            return collect([]);
        }

        // Filter roles based on clearance level
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
