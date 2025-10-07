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
        // Essential global middleware
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Use our custom CSRF middleware that disables during testing
        $middleware->web(replace: [
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class => \App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        // Route middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        // Middleware groups
        $middleware->group('admin_only', [
            'auth',
            'verified',
            'admin',
        ]);
    })
    ->withProviders([
        // Add your custom providers here
        // \App\Providers\PermissionServiceProvider::class, // Disabled for simplified admin system
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle 403 errors gracefully
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. You do not have permission to perform this action.',
                    'error' => $e->getMessage(),
                ], 403);
            }

            return redirect()->route('dashboard')->with('error', 'Access denied. You do not have permission to perform this action.');
        });

        // Handle 404 errors
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Resource not found.',
                    'error' => $e->getMessage(),
                ], 404);
            }

            return redirect()->route('dashboard')->with('error', 'The requested resource was not found.');
        });
    })->create();
