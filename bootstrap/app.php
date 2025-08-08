<?php
// filepath: c:\Users\Joseph Njoroge\parish-system\bootstrap\app.php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);

        // API middleware
        $middleware->api(append: [
            \App\Http\Middleware\ApiRateLimitMiddleware::class,
            \App\Http\Middleware\ValidateJsonMiddleware::class,
        ]);

        // Global middleware for all requests
        $middleware->append([
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);

        // Route middleware aliases
        $middleware->alias([
            // Existing middleware
            'check.user.status' => \App\Http\Middleware\CheckUserStatus::class,
            
            // Spatie Permission middleware (existing)
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            
            // Custom Parish System middleware
            'custom.permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'custom.role' => \App\Http\Middleware\RoleMiddleware::class,
            'church.context' => \App\Http\Middleware\ChurchContextMiddleware::class,
            'activity.log' => \App\Http\Middleware\ActivityLogMiddleware::class,
            'api.rate' => \App\Http\Middleware\ApiRateLimitMiddleware::class,
            'validate.json' => \App\Http\Middleware\ValidateJsonMiddleware::class,
            'security.headers' => \App\Http\Middleware\SecurityHeadersMiddleware::class,
            
        ]);

        // Middleware groups for specific route patterns
        $middleware->group('parish', [
            'auth',
            'verified',
            'church.context',
            'activity.log',
        ]);

        // Admin middleware group
        $middleware->group('admin', [
            'auth',
            'verified',
            'custom.role:super-admin',
            'activity.log',
        ]);

        // Member management middleware group
        $middleware->group('member.management', [
            'auth',
            'verified',
            'church.context',
            'activity.log',
        ]);
    })
    ->withProviders([
        // Add your custom providers here
        \App\Providers\PermissionServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle 403 errors gracefully
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. You do not have permission to perform this action.',
                    'error' => $e->getMessage()
                ], 403);
            }
            
            return redirect()->route('dashboard')->with('error', 'Access denied. You do not have permission to perform this action.');
        });
        
        // Handle 404 errors
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Resource not found.',
                    'error' => $e->getMessage()
                ], 404);
            }
            
            return redirect()->route('dashboard')->with('error', 'The requested resource was not found.');
        });
    })->create();
