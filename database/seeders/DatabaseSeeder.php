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
            // 1. Users (simple is_admin flag authentication)
            UserSeeder::class,
            
            // 2. Sample data (depends on users)
            SampleDataSeeder::class,
        ]);

        $this->command->info('🎉 Parish Management System Database Seeding Completed Successfully!');
        $this->command->info('🔐 Simple admin authentication system is now configured.');
        $this->command->info('� Super Admin credentials: admin@parish.com / admin123');
        $this->command->info('�📊 You can now access the dashboard with admin privileges.');
    }
}
