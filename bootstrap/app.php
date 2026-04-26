<?php

// filepath: c:\Users\Joseph Njoroge\parish-system\bootstrap\app.php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ProductionSecurityMiddleware;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/auth.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(prepend: [
            ProductionSecurityMiddleware::class,
        ]);

        // Essential global middleware
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Use our custom CSRF middleware that disables during testing
        $middleware->web(replace: [
            Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class => VerifyCsrfToken::class,
        ]);

        // Route middleware aliases
        $middleware->alias([
            'admin' => AdminMiddleware::class,
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
        $exceptions->render(function (AccessDeniedHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. You do not have permission to perform this action.',
                    'error' => $e->getMessage(),
                ], 403);
            }

            return redirect()->route('dashboard')->with('error', 'Access denied. You do not have permission to perform this action.');
        });

        // Handle 404 errors
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->is('build/*') || $request->is('storage/*') || $request->is('favicon.ico')) {
                return response('Not Found', 404);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Resource not found.',
                    'error' => $e->getMessage(),
                ], 404);
            }

            return redirect()->route('dashboard')->with('error', 'The requested resource was not found.');
        });
    })->create();
