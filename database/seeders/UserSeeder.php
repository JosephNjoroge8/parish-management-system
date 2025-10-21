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
            // Create super admin user - full system access
            $admin = User::updateOrCreate(
                ['email' => 'admin@parish.local'],
                [
                    'name' => 'Parish Administrator',
                    'password' => Hash::make('parish123'),
                    'is_admin' => true, // Super admin flag - complete system control
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Create additional staff users for testing
            $staff = User::updateOrCreate(
                ['email' => 'staff@parish.local'],
                [
                    'name' => 'Parish Staff',
                    'password' => Hash::make('staff123'),
                    'is_admin' => false,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $secretary = User::updateOrCreate(
                ['email' => 'secretary@parish.local'],
                [
                    'name' => 'Parish Secretary',
                    'password' => Hash::make('secretary123'),
                    'is_admin' => false,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Create backup admin (alternative super admin)
            $backupAdmin = User::updateOrCreate(
                ['email' => 'admin@parish.com'],
                [
                    'name' => 'Backup Administrator',
                    'password' => Hash::make('admin123'),
                    'is_admin' => true, // Super admin flag
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $this->command->info('✅ Successfully created system users:');
            $this->command->info('   🔑 SUPER ADMIN: admin@parish.local / parish123');
            $this->command->info('   🔑 BACKUP ADMIN: admin@parish.com / admin123');
            $this->command->info('   👤 STAFF: staff@parish.local / staff123');
            $this->command->info('   👤 SECRETARY: secretary@parish.local / secretary123');
            $this->command->info('   ⚡ All users have complete system access configured!');

        } catch (\Exception $e) {
            $this->command->error('Error creating users: '.$e->getMessage());
            throw $e;
        }
    }
}
