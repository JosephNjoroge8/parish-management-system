<?php
// filepath: database/seeders/RolesAndPermissionsSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'manage-members',
            'manage-families',
            'manage-sacraments',
            'manage-groups',
            'manage-users',
            'view-reports',
            'manage-settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $priestRole = Role::create(['name' => 'priest']);
        $secretaryRole = Role::create(['name' => 'secretary']);
        $memberRole = Role::create(['name' => 'member']);

        // Assign permissions to roles
        $adminRole->givePermissionTo(Permission::all());
        $priestRole->givePermissionTo([
            'manage-members', 'manage-families', 'manage-sacraments', 
            'manage-groups', 'view-reports'
        ]);
        $secretaryRole->givePermissionTo([
            'manage-members', 'manage-families', 'view-reports'
        ]);

        // Create default admin user
        $admin = User::create([
            'name' => 'Parish Administrator',
            'email' => 'admin@parish.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $admin->assignRole('admin');
    }
}