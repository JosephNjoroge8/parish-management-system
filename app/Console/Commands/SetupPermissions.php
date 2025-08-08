<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SetupPermissions extends Command
{
    protected $signature = 'setup:permissions';
    protected $description = 'Setup roles and permissions for the parish system';

    public function handle()
    {
        $this->info('Setting up roles and permissions...');

        // Create permissions
        $permissions = [
            'manage users',
            'access members',
            'manage members', 
            'delete members',
            'export members',
            'access families',
            'manage families',
            'delete families',
            'access sacraments',
            'manage sacraments',
            'delete sacraments',
            'access tithes',
            'manage tithes',
            'delete tithes',
            'access activities',
            'manage activities',
            'delete activities',
            'access community groups',
            'manage community groups',
            'access reports',
            'export reports',
            'view financial reports',
        ];

        $this->info('Creating permissions...');
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $this->info('Creating roles...');
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $secretary = Role::firstOrCreate(['name' => 'secretary']);
        $treasurer = Role::firstOrCreate(['name' => 'treasurer']);

        // Assign permissions to super-admin
        $this->info('Assigning permissions to super-admin...');
        $superAdmin->givePermissionTo(Permission::all());

        // Assign permissions to other roles
        $admin->givePermissionTo([
            'access members', 'manage members', 'export members',
            'access families', 'manage families',
            'access sacraments', 'manage sacraments',
            'access activities', 'manage activities',
            'access community groups', 'manage community groups',
            'access reports', 'export reports',
        ]);

        $secretary->givePermissionTo([
            'access members', 'manage members',
            'access families', 'manage families',
            'access sacraments', 'manage sacraments',
            'access activities', 'manage activities',
        ]);

        $treasurer->givePermissionTo([
            'access members',
            'access tithes', 'manage tithes',
            'access reports', 'view financial reports',
        ]);

        // Assign super-admin role to all existing users
        $this->info('Assigning super-admin role to existing users...');
        $users = User::all();
        foreach ($users as $user) {
            if ($user->roles->count() === 0) {
                $user->assignRole('super-admin');
                $this->info("Assigned super-admin role to: {$user->email}");
            }
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->info('Setup completed successfully!');
        return 0;
    }
}