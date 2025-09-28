<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating system users with proper role assignments...');

        try {
            // Create or update admin user (skip if already exists from RolePermissionSeeder)
            $admin = User::updateOrCreate(
                ['email' => 'admin@parish.com'],
                [
                    'name' => 'Super Administrator',
                    'phone' => '+254700000001',
                    'password' => Hash::make('admin123'),
                    'is_active' => true,
                    'date_of_birth' => '1980-01-01',
                    'gender' => 'Male',
                    'address' => 'Parish Office',
                    'occupation' => 'Parish Administrator',
                    'emergency_contact' => 'Emergency Contact Admin',
                    'emergency_phone' => '+254700000011',
                    'email_verified_at' => now(),
                ]
            );

            // Assign super-admin role if not already assigned
            $this->assignRoleIfExists($admin, 'super-admin', 'Super Admin role assigned to admin user');

            // Create parish priest with admin role
            $priest = User::updateOrCreate(
                ['email' => 'priest@parish.com'],
                [
                    'name' => 'Father John Smith',
                    'phone' => '+254700000002',
                    'password' => Hash::make('priest123'),
                    'is_active' => true,
                    'date_of_birth' => '1975-05-15',
                    'gender' => 'Male',
                    'address' => 'Parish Rectory',
                    'occupation' => 'Parish Priest',
                    'emergency_contact' => 'Diocese Office',
                    'emergency_phone' => '+254700000022',
                    'email_verified_at' => now(),
                ]
            );

            $this->assignRoleIfExists($priest, 'admin', 'Admin role assigned to priest user');

            // Create secretary with secretary role
            $secretary = User::updateOrCreate(
                ['email' => 'secretary@parish.com'],
                [
                    'name' => 'Mary Johnson',
                    'phone' => '+254700000003',
                    'password' => Hash::make('secretary123'),
                    'is_active' => true,
                    'date_of_birth' => '1985-08-20',
                    'gender' => 'Female',
                    'address' => 'Nairobi, Kenya',
                    'occupation' => 'Parish Secretary',
                    'emergency_contact' => 'John Johnson',
                    'emergency_phone' => '+254700000033',
                    'email_verified_at' => now(),
                ]
            );

            $this->assignRoleIfExists($secretary, 'secretary', 'Secretary role assigned to secretary user');

            // Create treasurer with treasurer role
            $treasurer = User::updateOrCreate(
                ['email' => 'treasurer@parish.com'],
                [
                    'name' => 'Peter Kamau',
                    'phone' => '+254700000004',
                    'password' => Hash::make('treasurer123'),
                    'is_active' => true,
                    'date_of_birth' => '1978-12-10',
                    'gender' => 'Male',
                    'address' => 'Nairobi, Kenya',
                    'occupation' => 'Accountant',
                    'emergency_contact' => 'Grace Kamau',
                    'emergency_phone' => '+254700000044',
                    'email_verified_at' => now(),
                ]
            );

            $this->assignRoleIfExists($treasurer, 'treasurer', 'Treasurer role assigned to treasurer user');

            $this->command->info('✅ All system users created successfully with proper roles!');

        } catch (\Exception $e) {
            $this->command->error('Error creating users: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Safely assign role to user if the role exists
     */
    private function assignRoleIfExists($user, $roleName, $successMessage)
    {
        try {
            // Check if role exists
            $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
            
            if ($role && !$user->hasRole($roleName)) {
                $user->assignRole($roleName);
                $this->command->info('✅ ' . $successMessage);
                return true;
            } elseif ($user->hasRole($roleName)) {
                $this->command->info("ℹ️  User already has {$roleName} role");
                return true;
            } else {
                $this->command->warn("⚠️  Role '{$roleName}' not found, skipping assignment");
                return false;
            }
        } catch (\Exception $e) {
            $this->command->error("❌ Failed to assign {$roleName} role: " . $e->getMessage());
            return false;
        }
    }
}
