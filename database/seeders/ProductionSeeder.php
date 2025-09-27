<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds for production environment.
     */
    public function run(): void
    {
        // Create permissions if they don't exist
        $permissions = [
            // User management
            'manage users',
            'access members',
            'manage members', 
            'delete members',
            'export members',
            
            // Family management
            'access families',
            'manage families',
            'delete families',
            
            // Sacraments
            'access sacraments',
            'manage sacraments',
            'delete sacraments',
            
            // Tithes and offerings
            'access tithes',
            'manage tithes',
            'delete tithes',
            
            // Activities
            'access activities',
            'manage activities', 
            'delete activities',
            
            // Community groups
            'access community groups',
            'manage community groups',
            
            // Reports
            'access reports',
            'export reports',
            'view financial reports',
            
            // Admin
            'assign roles',
            'manage permissions',
            'access admin panel',
            'system backup',
            'clear cache'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $secretary = Role::firstOrCreate(['name' => 'secretary', 'guard_name' => 'web']);
        $treasurer = Role::firstOrCreate(['name' => 'treasurer', 'guard_name' => 'web']);

        // Assign permissions to roles
        $superAdmin->syncPermissions(Permission::all());
        
        $admin->syncPermissions([
            'access members', 'manage members', 'export members',
            'access families', 'manage families', 
            'access sacraments', 'manage sacraments',
            'access activities', 'manage activities',
            'access community groups', 'manage community groups',
            'access reports', 'export reports',
            'access tithes', 'manage tithes'
        ]);

        $secretary->syncPermissions([
            'access members', 'manage members', 'export members',
            'access families', 'manage families',
            'access sacraments', 'manage sacraments',
            'access activities', 'manage activities',
            'access community groups',
            'access reports', 'export reports'
        ]);

        $treasurer->syncPermissions([
            'access members', 'access families',
            'access tithes', 'manage tithes', 'delete tithes',
            'access reports', 'export reports', 'view financial reports'
        ]);

        // Create default super admin user
        $user = User::firstOrCreate(
            ['email' => 'admin@parish.local'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'), // Change this password immediately
                'email_verified_at' => now(),
            ]
        );

        $user->assignRole('super-admin');

        $this->command->info('Production seeder completed successfully!');
        $this->command->warn('IMPORTANT: Change the default admin password immediately after deployment!');
        $this->command->info('Default login: admin@parish.local / admin123');
    }
}