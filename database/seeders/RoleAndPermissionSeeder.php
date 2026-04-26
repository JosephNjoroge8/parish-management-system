<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Member management
            'view members',
            'create members',
            'edit members',
            'delete members',
            'manage member sacraments',

            // Family management
            'view families',
            'create families',
            'edit families',
            'delete families',

            // Sacrament records
            'view sacraments',
            'create sacraments',
            'edit sacraments',
            'delete sacraments',
            'manage baptism records',
            'manage marriage records',

            // Financial management
            'view tithes',
            'create tithes',
            'edit tithes',
            'delete tithes',
            'view financial reports',

            // Activity management
            'view activities',
            'create activities',
            'edit activities',
            'delete activities',
            'manage activity participants',

            // Community groups
            'view community groups',
            'create community groups',
            'edit community groups',
            'delete community groups',

            // Reports and analytics
            'view reports',
            'generate reports',
            'export data',

            // System administration
            'manage users',
            'manage roles',
            'manage permissions',
            'view system logs',
            'manage system settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - has all permissions
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Parish Priest - has most permissions except system administration
        $parishPriest = Role::create(['name' => 'parish_priest']);
        $parishPriest->givePermissionTo([
            'view members', 'create members', 'edit members', 'delete members', 'manage member sacraments',
            'view families', 'create families', 'edit families', 'delete families',
            'view sacraments', 'create sacraments', 'edit sacraments', 'delete sacraments',
            'manage baptism records', 'manage marriage records',
            'view tithes', 'create tithes', 'edit tithes', 'view financial reports',
            'view activities', 'create activities', 'edit activities', 'delete activities', 'manage activity participants',
            'view community groups', 'create community groups', 'edit community groups', 'delete community groups',
            'view reports', 'generate reports', 'export data',
        ]);

        // Parish Secretary - administrative tasks
        $secretary = Role::create(['name' => 'secretary']);
        $secretary->givePermissionTo([
            'view members', 'create members', 'edit members',
            'view families', 'create families', 'edit families',
            'view sacraments', 'create sacraments', 'edit sacraments',
            'view tithes', 'create tithes', 'edit tithes',
            'view activities', 'create activities', 'edit activities', 'manage activity participants',
            'view community groups',
            'view reports', 'export data',
        ]);

        // Treasurer - financial focus
        $treasurer = Role::create(['name' => 'treasurer']);
        $treasurer->givePermissionTo([
            'view members',
            'view families',
            'view tithes', 'create tithes', 'edit tithes', 'delete tithes', 'view financial reports',
            'view activities',
            'view reports', 'generate reports',
        ]);

        // Catechist - sacrament focus
        $catechist = Role::create(['name' => 'catechist']);
        $catechist->givePermissionTo([
            'view members', 'edit members',
            'view families',
            'view sacraments', 'create sacraments', 'edit sacraments', 'manage baptism records',
            'view activities', 'create activities', 'edit activities', 'manage activity participants',
            'view community groups',
        ]);

        // Group Leader - limited to their groups
        $groupLeader = Role::create(['name' => 'group_leader']);
        $groupLeader->givePermissionTo([
            'view members',
            'view families',
            'view activities', 'create activities', 'manage activity participants',
            'view community groups', 'edit community groups',
        ]);

        // Member - read-only access to own data
        $member = Role::create(['name' => 'member']);
        $member->givePermissionTo([
            'view members',
            'view activities',
            'view community groups',
        ]);
    }
}
