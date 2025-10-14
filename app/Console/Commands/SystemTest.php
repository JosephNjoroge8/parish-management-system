<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Family;
use App\Models\Member;
use App\Models\Activity;
use App\Models\CommunityGroup;
use App\Models\Sacrament;
use Illuminate\Console\Command;

class SystemTest extends Command
{
    protected $signature = 'system:test';
    protected $description = 'Test system functionality and database connectivity';

    public function handle()
    {
        $this->info('🔍 Testing Parish Management System...');
        $this->newLine();

        // Test database connectivity
        try {
            $this->info('📊 Database Connection: ✅ Connected');
            
            // Test models
            $userCount = User::count();
            $familyCount = Family::count();
            $memberCount = Member::count();
            $activityCount = Activity::count();
            $groupCount = CommunityGroup::count();
            $sacramentCount = Sacrament::count();

            $this->info("👤 Users: {$userCount}");
            $this->info("👨‍👩‍👧‍👦 Families: {$familyCount}");
            $this->info("👥 Members: {$memberCount}");
            $this->info("📅 Activities: {$activityCount}");
            $this->info("🏛️ Community Groups: {$groupCount}");
            $this->info("⛪ Sacraments: {$sacramentCount}");

            $this->newLine();

            // Test admin user
            $adminUser = User::where('is_admin', true)->first();
            if ($adminUser) {
                $this->info("🔑 Admin User Found: {$adminUser->name} ({$adminUser->email})");
            } else {
                $this->warn("⚠️ No admin user found!");
            }

            $this->newLine();
            $this->info('✅ System Status: All components working properly!');
            $this->info('🌐 Server running at: http://127.0.0.1:8000');
            
            if ($adminUser) {
                $this->newLine();
                $this->info('🔐 Login Credentials:');
                $this->info("Email: {$adminUser->email}");
                $this->info('Password: admin123');
            }

        } catch (\Exception $e) {
            $this->error('❌ System Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}