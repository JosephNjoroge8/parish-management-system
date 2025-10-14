<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Models\Member;
use App\Models\Activity;
use App\Models\CommunityGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddSampleData extends Command
{
    protected $signature = 'data:sample';
    protected $description = 'Add sample data for testing the parish system';

    public function handle()
    {
        $this->info('🌱 Adding sample data to Parish Management System...');

        try {
            DB::beginTransaction();

            // Create sample families
            $family1 = Family::create([
                'family_name' => 'The Johnson Family',
                'head_of_family' => 'John Johnson',
                'address' => '123 Church Street, Nairobi',
                'phone' => '+254712345678',
                'email' => 'johnson.family@gmail.com',
                'registration_date' => now()->subMonths(6),
                'status' => 'active'
            ]);

            $family2 = Family::create([
                'family_name' => 'The Wanjiku Family', 
                'head_of_family' => 'Mary Wanjiku',
                'address' => '456 Faith Avenue, Kiambu',
                'phone' => '+254723456789',
                'email' => 'wanjiku.family@gmail.com',
                'registration_date' => now()->subMonths(3),
                'status' => 'active'
            ]);

            // Create sample members
            Member::create([
                'family_id' => $family1->id,
                'first_name' => 'John',
                'middle_name' => 'Paul',
                'last_name' => 'Johnson',
                'date_of_birth' => '1980-05-15',
                'gender' => 'Male',
                'phone' => '+254712345678',
                'email' => 'john.johnson@gmail.com',
                'occupation' => 'Teacher',
                'marital_status' => 'Married',
                'member_since' => now()->subMonths(6),
                'status' => 'active'
            ]);

            Member::create([
                'family_id' => $family1->id,
                'first_name' => 'Grace',
                'middle_name' => 'Anne',
                'last_name' => 'Johnson',
                'date_of_birth' => '1985-08-22',
                'gender' => 'Female',
                'phone' => '+254787654321',
                'email' => 'grace.johnson@gmail.com',
                'occupation' => 'Nurse',
                'marital_status' => 'Married',
                'member_since' => now()->subMonths(6),
                'status' => 'active'
            ]);

            Member::create([
                'family_id' => $family2->id,
                'first_name' => 'Mary',
                'middle_name' => 'Rose',
                'last_name' => 'Wanjiku',
                'date_of_birth' => '1975-12-10',
                'gender' => 'Female',
                'phone' => '+254723456789',
                'email' => 'mary.wanjiku@gmail.com',
                'occupation' => 'Business Owner',
                'marital_status' => 'Widowed',
                'member_since' => now()->subMonths(3),
                'status' => 'active'
            ]);

            // Create sample activities (simplified)
            Activity::create([
                'title' => 'Sunday Mass',
                'description' => 'Regular Sunday worship service',
                'activity_date' => now()->addDays(7)->setHour(8)->setMinute(0),
                'location' => 'Main Church',
                'status' => 'scheduled',
                'created_by' => 1
            ]);

            Activity::create([
                'title' => 'Youth Fellowship',
                'description' => 'Monthly youth gathering for fellowship and prayer',
                'activity_date' => now()->addDays(14)->setHour(15)->setMinute(0),
                'location' => 'Parish Hall',
                'status' => 'scheduled',
                'created_by' => 1
            ]);

            DB::commit();

            $this->info('✅ Sample data added successfully!');
            $this->info('📊 Added:');
            $this->info('   - 2 Families');
            $this->info('   - 3 Members');
            $this->info('   - 2 Activities');

        } catch (\Exception $e) {
            DB::rollback();
            $this->error('❌ Error adding sample data: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}