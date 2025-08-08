<?php
// filepath: app/Http/Middleware/ValidateJsonMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateJsonMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            if ($request->header('Content-Type') === 'application/json') {
                if (!$request->isJson()) {
                    return response()->json([
                        'error' => 'Invalid JSON format'
                    ], 400);
                }
            }
        }

        return $next($request);
    }
}