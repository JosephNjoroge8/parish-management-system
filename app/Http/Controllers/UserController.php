<?php
// filepath: app/Http/Controllers/UserController.php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\Controller;
use Illuminate\Routing\Controller as BaseController;
class UserController extends BaseController
{
    public function __construct()
    {
        $this->middleware('permission:manage users')->except(['index', 'show']);
        $this->middleware('permission:view users')->only(['index', 'show']);
    }

    public function index(Request $request): Response
    {
        $query = User::with(['roles', 'createdBy'])
                    ->when($request->search, function ($query, $search) {
                        $query->where('name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->when($request->role, function ($query, $role) {
                        $query->whereHas('roles', function ($q) use ($role) {
                            $q->where('name', $role);
                        });
                    })
                    ->when($request->status, function ($query, $status) {
                        if ($status === 'active') {
                            $query->where('is_active', true);
                        } elseif ($status === 'inactive') {
                            $query->where('is_active', false);
                        }
                    });

        $users = $query->paginate(15)->withQueryString();

        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'roles' => $user->roles->pluck('name'),
                'created_at' => $user->created_at->format('Y-m-d H:i'),
                'last_login_at' => $user->last_login_at?->format('Y-m-d H:i'),
                'created_by' => $user->createdBy?->name,
            ];
        });

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => Role::all()->pluck('name'),
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function show(User $user): Response
    {
        $user->load(['roles.permissions', 'createdBy', 'createdUsers']);

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
                'gender' => $user->gender,
                'address' => $user->address,
                'occupation' => $user->occupation,
                'emergency_contact' => $user->emergency_contact,
                'emergency_phone' => $user->emergency_phone,
                'notes' => $user->notes,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i'),
                'last_login_at' => $user->last_login_at?->format('Y-m-d H:i'),
                'created_at' => $user->created_at->format('Y-m-d H:i'),
                'created_by' => $user->createdBy?->name,
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'name' => $role->name,
                        'display_name' => ucwords(str_replace('-', ' ', $role->name)),
                        'permissions' => $role->permissions->pluck('name'),
                    ];
                }),
                'created_users_count' => $user->createdUsers->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        $roles = Role::all()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => ucwords(str_replace('-', ' ', $role->name)),
                'permissions_count' => $role->permissions->count(),
            ];
        });

        return Inertia::render('Admin/Users/Create', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|exists:roles,name',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female',
            'address' => 'nullable|string|max:500',
            'occupation' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'address' => $request->address,
            'occupation' => $request->occupation,
            'emergency_contact' => $request->emergency_contact,
            'emergency_phone' => $request->emergency_phone,
            'notes' => $request->notes,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::id(),
        ]);

        // Assign role
        $user->assignRole($request->role);

        return redirect()->route('admin.users.index')
                        ->with('success', 'User created successfully.');
    }

    public function edit(User $user): Response
    {
        $roles = Role::all()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => ucwords(str_replace('-', ' ', $role->name)),
            ];
        });

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
                'gender' => $user->gender,
                'address' => $user->address,
                'occupation' => $user->occupation,
                'emergency_contact' => $user->emergency_contact,
                'emergency_phone' => $user->emergency_phone,
                'notes' => $user->notes,
                'is_active' => $user->is_active,
                'roles' => $user->roles->pluck('name'),
            ],
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'role' => 'required|exists:roles,name',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female',
            'address' => 'nullable|string|max:500',
            'occupation' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $updateData = $request->only([
            'name', 'email', 'phone', 'date_of_birth', 'gender',
            'address', 'occupation', 'emergency_contact', 'emergency_phone', 'notes'
        ]);

        $updateData['is_active'] = $request->boolean('is_active');

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Update role
        $user->syncRoles([$request->role]);

        return redirect()->route('admin.users.show', $user)
                        ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        // Prevent deletion of super admin
        if ($user->hasRole('super-admin')) {
            return back()->with('error', 'Super admin cannot be deleted.');
        }

        // Prevent self-deletion
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Check if user has created other users
        if ($user->createdUsers()->exists()) {
            return back()->with('error', 'Cannot delete user who has created other users. Please reassign or delete those users first.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
                        ->with('success', 'User deleted successfully.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        // Prevent deactivating super admin
        if ($user->hasRole('super-admin')) {
            return back()->with('error', 'Super admin cannot be deactivated.');
        }

        // Prevent self-deactivation
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User {$status} successfully.");
    }
}