<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Members Management
            'access members',
            'manage members',
            'delete members',
            'export members',
            
            // Families Management
            'access families',
            'manage families',
            'delete families',
            
            // Sacraments Management
            'access sacraments',
            'manage sacraments',
            'delete sacraments',
            
            // Tithes Management
            'access tithes',
            'manage tithes',
            'delete tithes',
            'view financial reports',
            
            // Activities Management
            'access activities',
            'manage activities',
            'delete activities',
            
            // Reports Access
            'access reports',
            'export reports',
            
            // User Management (Admin only)
            'manage users',
            'assign roles',
            'manage permissions',
            
            // System Administration
            'access admin panel',
            'system backup',
            'clear cache',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $memberRole = Role::firstOrCreate(['name' => 'member']);
        $viewerRole = Role::firstOrCreate(['name' => 'viewer']);

        // Super Admin gets all permissions
        $superAdminRole->givePermissionTo(Permission::all());

        // Admin gets most permissions (except super admin specific ones)
        $adminPermissions = [
            'access members', 'manage members', 'delete members', 'export members',
            'access families', 'manage families', 'delete families',
            'access sacraments', 'manage sacraments', 'delete sacraments',
            'access tithes', 'manage tithes', 'delete tithes', 'view financial reports',
            'access activities', 'manage activities', 'delete activities',
            'access reports', 'export reports',
            'manage users', 'assign roles',
            'access admin panel', 'clear cache',
        ];
        $adminRole->givePermissionTo($adminPermissions);

        // Member role gets basic access
        $memberPermissions = [
            'access members', 'access families', 'access sacraments',
            'access tithes', 'access activities', 'access reports',
        ];
        $memberRole->givePermissionTo($memberPermissions);

        // Viewer role gets read-only access
        $viewerPermissions = [
            'access members', 'access families', 'access sacraments',
            'access activities', 'access reports',
        ];
        $viewerRole->givePermissionTo($viewerPermissions);

        // Assign roles to existing users
        $adminUser = User::where('email', 'admin@parish.com')->first();
        if ($adminUser) {
            $adminUser->assignRole('super-admin');
            $this->command->info('✅ Assigned super-admin role to admin@parish.com');
        }

        $testUser = User::where('email', 'test@parish.com')->first();
        if ($testUser) {
            $testUser->assignRole('admin');
            $this->command->info('✅ Assigned admin role to test@parish.com');
        }

        $this->command->info('🔐 Permissions and roles seeded successfully!');
        $this->command->info('📊 Created ' . Permission::count() . ' permissions and ' . Role::count() . ' roles');
    }
}