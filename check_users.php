<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

echo "=== USER STATUS REPORT ===\n";

try {
    // Check total users
    $totalUsers = \App\Models\User::count();
    $activeUsers = \App\Models\User::where('is_active', true)->count();
    $inactiveUsers = \App\Models\User::where('is_active', false)->count();
    $adminUsers = \App\Models\User::where('is_admin', true)->count();

    echo "📊 SUMMARY:\n";
    echo "   Total Users: $totalUsers\n";
    echo "   Active Users: $activeUsers\n";
    echo "   Inactive Users: $inactiveUsers\n";
    echo "   Admin Users: $adminUsers\n\n";

    if ($totalUsers > 0) {
        echo "👤 USER DETAILS:\n";
        $users = \App\Models\User::select('id', 'name', 'email', 'is_active', 'is_admin', 'last_login_at', 'created_at')
            ->orderBy('is_active', 'desc')
            ->orderBy('is_admin', 'desc')
            ->get();

        foreach ($users as $user) {
            $status = $user->is_active ? '🟢 ACTIVE' : '🔴 INACTIVE';
            $admin = $user->is_admin ? ' 👑 ADMIN' : '';
            $lastLogin = $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never logged in';
            $created = $user->created_at->diffForHumans();

            echo "   ID: {$user->id} | {$status}{$admin}\n";
            echo "   📛 Name: {$user->name}\n";
            echo "   📧 Email: {$user->email}\n";
            echo "   ⏰ Last Login: {$lastLogin}\n";
            echo "   📅 Created: {$created}\n";
            echo "   ─────────────────────────────────────\n";
        }
    } else {
        echo "❌ NO USERS FOUND!\n";
        echo "🔧 Creating admin user...\n";

        $admin = \App\Models\User::create([
            'name' => 'Parish Administrator',
            'email' => 'admin@parish.com',
            'email_verified_at' => now(),
            'password' => \Illuminate\Support\Facades\Hash::make('admin123'),
            'is_active' => true,
            'is_admin' => true,
        ]);

        echo "✅ Admin user created successfully!\n";
        echo "📧 Email: admin@parish.com\n";
        echo "🔑 Password: admin123\n";
    }

    // Check if admin user exists
    $admin = \App\Models\User::where('email', 'admin@parish.com')->first();
    if ($admin) {
        echo "\n=== ADMIN USER VERIFICATION ===\n";
        echo "✅ Admin user confirmed\n";
        echo "📧 Email: {$admin->email}\n";
        echo '🔑 Status: '.($admin->is_active ? 'ACTIVE' : 'INACTIVE')."\n";
        echo '👑 Admin Rights: '.($admin->is_admin ? 'YES' : 'NO')."\n";
        echo "🌐 Login URL: http://localhost:8000/login\n";
    }

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
}
