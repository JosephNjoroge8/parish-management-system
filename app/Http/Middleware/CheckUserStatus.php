<?php
// filepath: app/Http/Middleware/CheckUserStatus.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if user has a status field and if it's active
            if (isset($user->status) && $user->status !== 'active') {
                Auth::logout();
                return redirect('/login')->with('error', 'Your account is not active.');
            }
        }
        
        return $next($request);
    }
}