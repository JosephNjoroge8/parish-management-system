<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class SystemHealthCheck extends Command
{
    protected $signature = 'system:health';

    protected $description = 'Comprehensive health check of the Parish Management System';

    public function handle()
    {
        $this->info('🏥 Parish Management System - Health Check');
        $this->info('='.str_repeat('=', 50));
        $this->newLine();

        $passed = 0;
        $total = 0;

        // Database connectivity
        $total++;
        try {
            DB::connection()->getPdo();
            $this->checkPassed('Database Connection');
            $passed++;
        } catch (\Exception $e) {
            $this->checkFailed('Database Connection', $e->getMessage());
        }

        // Environment configuration
        $total++;
        if (config('app.key')) {
            $this->checkPassed('Application Key');
            $passed++;
        } else {
            $this->checkFailed('Application Key', 'No application key set');
        }

        // Frontend assets
        $total++;
        if (file_exists(public_path('build/manifest.json'))) {
            $this->checkPassed('Frontend Assets Built');
            $passed++;
        } else {
            $this->checkFailed('Frontend Assets Built', 'Manifest file not found');
        }

        // Storage permissions
        $total++;
        try {
            Storage::disk('local')->put('test.txt', 'test');
            Storage::disk('local')->delete('test.txt');
            $this->checkPassed('Storage Writable');
            $passed++;
        } catch (\Exception $e) {
            $this->checkFailed('Storage Writable', $e->getMessage());
        }

        // Critical routes
        $total++;
        $routeNames = ['login', 'dashboard'];
        $routesExist = true;
        foreach ($routeNames as $routeName) {
            if (! Route::has($routeName)) {
                $routesExist = false;
                break;
            }
        }
        if ($routesExist) {
            $this->checkPassed('Critical Routes');
            $passed++;
        } else {
            $this->checkFailed('Critical Routes', 'Some routes missing');
        }

        // Models accessibility
        $total++;
        try {
            \App\Models\User::count();
            \App\Models\Family::count();
            \App\Models\Member::count();
            $this->checkPassed('Models Accessible');
            $passed++;
        } catch (\Exception $e) {
            $this->checkFailed('Models Accessible', $e->getMessage());
        }

        $this->newLine();
        $this->info("📊 Health Check Results: {$passed}/{$total} checks passed");

        if ($passed === $total) {
            $this->info('🎉 System is fully operational!');
            $this->newLine();
            $this->info('🚀 Ready to use:');
            $this->info('   🌐 Application: http://127.0.0.1:8000');
            $this->info('   📧 Admin Email: admin@parish.com');
            $this->info('   🔑 Password: admin123');
            $this->newLine();
            $this->info('📖 Available features:');
            $this->info('   • User Management & Authentication');
            $this->info('   • Family & Member Management');
            $this->info('   • Sacrament Records');
            $this->info('   • Activity & Event Management');
            $this->info('   • Community Groups');
            $this->info('   • Reports & Analytics');

            return 0;
        } else {
            $this->warn('⚠️ Some issues detected. Please review and fix.');

            return 1;
        }
    }

    private function checkPassed($test)
    {
        $this->info("✅ {$test}");
    }

    private function checkFailed($test, $reason)
    {
        $this->error("❌ {$test}: {$reason}");
    }
}
