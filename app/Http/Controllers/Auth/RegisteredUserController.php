<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct()
    {
        // Middleware should be applied in routes, not here
    }

    /**
     * Static helper method to check if user is super admin (for use outside middleware)
     */
    public static function userIsSuperAdmin($user): bool
    {
        // Simplified: check is_admin flag
        return $user->is_admin;
    }

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        // Simple admin/user system - no complex roles needed
        $userTypes = collect([
            [
                'id' => 1,
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full system access',
            ],
            [
                'id' => 2,
                'name' => 'user',
                'display_name' => 'Regular User',
                'description' => 'Standard access',
            ],
        ]);

        return Inertia::render('Auth/Register', [
            'userTypes' => $userTypes,
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        // Simplified validation rules
        $request->validate([
            'name' => 'required|string|max:255|min:2',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'user_type' => 'required|string|in:admin,user',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female',
            'address' => 'nullable|string|max:500',
            'occupation' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        try {
            // Create user with transaction for data integrity
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => strtolower(trim($request->email)),
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
                'is_admin' => $request->user_type === 'admin',
                'email_verified_at' => now(),
                'created_by' => Auth::id(),
            ]);

            // Simple admin flag assignment - no external role system needed

            // Fire the registered event
            event(new Registered($user));

            DB::commit();

            // Log the action
            Log::info('New user created', [
                'created_user_id' => $user->id,
                'created_user_email' => $user->email,
                'created_by' => Auth::id(),
                'user_type' => $request->user_type,
                'is_admin' => $user->is_admin,
            ]);

            $userTypeDisplay = $user->is_admin ? 'Administrator' : 'Regular User';

            return redirect()->route('admin.users.index')
                ->with('success', "User '{$user->name}' created successfully as {$userTypeDisplay}.");

        } catch (\Exception $e) {
            DB::rollback();

            // Log the error
            Log::error('Failed to create user', [
                'error' => $e->getMessage(),
                'email' => $request->email,
                'created_by' => Auth::id(),
            ]);

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('error', 'Failed to create user. Please try again.');
        }
    }

    /**
     * Show the specified user (optional method for viewing created user)
     */
    public function show(User $user): Response
    {
        // Ensure user can only view users they created or if they're super admin
        if (! self::userIsSuperAdmin(Auth::user()) && $user->created_by !== Auth::id()) {
            abort(403, 'You can only view users you created.');
        }

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_active' => $user->is_active,
            'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i'),
            'created_at' => $user->created_at->format('Y-m-d H:i'),
            'created_by' => $user->createdBy?->name ?? 'System',
        ];

        // Try to get roles
        try {
            if (method_exists($user, 'roles') && $user->roles) {
                $userData['roles'] = $user->roles->pluck('name');
            } else {
                $userData['roles'] = ['viewer'];
            }
        } catch (\Exception $e) {
            $userData['roles'] = ['viewer'];
        }

        return Inertia::render('Admin/Users/Show', [
            'user' => $userData,
        ]);
    }
}
