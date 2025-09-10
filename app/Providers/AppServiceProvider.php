<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Models\Member;
use App\Observers\MemberObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register custom Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\OptimizeLogs::class,
                \App\Console\Commands\PerformanceOptimization::class,
            ]);
        }

        // Register performance optimization services
        $this->app->singleton(\App\Services\CacheOptimizationService::class);
        $this->app->singleton(\App\Services\PerformanceMonitorService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure Vite for production
        if (app()->environment('production')) {
            Vite::useHotFile(public_path('hot'))
                ->useBuildDirectory('build');
        } else {
            Vite::prefetch(concurrency: 3);
        }
        
        // Register model observers
        Member::observe(MemberObserver::class);
    }
}
