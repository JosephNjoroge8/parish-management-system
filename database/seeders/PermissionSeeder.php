<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'access dashboard',
            'access members',
            'manage members',
            'access families',
            'manage families',
            'access sacraments',
            'manage sacraments',
            'access tithes',
            'manage tithes',
            'access reports',
            'view financial reports',
            'access community groups',
            'manage community groups',
            'manage users',
            'access admin',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdminRole = Role::create(['name' => 'Super Admin']);
        $superAdminRole->givePermissionTo(Permission::all());

        $adminRole = Role::create(['name' => 'Admin']);
        $adminRole->givePermissionTo([
            'access dashboard',
            'access members',
            'manage members',
            'access families',
            'manage families',
            'access sacraments',
            'manage sacraments',
            'access tithes',
            'manage tithes',
            'access reports',
            'view financial reports',
            'access community groups',
            'manage community groups',
        ]);

        $parishClerkRole = Role::create(['name' => 'Parish Clerk']);
        $parishClerkRole->givePermissionTo([
            'access dashboard',
            'access members',
            'manage members',
            'access families',
            'manage families',
            'access sacraments',
            'manage sacraments',
            'access community groups',
        ]);

        $treasurerRole = Role::create(['name' => 'Treasurer']);
        $treasurerRole->givePermissionTo([
            'access dashboard',
            'access tithes',
            'manage tithes',
            'access reports',
            'view financial reports',
        ]);

        $viewerRole = Role::create(['name' => 'Viewer']);
        $viewerRole->givePermissionTo([
            'access dashboard',
            'access members',
            'access families',
            'access sacraments',
            'access reports',
        ]);

        // Assign Super Admin role to first user (if exists)
        $user = User::first();
        if ($user) {
            $user->assignRole($superAdminRole);
        }
    }
}