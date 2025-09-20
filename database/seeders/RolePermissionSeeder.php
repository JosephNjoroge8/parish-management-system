<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // Clear cache to avoid permission issues
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            // Create basic permissions
            $permissions = [
                // User management
                'access users',
                'manage users',
                'delete users',
                
                // Role management
                'access roles',
                'manage roles',
                'delete roles',
                
                // Member management
                'access members',
                'manage members',
                'delete members',
                'export members',
                
                // Family management
                'access families',
                'manage families',
                'delete families',
                
                // Sacrament management
                'access sacraments',
                'manage sacraments',
                'delete sacraments',
                
                // Financial management
                'access tithes',
                'manage tithes',
                'delete tithes',
                'view financial reports',
                
                // Activity management
                'access activities',
                'manage activities',
                'delete activities',
                
                // Report management
                'access reports',
                'export reports',
                
                // Settings management
                'access settings',
                'manage settings',
            ];

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }

            // Create roles with hierarchical clearance levels
            $roles = [
                [
                    'name' => 'super-admin',
                    'clearance_level' => 5,
                    'permissions' => $permissions // Super admin gets all permissions
                ],
                [
                    'name' => 'admin',
                    'clearance_level' => 4,
                    'permissions' => [
                        'access users', 'manage users',
                        'access members', 'manage members', 'export members',
                        'access families', 'manage families',
                        'access sacraments', 'manage sacraments',
                        'access tithes', 'manage tithes', 'view financial reports',
                        'access activities', 'manage activities',
                        'access reports', 'export reports',
                        'access settings'
                    ]
                ],
                [
                    'name' => 'manager',
                    'clearance_level' => 3,
                    'permissions' => [
                        'access members', 'manage members',
                        'access families', 'manage families',
                        'access sacraments', 'manage sacraments',
                        'access activities', 'manage activities',
                        'access reports'
                    ]
                ],
                [
                    'name' => 'staff',
                    'clearance_level' => 2,
                    'permissions' => [
                        'access members', 'manage members',
                        'access families',
                        'access sacraments',
                        'access activities'
                    ]
                ],
                [
                    'name' => 'secretary',
                    'clearance_level' => 2,
                    'permissions' => [
                        'access members', 'manage members',
                        'access families', 'manage families',
                        'access sacraments', 'manage sacraments',
                        'access reports'
                    ]
                ],
                [
                    'name' => 'treasurer',
                    'clearance_level' => 2,
                    'permissions' => [
                        'access members',
                        'access tithes', 'manage tithes', 'view financial reports',
                        'access reports', 'export reports'
                    ]
                ],
                [
                    'name' => 'viewer',
                    'clearance_level' => 1,
                    'permissions' => [
                        'access members',
                        'access families',
                        'access sacraments',
                        'access activities'
                    ]
                ]
            ];

            foreach ($roles as $roleData) {
                $role = Role::firstOrCreate(['name' => $roleData['name']]);
                
                // Assign permissions to role
                $role->syncPermissions($roleData['permissions']);
                
                Log::info("Role created/updated: {$roleData['name']} with " . count($roleData['permissions']) . " permissions");
            }

            // Create default super admin user if not exists
            $superAdminEmail = 'admin@parish.com';
            $superAdmin = User::firstOrCreate(
                ['email' => $superAdminEmail],
                [
                    'name' => 'Super Administrator',
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Assign super-admin role
            if (!$superAdmin->hasRole('super-admin')) {
                $superAdmin->assignRole('super-admin');
                Log::info('Super admin role assigned to default user');
            }

            $this->command->info('Roles and permissions seeded successfully!');
            
        } catch (\Exception $e) {
            Log::error('Error seeding roles and permissions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->command->error('Error seeding roles and permissions: ' . $e->getMessage());
        }
    }
}