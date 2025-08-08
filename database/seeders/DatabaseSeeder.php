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

        // Create admin user first (required for other seeders)
        $this->command->info('Creating admin user...');
        $admin = User::factory()->create([
            'name' => 'Parish Administrator',
            'email' => 'admin@parish.com',
            'phone' => '+254700000000',
            'is_active' => true,
            'date_of_birth' => '1980-01-01',
            'gender' => 'male',
            'address' => 'Parish Office',
            'occupation' => 'Parish Administrator',
        ]);

        // Create test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+254700000001',
            'is_active' => true,
        ]);

        $this->command->info('✅ Created admin and test users');

        // Run seeders in the correct order based on dependencies
        $this->call([
            // 1. Permissions and roles (no dependencies)
            PermissionSeeder::class,
            
            // 2. Family data (depends on users)
            FamilySeeder::class,
            
            // 3. Member data (depends on families and users)
            MemberSeeder::class,
            
            // 4. Community groups (depends on members)
            CommunityGroupSeeder::class,
            
            // 5. Sacrament data (depends on members)
            SacramentSeeder::class,
            
            // 6. Tithe data (depends on members)
            TitheSeeder::class,
        ]);

        $this->command->info('🎉 Parish Management System Database Seeding Completed Successfully!');
        $this->command->info('📊 You can now access the dashboard to view your parish data.');
    }
}
