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
     * Handle an incoming request with simplified admin check
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login')
                           ->with('error', 'Please login to access this area.');
        }
        
        // Simplified admin check using is_admin database flag
        if ($user->is_admin) {
            return $next($request);
        }
        
        // Deny access
        return redirect()->route('dashboard')
                       ->with('error', 'You do not have permission to access this area. Super admin privileges required.');
    }


}