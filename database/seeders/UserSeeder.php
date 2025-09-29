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
            // Create single super admin user - no roles needed, full access to everything
            $admin = User::updateOrCreate(
                ['email' => 'admin@parish.com'],
                [
                    'name' => 'Parish Administrator',
                    'phone' => '+254700000001',
                    'password' => Hash::make('admin123'),
                    'is_active' => true,
                    'is_admin' => true, // Simple admin flag
                    'date_of_birth' => '1980-01-01',
                    'gender' => 'Male',
                    'address' => 'Parish Office',
                    'occupation' => 'Parish Administrator',
                    'emergency_contact' => 'Emergency Contact Admin',
                    'emergency_phone' => '+254700000011',
                    'email_verified_at' => now(),
                ]
            );

            $this->command->info('✅ Single admin user created - no role restrictions, full system access!');

        } catch (\Exception $e) {
            $this->command->error('Error creating admin user: ' . $e->getMessage());
            throw $e;
        }
    }
}
