<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting Parish Management System Database Seeding...');
        $this->command->info('📋 Creating comprehensive test data for production deployment...');

        // Run seeders in the correct order based on dependencies
        $this->call([
            // 1. Enhanced Users - Creates 4 users including 2 super admins
            UserSeeder::class,

            // 2. Enhanced Members - Creates exactly 30 comprehensive test members
            Enhanced30MemberSeeder::class,

            // 3. Additional sample data if needed
            // SampleDataSeeder::class, // Commented out - Enhanced30MemberSeeder provides all needed test data
        ]);

        $this->command->info('');
        $this->command->info('🎉 Parish Management System Database Seeding Completed Successfully!');
        $this->command->info('');
        $this->command->info('🔐 AUTHENTICATION SYSTEM CONFIGURED:');
        $this->command->info('  ✅ Simple is_admin flag authentication active');
        $this->command->info('  ✅ 4 test users created (2 super admins, 1 staff, 1 secretary)');
        $this->command->info('');
        $this->command->info('👤 LOGIN CREDENTIALS:');
        $this->command->info('  🔑 Super Admin: admin@parish.local / parish123');
        $this->command->info('  🔑 Backup Admin: admin@parish.com / admin123');
        $this->command->info('  👨‍💼 Staff User: staff@parish.local / staff123');
        $this->command->info('  📝 Secretary: secretary@parish.local / secretary123');
        $this->command->info('');
        $this->command->info('📊 MEMBER TEST DATA:');
        $this->command->info('  ✅ 30 comprehensive parish members created');
        $this->command->info('  ✅ All matrimony statuses: married, single, widowed, divorced');
        $this->command->info('  ✅ All marriage types: church, civil, customary');
        $this->command->info('  ✅ All age groups: children, teens, adults, seniors');
        $this->command->info('  ✅ Diverse occupations and education levels');
        $this->command->info('  ✅ Special needs member included');
        $this->command->info('  ✅ All membership statuses represented');
        $this->command->info('');
        $this->command->info('🌐 SYSTEM ACCESS:');
        $this->command->info('  📍 URL: http://127.0.0.1:8000');
        $this->command->info('  🚀 Ready for comprehensive production testing!');
    }
}
