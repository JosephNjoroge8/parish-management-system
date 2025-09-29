<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds for production environment.
     */
    public function run(): void
    {
        // Create default super admin user with simple is_admin flag
        $user = User::firstOrCreate(
            ['email' => 'admin@parish.local'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'), // Change this password immediately
                'email_verified_at' => now(),
                'is_active' => true,
                'is_admin' => true, // Simple admin flag
            ]
        );

        $this->command->info('Production seeder completed successfully!');
        $this->command->warn('IMPORTANT: Change the default admin password immediately after deployment!');
        $this->command->info('Default login: admin@parish.local / admin123');
        $this->command->info('Simple admin authentication system is configured - no complex roles needed.');
    }
}