<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
                    'password' => Hash::make('admin123'),
                    'is_admin' => true, // Simple admin flag
                    'email_verified_at' => now(),
                ]
            );

            $this->command->info('✅ Single admin user created - no role restrictions, full system access!');

        } catch (\Exception $e) {
            $this->command->error('Error creating admin user: '.$e->getMessage());
            throw $e;
        }
    }
}
