<?php
// filepath: app/Http/Controllers/RoleController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    public function __construct()
    {
        // Apply middleware via route definition instead
        // In routes file: Route::group(['middleware' => ['permission:manage roles']], function () { ... });
    }

    /**
     * Get clearance level for a role
     */
    private function getClearanceLevel(string $roleName): int
    {
        $name = strtolower($roleName);
        if (str_contains($name, 'super')) return 5;
        if (str_contains($name, 'admin')) return 4;
        if (str_contains($name, 'manager')) return 3;
        if (str_contains($name, 'staff') || str_contains($name, 'secretary') || str_contains($name, 'treasurer')) return 2;
        return 1; // Viewer level
    }

    /**
     * Check if user can manage role based on clearance level
     */
    private function canManageRole(string $roleName): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Super admin can manage all roles
        if ($user && $user->is_admin) {
            return true;
        }

        if (!$user) {
            return false;
        }

        // Get user's highest clearance level
        $userLevel = 0;
        foreach ($user->roles as $role) {
            $userLevel = max($userLevel, $this->getClearanceLevel($role->name));
        }

        // User can only manage roles with lower clearance level
        $targetLevel = $this->getClearanceLevel($roleName);
        return $userLevel > $targetLevel;
    }

    public function index(): Response
    {
        $roles = Role::with(['permissions', 'users'])
                    ->withCount('users')
                    ->get()
                    ->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => ucwords(str_replace('-', ' ', $role->name)),
                            'permissions_count' => $role->permissions->count(),
                            'users_count' => $role->users_count,
                            'created_at' => $role->created_at->format('Y-m-d'),
                            'clearance_level' => $this->getClearanceLevel($role->name),
                            'description' => $this->getRoleDescription($role->name),
                        ];
                    });

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Get role description based on name
     */
    private function getRoleDescription(string $roleName): string
    {
        $descriptions = [
            'super-admin' => 'Full system access with all permissions',
            'admin' => 'Administrative access with most permissions',
            'manager' => 'Management level access for operations',
            'secretary' => 'Staff level access for daily operations',
            'treasurer' => 'Financial management and reporting access',
            'staff' => 'Basic staff operations access',
            'viewer' => 'Read-only access to basic information'
        ];

        return $descriptions[$roleName] ?? 'Custom role with specific permissions';
    }

    public function create(): Response
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode(' ', $permission->name)[1] ?? 'other';
        });

        return Inertia::render('Admin/Roles/Create', [
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
            'clearance_level' => 'required|integer|min:1|max:5',
        ]);

        // Check if user can create role with this clearance level
        if (!$this->canManageRole($request->name)) {
            return redirect()->back()
                           ->withErrors(['name' => 'You do not have permission to create roles at this clearance level.']);
        }

        try {
            $role = Role::create([
                'name' => $request->name,
                'guard_name' => 'web'
            ]);
            
            $role->givePermissionTo($request->permissions);

            // Log role creation
            Log::info('Role created', [
                'role_name' => $role->name,
                'permissions_count' => count($request->permissions),
                'created_by' => Auth::user()->email
            ]);

            return redirect()->route('admin.roles.index')
                           ->with('success', 'Role created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating role: ' . $e->getMessage());
            return redirect()->back()
                           ->withInput()
                           ->withErrors(['name' => 'Failed to create role. Please try again.']);
        }
    }

    public function show(Role $role): Response
    {
        $role->load('permissions', 'users');

        return Inertia::render('Admin/Roles/Show', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => ucwords(str_replace('-', ' ', $role->name)),
                'permissions' => $role->permissions->pluck('name'),
                'users' => $role->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_active' => $user->is_active,
                    ];
                }),
                'created_at' => $role->created_at->format('Y-m-d H:i'),
                'description' => $this->getRoleDescription($role->name),
                'clearance_level' => $this->getClearanceLevel($role->name),
            ],
        ]);
    }

    public function edit(Role $role): Response
    {
        // Check if user can edit this role
        if (!$this->canManageRole($role->name)) {
            abort(403, 'You do not have permission to edit this role.');
        }

        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode(' ', $permission->name)[1] ?? 'other';
        });

        return Inertia::render('Admin/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => ucwords(str_replace('-', ' ', $role->name)),
                'permissions' => $role->permissions->pluck('name'),
                'users_count' => $role->users->count(),
                'description' => $this->getRoleDescription($role->name),
                'clearance_level' => $this->getClearanceLevel($role->name),
            ],
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        // Check if user can edit this role
        if (!$this->canManageRole($role->name)) {
            abort(403, 'You do not have permission to edit this role.');
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')->ignore($role->id),
            ],
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
            'clearance_level' => 'required|integer|min:1|max:5',
        ]);

        // Don't allow changing super-admin role name
        if ($role->name === 'super-admin' && $request->name !== 'super-admin') {
            return redirect()->back()
                           ->withErrors(['name' => 'Super Admin role name cannot be changed.']);
        }

        // Check if new role name would require higher clearance
        if ($request->name !== $role->name && !$this->canManageRole($request->name)) {
            return redirect()->back()
                           ->withErrors(['name' => 'You do not have permission to create roles at this clearance level.']);
        }

        try {
            $role->update([
                'name' => $request->name,
            ]);

            // For super-admin, don't change permissions
            if ($role->name !== 'super-admin') {
                $role->syncPermissions($request->permissions);
            }

            // Log role update
            Log::info('Role updated', [
                'role_name' => $role->name,
                'permissions_count' => count($request->permissions),
                'updated_by' => Auth::user()->email
            ]);

            return redirect()->route('admin.roles.show', $role)
                           ->with('success', 'Role updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating role: ' . $e->getMessage());
            return redirect()->back()
                           ->withInput()
                           ->withErrors(['name' => 'Failed to update role. Please try again.']);
        }
    }

    public function destroy(Role $role): RedirectResponse
    {
        // Check if user can delete this role
        if (!$this->canManageRole($role->name)) {
            abort(403, 'You do not have permission to delete this role.');
        }

        // Prevent deletion of super-admin role
        if ($role->name === 'super-admin') {
            return redirect()->route('admin.roles.index')
                           ->withErrors(['error' => 'Super Admin role cannot be deleted.']);
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return redirect()->route('admin.roles.index')
                           ->withErrors(['error' => "Cannot delete role '{$role->name}' because it has assigned users."]);
        }

        try {
            // Log role deletion before deleting
            Log::info('Role deleted', [
                'role_name' => $role->name,
                'deleted_by' => Auth::user()->email
            ]);

            $roleName = $role->name;
            $role->delete();

            return redirect()->route('admin.roles.index')
                           ->with('success', "Role '{$roleName}' deleted successfully.");
        } catch (\Exception $e) {
            Log::error('Error deleting role: ' . $e->getMessage());
            return redirect()->route('admin.roles.index')
                           ->withErrors(['error' => 'Failed to delete role. Please try again.']);
        }
    }
}