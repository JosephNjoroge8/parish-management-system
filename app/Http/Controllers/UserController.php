<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        try {
            // Performance optimization: Use select to limit fields
            $query = User::select(['id', 'name', 'email', 'phone', 'is_active', 'is_admin', 'created_at', 'updated_at', 'last_login_at', 'created_by'])
                ->with([
                    'createdBy:id,name', // Only load required creator fields
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

            // Admin filter
            if ($request->filled('user_type')) {
                $query->where('is_admin', $request->user_type === 'admin');
            }

            // Status filter
            if ($request->filled('status')) {
                $query->where('is_active', $request->status === 'active');
            }

            // Add ordering for consistent pagination
            $query->orderBy('created_at', 'desc');

            // Use efficient pagination
            $users = $query->paginate(15)->withQueryString();

            $user = Auth::user();

            // Simple admin permission checking
            $canCreateUser = $user && $user->is_admin;
            $canEditUser = $user && $user->is_admin;
            $canDeleteUser = $user && $user->is_admin;

            $userPermissions = [
                'create_user' => $canCreateUser,
                'edit_user' => $canEditUser,
                'delete_user' => $canDeleteUser,
            ];

            return Inertia::render('Admin/Users/Index', [
                'users' => $users,
                'filters' => $request->only(['search', 'user_type', 'status']),
                'can' => $userPermissions,
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading users index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Inertia::render('Admin/Users/Index', [
                'users' => collect([]),
                'filters' => [],
                'can' => [
                    'create_user' => false,
                    'edit_user' => false,
                    'delete_user' => false,
                ],
                'error' => 'Unable to load users. Please refresh the page.',
            ]);
        }
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'can' => [
                'create_admin' => Auth::user()->is_admin,
            ],
        ]);
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
                'user_type' => 'required|string|in:admin,user',
                'is_active' => 'boolean',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|string|in:male,female,other',
                'address' => 'nullable|string|max:500',
                'occupation' => 'nullable|string|max:255',
                'emergency_contact' => 'nullable|string|max:255',
                'emergency_phone' => 'nullable|string|max:20',
                'notes' => 'nullable|string|max:1000',
            ]);

            // Simple admin check - only admins can create other admins
            if ($validated['user_type'] === 'admin' && ! Auth::user()->is_admin) {
                return back()->withErrors([
                    'user_type' => 'Only administrators can create admin users.',
                ])->withInput();
            }

            // Create user with optimized data
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'is_active' => $validated['is_active'] ?? true,
                'is_admin' => $validated['user_type'] === 'admin',
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'address' => $validated['address'] ?? null,
                'occupation' => $validated['occupation'] ?? null,
                'emergency_contact' => $validated['emergency_contact'] ?? null,
                'emergency_phone' => $validated['emergency_phone'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ];

            $user = User::create($userData);

            Log::info('User created successfully', [
                'created_user_id' => $user->id,
                'created_by' => Auth::id(),
                'user_type' => $validated['user_type'],
                'is_admin' => $user->is_admin,
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
                'input' => $request->except(['password', 'password_confirmation']),
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
        $user->load('createdBy');

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'can' => [
                'edit_user' => Auth::user()->is_admin,
                'delete_user' => Auth::user()->is_admin,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'can' => [
                'edit_admin' => Auth::user()->is_admin,
            ],
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'user_type' => 'required|string|in:admin,user',
            'is_active' => 'boolean',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other',
            'address' => 'nullable|string|max:500',
            'occupation' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Simple admin check - only admins can create other admins
        if ($validated['user_type'] === 'admin' && ! Auth::user()->is_admin) {
            return back()->withErrors([
                'user_type' => 'Only administrators can create admin users.',
            ]);
        }

        // Prevent users from deactivating themselves
        if ($user->id === Auth::id() && isset($validated['is_active']) && ! $validated['is_active']) {
            return back()->withErrors([
                'is_active' => 'You cannot deactivate your own account.',
            ]);
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'] ?? $user->is_active,
            'is_admin' => $validated['user_type'] === 'admin',
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'address' => $validated['address'] ?? null,
            'occupation' => $validated['occupation'] ?? null,
            'emergency_contact' => $validated['emergency_contact'] ?? null,
            'emergency_phone' => $validated['emergency_phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];

        // Update password if provided
        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        Log::info('User updated', [
            'user_id' => $user->id,
            'updated_by' => Auth::id(),
            'user_type' => $validated['user_type'],
            'is_admin' => $user->is_admin,
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
                'delete' => 'You cannot delete your own account.',
            ]);
        }

        // Prevent deletion of super admin by email
        if ($user->email === 'admin@parish.com') {
            return back()->withErrors([
                'delete' => 'Cannot delete the main administrator account.',
            ]);
        }

        Log::info('User deleted', [
            'deleted_user_id' => $user->id,
            'deleted_by' => Auth::id(),
        ]);

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        // Prevent users from deactivating themselves
        if ($user->id === Auth::id()) {
            return back()->withErrors([
                'status' => 'You cannot change your own status.',
            ]);
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        Log::info("User {$status}", [
            'user_id' => $user->id,
            'status' => $user->is_active,
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', "User {$status} successfully.");
    }
}
