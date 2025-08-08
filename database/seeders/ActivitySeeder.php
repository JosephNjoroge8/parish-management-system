<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\CommunityGroup;
use Carbon\Carbon;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $groups = CommunityGroup::all();

        $activities = [
            [
                'title' => 'Sunday Mass',
                'description' => 'Regular Sunday worship service with Holy Communion',
                'activity_type' => 'mass',
                'start_datetime' => Carbon::next('Sunday')->setTime(9, 0),
                'end_datetime' => Carbon::next('Sunday')->setTime(11, 0),
                'location' => 'Main Church',
                'organizer_id' => $admin->id,
                'group_id' => null,
                'budget' => null,
                'actual_cost' => null,
                'expected_participants' => 200,
                'actual_participants' => null,
                'status' => 'planned',
                'notes' => 'Regular weekly mass',
                'is_recurring' => true,
                'recurrence_pattern' => 'weekly',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Youth Fellowship Meeting',
                'description' => 'Monthly youth gathering for fellowship, planning, and spiritual growth',
                'activity_type' => 'meeting',
                'start_datetime' => Carbon::now()->addDays(7)->setTime(15, 0),
                'end_datetime' => Carbon::now()->addDays(7)->setTime(17, 0),
                'location' => 'Parish Hall',
                'organizer_id' => $admin->id,
                'group_id' => $groups->where('group_type', 'youth')->first()?->id,
                'budget' => 5000.00,
                'actual_cost' => null,
                'expected_participants' => 30,
                'actual_participants' => null,
                'status' => 'planned',
                'notes' => 'Monthly youth meeting with refreshments',
                'is_recurring' => true,
                'recurrence_pattern' => 'monthly',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Parish Fundraising Dinner',
                'description' => 'Annual fundraising event for church building fund',
                'activity_type' => 'fundraising',
                'start_datetime' => Carbon::now()->addDays(30)->setTime(18, 0),
                'end_datetime' => Carbon::now()->addDays(30)->setTime(22, 0),
                'location' => 'Parish Hall',
                'organizer_id' => $admin->id,
                'group_id' => null,
                'budget' => 150000.00,
                'actual_cost' => null,
                'expected_participants' => 100,
                'actual_participants' => null,
                'status' => 'planned',
                'notes' => 'Annual fundraising dinner with entertainment',
                'is_recurring' => false,
                'recurrence_pattern' => null,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Marriage Preparation Workshop',
                'description' => 'Pre-marriage counseling and preparation sessions for engaged couples',
                'activity_type' => 'workshop',
                'start_datetime' => Carbon::now()->addDays(14)->setTime(9, 0),
                'end_datetime' => Carbon::now()->addDays(14)->setTime(16, 0),
                'location' => 'Conference Room',
                'organizer_id' => $admin->id,
                'group_id' => null,
                'budget' => 10000.00,
                'actual_cost' => null,
                'expected_participants' => 20,
                'actual_participants' => null,
                'status' => 'planned',
                'notes' => 'Pre-marriage counseling with lunch included',
                'is_recurring' => false,
                'recurrence_pattern' => null,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Women\'s Guild Monthly Meeting',
                'description' => 'Monthly women\'s fellowship and planning meeting',
                'activity_type' => 'meeting',
                'start_datetime' => Carbon::now()->addDays(10)->setTime(14, 0),
                'end_datetime' => Carbon::now()->addDays(10)->setTime(16, 0),
                'location' => 'Church Vestry',
                'organizer_id' => $admin->id,
                'group_id' => $groups->where('group_type', 'women')->first()?->id,
                'budget' => 3000.00,
                'actual_cost' => null,
                'expected_participants' => 25,
                'actual_participants' => null,
                'status' => 'planned',
                'notes' => 'Monthly meeting with tea service',
                'is_recurring' => true,
                'recurrence_pattern' => 'monthly',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('activities')->insert($activities);
    }
}