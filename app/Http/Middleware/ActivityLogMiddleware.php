<?php
// filepath: app/Http/Middleware/ActivityLogMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log only specific activities
        if ($this->shouldLog($request)) {
            $this->logActivity($request, $response);
        }

        return $response;
    }

    private function shouldLog(Request $request): bool
    {
        $sensitiveRoutes = [
            'members.store',
            'members.update',
            'members.destroy',
            'users.store',
            'users.update',
            'users.destroy',
            'roles.assign',
            'permissions.assign'
        ];

        return in_array($request->route()?->getName(), $sensitiveRoutes);
    }

    private function logActivity(Request $request, Response $response): void
    {
        $user = Auth::user();
        
        Log::info('User Activity', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status_code' => $response->getStatusCode(),
            'timestamp' => now()->toISOString()
        ]);
    }
}