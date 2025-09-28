<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting Parish Management System Database Seeding...');

        // Run seeders in the correct order based on dependencies
        $this->call([
            // 1. CRITICAL: Roles and permissions MUST be seeded first
            RolePermissionSeeder::class,
            
            // 2. Users (depends on roles and permissions)
            UserSeeder::class,
            
            // 3. Sample data (depends on users and permissions)
            SampleDataSeeder::class,
        ]);

        $this->command->info('🎉 Parish Management System Database Seeding Completed Successfully!');
        $this->command->info('🔐 Authentication and authorization system is now properly configured.');
        $this->command->info('� Super Admin credentials: admin@parish.com / admin123');
        $this->command->info('�📊 You can now access the dashboard with proper role-based security.');
    }
}
