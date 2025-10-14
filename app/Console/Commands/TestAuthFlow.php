<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class TestAuthFlow extends Command
{
    protected $signature = 'auth:test';
    protected $description = 'Test authentication flow and CSRF functionality';

    public function handle()
    {
        $this->info('🔐 Testing Authentication Flow...');
        $this->newLine();

        // Check auth routes
        $authRoutes = [
            'login' => 'Login page',
            'login.store' => 'Login form submission', 
            'dashboard' => 'Dashboard (authenticated)',
            'logout' => 'Logout'
        ];

        foreach ($authRoutes as $route => $description) {
            if (Route::has($route)) {
                $this->info("✅ {$description}: " . route($route));
            } else {
                $this->error("❌ Missing route: {$route}");
            }
        }

        $this->newLine();

        // Check session configuration
        $this->info('📋 Session Configuration:');
        $this->info('   Driver: ' . config('session.driver'));
        $this->info('   Lifetime: ' . config('session.lifetime') . ' minutes');
        $this->info('   Domain: ' . (config('session.domain') ?: 'localhost'));
        $this->info('   Secure: ' . (config('session.secure_cookie') ? 'Yes' : 'No'));
        $this->info('   HTTP Only: ' . (config('session.http_only') ? 'Yes' : 'No'));
        $this->info('   Same Site: ' . config('session.same_site'));

        $this->newLine();

        // Check CSRF middleware
        $middlewareGroups = config('app.middleware_groups', []);
        $webMiddleware = $middlewareGroups['web'] ?? [];
        
        $csrfFound = false;
        foreach ($webMiddleware as $middleware) {
            if (str_contains($middleware, 'VerifyCsrfToken')) {
                $csrfFound = true;
                break;
            }
        }

        if ($csrfFound) {
            $this->info('✅ CSRF Protection: Enabled');
        } else {
            $this->warn('⚠️ CSRF Protection: Not found in web middleware');
        }

        $this->newLine();
        $this->info('🔍 Recommendations:');
        $this->info('1. Login at: http://127.0.0.1:8000/login');
        $this->info('2. Use credentials: admin@parish.com / admin123');
        $this->info('3. Clear browser cache if issues persist');
        $this->info('4. Check browser console for JavaScript errors');

        return 0;
    }
}