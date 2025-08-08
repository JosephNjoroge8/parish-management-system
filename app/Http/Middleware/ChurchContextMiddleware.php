<?php
// filepath: app/Http/Middleware/ChurchContextMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasPermissions;

class ChurchContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        // Add church context to request
        $request->merge([
            'user_church' => $user->local_church ?? null,
            'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'user_roles' => $user->roles->pluck('name')->toArray()
        ]);

        return $next($request);
    }
}