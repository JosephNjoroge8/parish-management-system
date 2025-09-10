<?php

namespace App\Console\Commands;

use App\Services\PerformanceMonitorService;
use App\Services\CacheOptimizationService;
use Illuminate\Console\Command;

class PerformanceOptimization extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'parish:optimize 
                           {--analyze : Analyze current performance}
                           {--cache : Optimize caching}
                           {--logs : Optimize log files}
                           {--all : Run all optimizations}';

    /**
     * The console command description.
     */
    protected $description = 'Comprehensive performance optimization for the parish system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Parish System Performance Optimization');
        $this->newLine();

        if ($this->option('analyze') || $this->option('all')) {
            $this->analyzePerformance();
        }

        if ($this->option('cache') || $this->option('all')) {
            $this->optimizeCache();
        }

        if ($this->option('logs') || $this->option('all')) {
            $this->optimizeLogs();
        }

        if ($this->option('all')) {
            $this->runAllOptimizations();
        }

        $this->newLine();
        $this->info('✅ Performance optimization completed!');

        return 0;
    }

    /**
     * Analyze current performance
     */
    private function analyzePerformance(): void
    {
        $this->info('📊 Analyzing performance...');

        PerformanceMonitorService::startMonitoring();
        
        // Simulate some queries to gather metrics
        \App\Models\Member::count();
        \App\Models\Family::count();
        
        $report = PerformanceMonitorService::generateReport();
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Queries', $report['metrics']['queries']['total_count']],
                ['Query Time', $report['metrics']['queries']['total_time'] . 'ms'],
                ['Memory Usage', $report['metrics']['memory']['current_usage']],
                ['Peak Memory', $report['metrics']['memory']['peak_usage']],
                ['Slow Queries', $report['metrics']['queries']['slow_queries']],
            ]
        );

        if (!empty($report['recommendations'])) {
            $this->warn('⚠️  Performance Recommendations:');
            foreach ($report['recommendations'] as $rec) {
                $this->line("• {$rec['message']} ({$rec['time']}ms)");
            }
        }

        if (!empty($report['database_optimizations'])) {
            $this->warn('💾 Database Optimization Suggestions:');
            foreach ($report['database_optimizations'] as $opt) {
                $this->line("• {$opt['suggestion']}");
            }
        }
    }

    /**
     * Optimize caching
     */
    private function optimizeCache(): void
    {
        $this->info('🗄️  Optimizing cache...');

        // Clear old cache
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');

        // Warm up cache
        CacheOptimizationService::warmupCache();

        $this->info('✅ Cache optimization completed');
    }

    /**
     * Optimize logs
     */
    private function optimizeLogs(): void
    {
        $this->info('📝 Optimizing logs...');

        $this->call('logs:optimize', [
            '--days' => 7,
            '--size' => 5,
            '--archive' => true
        ]);

        $this->info('✅ Log optimization completed');
    }

    /**
     * Run all optimizations
     */
    private function runAllOptimizations(): void
    {
        $this->info('🔧 Running additional optimizations...');

        // Laravel optimizations
        $this->call('optimize');
        $this->call('config:cache');
        $this->call('route:cache');
        $this->call('view:cache');

        // Database optimizations
        $this->call('db:show', ['--counts' => true]);

        $this->info('✅ All optimizations completed');
    }
}
