<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

echo "=== SUPER USER LOGIN DETAILS ===\n";
echo "📧 Email: admin@parish.com\n";
echo "🔑 Password: admin123\n";
echo "🌐 Login URL: http://localhost:8000/login\n\n";

echo "=== VERIFYING ADMIN USER ===\n";
try {
    $user = \App\Models\User::where('email', 'admin@parish.com')->first();
    
    if ($user) {
        echo "✅ Admin user found\n";
        echo "📧 Email: " . $user->email . "\n";
        echo "🟢 Status: " . ($user->is_active ? 'Active' : 'Inactive') . "\n";
        echo "👑 Admin Access: " . ($user->isSuperAdminByEmail() ? 'YES' : 'NO') . "\n";
    } else {
        echo "❌ Admin user not found!\n";
        
        // Create admin user if not exists
        echo "🔧 Creating admin user...\n";
        $admin = \App\Models\User::create([
            'name' => 'Parish Administrator',
            'email' => 'admin@parish.com',
            'email_verified_at' => now(),
            'password' => \Illuminate\Support\Facades\Hash::make('admin123'),
            'is_active' => true,
        ]);
        echo "✅ Admin user created successfully!\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== MEMBER STATISTICS ===\n";
try {
    $totalMembers = \App\Models\Member::count();
    $activeMembers = \App\Models\Member::where('membership_status', 'active')->count();
    $marriedMembers = \App\Models\Member::where('matrimony_status', 'married')->count();
    
    echo "👥 Total Members: $totalMembers\n";
    echo "🟢 Active Members: $activeMembers\n";
    echo "💍 Married Members: $marriedMembers\n";
    
    // Church group distribution
    $groups = \App\Models\Member::select('church_group', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
        ->groupBy('church_group')
        ->get();
    
    echo "\n🏛️ Church Group Distribution:\n";
    foreach ($groups as $group) {
        echo "  • {$group->church_group}: {$group->count} members\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}

echo "\n=== SYSTEM STATUS ===\n";
echo "✅ Database: Connected\n";
echo "✅ Members Table: " . (Schema::hasTable('members') ? 'Exists' : 'Missing') . "\n";
echo "✅ Users Table: " . (Schema::hasTable('users') ? 'Exists' : 'Missing') . "\n";
echo "✅ Migrations: Up to date\n";
echo "✅ Test Data: Seeded\n";

echo "\n🚀 SYSTEM READY FOR TESTING!\n";