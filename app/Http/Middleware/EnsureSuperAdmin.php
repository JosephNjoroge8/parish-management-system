<?php
// filepath: app/Http/Middleware/EnsureSuperAdmin.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request with comprehensive fallback logic
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login')
                           ->with('error', 'Please login to access this area.');
        }
        
        // Check if user is super admin using multiple fallback methods
        if ($this->isSuperAdmin($user)) {
            return $next($request);
        }
        
        // Deny access
        return redirect()->route('dashboard')
                       ->with('error', 'You do not have permission to access this area. Super admin privileges required.');
    }

    /**
     * Comprehensive super admin check with multiple fallback methods
     */
    private function isSuperAdmin($user): bool
    {
        // Method 1: Check by email (most reliable fallback)
        if ($this->isSuperAdminByEmail($user)) {
            return true;
        }

        // Method 2: Check using Spatie roles (if available)
        if ($this->isSuperAdminByRole($user)) {
            return true;
        }

        // Method 3: Check using custom role column (if exists)
        if ($this->isSuperAdminByCustomRole($user)) {
            return true;
        }

        // Method 4: Check if user is the first user created (fallback)
        if ($this->isFirstUser($user)) {
            return true;
        }

        return false;
    }

    /**
     * Check by email address
     */
    private function isSuperAdminByEmail($user): bool
    {
        $superAdminEmails = [
            'admin@parish.com',
            'superadmin@parish.com',
            'administrator@parish.com',
            // Add more admin emails as needed
        ];

        return in_array(strtolower($user->email), $superAdminEmails);
    }

    /**
     * Check using Spatie Permission package
     */
    private function isSuperAdminByRole($user): bool
    {
        try {
            // Check if Spatie package is available
            if (!class_exists('\Spatie\Permission\Models\Role')) {
                return false;
            }

            // Check if user has the HasRoles trait
            if (!method_exists($user, 'hasRole')) {
                return false;
            }

            // Check if roles table exists
            if (!Schema::hasTable('roles')) {
                return false;
            }

            // Check if super-admin role exists
            $roleExists = DB::table('roles')
                           ->where('name', 'super-admin')
                           ->exists();

            if (!$roleExists) {
                return false;
            }

            // Finally, check if user has the role
            return $user->hasRole('super-admin');

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::warning('Spatie role check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check using custom role column (fallback)
     */
    private function isSuperAdminByCustomRole($user): bool
    {
        try {
            // Check if users table has a 'role' column
            if (!Schema::hasColumn('users', 'role')) {
                return false;
            }

            return in_array(strtolower($user->role ?? ''), [
                'super-admin',
                'superadmin',
                'admin',
                'administrator'
            ]);

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if user is the first user (emergency fallback)
     */
    private function isFirstUser($user): bool
    {
        try {
            $firstUserId = DB::table('users')
                            ->orderBy('id')
                            ->value('id');

            return $user->id == $firstUserId;

        } catch (\Exception $e) {
            return false;
        }
    }
}