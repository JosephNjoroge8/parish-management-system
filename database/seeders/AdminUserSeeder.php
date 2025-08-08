<?php
// filepath: database/seeders/AdminUserSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create super admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'l'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'is_active' => true,
                'role' => 'super-admin', // Fallback role
            ]
        );

        // Try to create Spatie roles and assign them
        try {
            if (class_exists('\Spatie\Permission\Models\Role')) {
                // Create roles
                $superAdminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin']);
                $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
                
                // Assign role to user
                if (method_exists($adminUser, 'assignRole')) {
                    $adminUser->assignRole('super-admin');
                }
                
                echo "Spatie roles created and assigned successfully.\n";
            }
        } catch (\Exception $e) {
            echo "Spatie setup failed, using fallback role system: " . $e->getMessage() . "\n";
        }

        echo "Admin user created: admin@parish.com / admin123\n";
    }
}