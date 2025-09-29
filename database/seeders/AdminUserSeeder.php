<?php
// filepath: database/seeders/AdminUserSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create super admin user with simple is_admin flag
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@parish.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'is_active' => true,
                'is_admin' => true, // Simple admin flag
            ]
        );

        echo "Admin user created/updated: admin@parish.com / admin123\n";
    }
}