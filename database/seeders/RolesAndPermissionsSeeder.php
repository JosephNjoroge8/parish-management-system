<?php
// filepath: database/seeders/RolesAndPermissionsSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User Management
            'manage users',
            'create users',
            'edit users',
            'delete users',
            'view users',

            // Member Management
            'manage members',
            'create members',
            'edit members',
            'delete members',
            'view members',
            'export members',

            // Family Management
            'manage families',
            'create families',
            'edit families',
            'delete families',
            'view families',

            // Sacrament Management
            'manage sacraments',
            'create sacraments',
            'edit sacraments',
            'delete sacraments',
            'view sacraments',

            // Tithe Management
            'manage tithes',
            'create tithes',
            'edit tithes',
            'delete tithes',
            'view tithes',
            'view financial reports',

            // Community Group Management
            'manage community groups',
            'create community groups',
            'edit community groups',
            'delete community groups',
            'view community groups',

            // Activity Management
            'manage activities',
            'create activities',
            'edit activities',
            'delete activities',
            'view activities',

            // Reports and Analytics
            'view reports',
            'view analytics',
            'export reports',
            'view financial data',

            // System Settings
            'manage settings',
            'manage roles',
            'manage permissions',
            'view system logs',

            // Access permissions
            'access dashboard',
            'access members',
            'access families',
            'access sacraments',
            'access tithes',
            'access community groups',
            'access activities',
            'access reports',
            'access settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - has all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - has most permissions except user management and system settings
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $adminPermissions = [
            'manage members', 'create members', 'edit members', 'view members', 'export members',
            'manage families', 'create families', 'edit families', 'view families',
            'manage sacraments', 'create sacraments', 'edit sacraments', 'view sacraments',
            'manage tithes', 'create tithes', 'edit tithes', 'view tithes', 'view financial reports',
            'manage community groups', 'create community groups', 'edit community groups', 'view community groups',
            'manage activities', 'create activities', 'edit activities', 'view activities',
            'view reports', 'view analytics', 'export reports', 'view financial data',
            'access dashboard', 'access members', 'access families', 'access sacraments',
            'access tithes', 'access community groups', 'access activities', 'access reports',
        ];
        $admin->givePermissionTo($adminPermissions);

        // Secretary - limited permissions
        $secretary = Role::firstOrCreate(['name' => 'secretary']);
        $secretaryPermissions = [
            'manage members', 'create members', 'edit members', 'view members',
            'manage families', 'create families', 'edit families', 'view families',
            'create sacraments', 'edit sacraments', 'view sacraments',
            'view tithes', 'create tithes',
            'view community groups', 'view activities',
            'view reports', 'access dashboard', 'access members', 'access families',
            'access sacraments', 'access community groups', 'access activities',
        ];
        $secretary->givePermissionTo($secretaryPermissions);

        // Treasurer - financial focus
        $treasurer = Role::firstOrCreate(['name' => 'treasurer']);
        $treasurerPermissions = [
            'view members', 'view families',
            'manage tithes', 'create tithes', 'edit tithes', 'view tithes',
            'view financial reports', 'view financial data',
            'view reports', 'view analytics', 'export reports',
            'access dashboard', 'access tithes', 'access reports',
        ];
        $treasurer->givePermissionTo($treasurerPermissions);

        // Viewer - read-only access
        $viewer = Role::firstOrCreate(['name' => 'viewer']);
        $viewerPermissions = [
            'view members', 'view families', 'view sacraments', 'view community groups',
            'view activities', 'view reports',
            'access dashboard', 'access members', 'access families', 'access sacraments',
            'access community groups', 'access activities', 'access reports',
        ];
        $viewer->givePermissionTo($viewerPermissions);

        // Create Super Admin User
        $superAdminUser = User::firstOrCreate(
            ['email' => 'admin@parish.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('admin123'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $superAdminUser->assignRole('super-admin');

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Super Admin created: admin@parish.com / admin123');
    }
}