<?php

namespace App\Providers;

use App\Console\Commands\OptimizeLogs;
use App\Console\Commands\PerformanceOptimization;
use App\Models\Member;
use App\Observers\MemberObserver;
use App\Services\CacheOptimizationService;
use App\Services\PerformanceMonitorService;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
                OptimizeLogs::class,
                PerformanceOptimization::class,
            ]);
        }

        // Register performance optimization services
        $this->app->singleton(CacheOptimizationService::class);
        $this->app->singleton(PerformanceMonitorService::class);
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
