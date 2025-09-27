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

        $this->command->info('Creating initial users...');

        $this->command->info('✅ Created admin and test users');

        // Run seeders in the correct order based on dependencies
        $this->call([
            // 1. Users and permissions
            UserSeeder::class,
            
            // 2. Sample data (depends on users)
            SampleDataSeeder::class,
        ]);

        $this->command->info('🎉 Parish Management System Database Seeding Completed Successfully!');
        $this->command->info('📊 You can now access the dashboard to view your parish data.');
    }
}
