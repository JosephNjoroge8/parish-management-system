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
            $this->command->info('🔐 Setting up roles and permissions system...');
            
            // Clear cache to avoid permission issues
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            // Create basic permissions (use firstOrCreate to avoid duplicates)
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

            $this->command->info('Creating permissions...');
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }
            $this->command->info("✅ Created/verified " . count($permissions) . " permissions");

            // Create roles with hierarchical clearance levels
            $this->command->info('Creating roles...');
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
                
                $this->command->info("✅ Role '{$roleData['name']}' created with " . count($roleData['permissions']) . " permissions");
                Log::info("Role created/updated: {$roleData['name']} with " . count($roleData['permissions']) . " permissions");
            }

            // Create default super admin user if not exists
            $this->command->info('Creating default super admin user...');
            $superAdminEmail = 'admin@parish.com';
            $superAdmin = User::firstOrCreate(
                ['email' => $superAdminEmail],
                [
                    'name' => 'Super Administrator',
                    'password' => Hash::make('admin123'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'date_of_birth' => '1980-01-01',
                    'gender' => 'Male',
                    'address' => 'Parish Office',
                    'occupation' => 'System Administrator',
                    'phone' => '+254700000001',
                ]
            );

            // Assign super-admin role (force assignment)
            try {
                // Remove existing roles first to ensure clean assignment
                $superAdmin->roles()->detach();
                
                // Assign super-admin role
                $superAdmin->assignRole('super-admin');
                Log::info('Super admin role assigned to default user');
                $this->command->info('✅ Super-admin role assigned to admin@parish.com');
                
                // Verify the assignment
                $hasRole = $superAdmin->hasRole('super-admin');
                $this->command->info($hasRole ? '✅ Role assignment verified' : '❌ Role assignment failed');
                
            } catch (\Exception $e) {
                $this->command->error('❌ Failed to assign super-admin role: ' . $e->getMessage());
                Log::error('Super admin role assignment failed', ['error' => $e->getMessage()]);
            }

            $this->command->info('🎉 Roles and permissions system setup completed successfully!');
            $this->command->info('🔑 Super Admin Login: admin@parish.com / admin123');
            
        } catch (\Exception $e) {
            Log::error('Error seeding roles and permissions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->command->error('Error seeding roles and permissions: ' . $e->getMessage());
        }
    }
}