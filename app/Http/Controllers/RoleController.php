<?php
// filepath: app/Http/Controllers/RoleController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function __construct()
    {
        // Apply middleware via route definition instead
        // In routes file: Route::group(['middleware' => ['permission:manage roles']], function () { ... });
    }

    public function index(): Response
    {
        $roles = Role::with('permissions')
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
                        ];
                    });

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
        ]);
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
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create(['name' => $request->name]);
        $role->givePermissionTo($request->permissions);

        return redirect()->route('admin.roles.index')
                        ->with('success', 'Role created successfully.');
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
            ],
        ]);
    }

    public function edit(Role $role): Response
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode(' ', $permission->name)[1] ?? 'other';
        });

        return Inertia::render('Admin/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ],
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return redirect()->route('admin.roles.show', $role)
                        ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        // Prevent deletion of super-admin role
        if ($role->name === 'super-admin') {
            return back()->with('error', 'Super admin role cannot be deleted.');
        }

        // Check if role has users
        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete role that has assigned users.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
                        ->with('success', 'Role deleted successfully.');
    }
}