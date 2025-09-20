<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DatabaseOptimizationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Start query logging if in debug mode
        if (config('app.debug')) {
            DB::enableQueryLog();
        }

        $response = $next($request);

        // Log slow queries in debug mode
        if (config('app.debug')) {
            $queries = DB::getQueryLog();
            
            foreach ($queries as $query) {
                $time = $query['time'];
                
                // Log queries that take longer than 100ms
                if ($time > 100) {
                    Log::warning('Slow database query detected', [
                        'query' => $query['query'],
                        'bindings' => $query['bindings'],
                        'time' => $time . 'ms',
                        'url' => $request->url()
                    ]);
                }
            }
            
            // Log if too many queries
            $queryCount = count($queries);
            if ($queryCount > 20) {
                Log::warning('High number of database queries', [
                    'count' => $queryCount,
                    'url' => $request->url()
                ]);
            }
        }

        return $response;
    }
}