<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommunityGroup;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;

class CommunityGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();
        $members = Member::all();

        if ($members->isEmpty()) {
            $this->command->info('No members found. Please run MemberSeeder first.');
            return;
        }

        // Get adult members (18 and older) for leadership roles
        $adultMembers = $members->filter(function ($member) {
            return Carbon::parse($member->date_of_birth)->age >= 18;
        });

        $maleAdults = $adultMembers->where('gender', 'male');
        $femaleAdults = $adultMembers->where('gender', 'female');

        $groups = [
            [
                'name' => 'Parish Youth Group',
                'description' => 'Young adults fellowship, spiritual growth, and community service activities',
                'group_type' => 'youth',
                'leader_id' => $adultMembers->where('church_group', 'Youth')->first()?->id ?? $adultMembers->first()?->id,
                'meeting_day' => 'saturday',
                'meeting_time' => '15:00:00',
                'meeting_location' => 'Parish Hall',
                'status' => 'active', // Changed from is_active to status
                'created_by' => $admin ? $admin->id : null,
            ],
            [
                'name' => 'Catholic Women\'s Association (C.W.A)',
                'description' => 'Women\'s fellowship, community service, and spiritual development',
                'group_type' => 'women',
                'leader_id' => $femaleAdults->where('church_group', 'C.W.A')->first()?->id ?? $femaleAdults->first()?->id,
                'meeting_day' => 'wednesday',
                'meeting_time' => '14:00:00',
                'meeting_location' => 'Church Vestry',
                'status' => 'active',
                'created_by' => $admin ? $admin->id : null,
            ],
            [
                'name' => 'Catholic Men\'s Association (CMA)',
                'description' => 'Men\'s spiritual growth, brotherhood, and community leadership',
                'group_type' => 'men',
                'leader_id' => $maleAdults->where('church_group', 'CMA')->first()?->id ?? $maleAdults->first()?->id,
                'meeting_day' => 'sunday',
                'meeting_time' => '07:00:00',
                'meeting_location' => 'Parish Hall',
                'status' => 'active',
                'created_by' => $admin ? $admin->id : null,
            ],
            [
                'name' => 'Pontifical Mission Societies (PMC)',
                'description' => 'Children and youth mission education and activities',
                'group_type' => 'children',
                'leader_id' => $adultMembers->skip(1)->first()?->id,
                'meeting_day' => 'sunday',
                'meeting_time' => '09:00:00',
                'meeting_location' => 'Sunday School Room',
                'status' => 'active',
                'created_by' => $admin ? $admin->id : null,
            ],
            [
                'name' => 'Parish Choir',
                'description' => 'Music ministry, worship leadership, and liturgical music',
                'group_type' => 'choir',
                'leader_id' => $adultMembers->where('church_group', 'Choir')->first()?->id ?? $adultMembers->skip(2)->first()?->id,
                'meeting_day' => 'thursday',
                'meeting_time' => '18:30:00',
                'meeting_location' => 'Church',
                'status' => 'active',
                'created_by' => $admin ? $admin->id : null,
                'name' => 'Catholic Action',
                'description' => 'Social justice, community advocacy, and Catholic social teaching',
                'group_type' => 'ministry', // Changed from apostolate to ministry
                'leader_id' => $adultMembers->where('church_group', 'Catholic Action')->first()?->id ?? $adultMembers->skip(3)->first()?->id,
                'meeting_day' => 'tuesday',
                'meeting_time' => '18:00:00',
                'meeting_location' => 'Parish Office',
                'status' => 'active',
                'created_by' => $admin ? $admin->id : null,
            ],
            [
                'name' => 'Pioneer Total Abstinence Association',
                'description' => 'Promoting sobriety, self-discipline, and healthy living',
                'group_type' => 'ministry', // Changed from apostolate to ministry
                'leader_id' => $adultMembers->where('church_group', 'Pioneer')->first()?->id ?? $adultMembers->skip(4)->first()?->id,
                'meeting_day' => 'friday',
                'meeting_time' => '19:00:00',
                'meeting_location' => 'Parish Hall',
                'status' => 'active',
                'created_by' => $admin ? $admin->id : null,
            ],
        ];

        foreach ($groups as $group) {
            try {
                CommunityGroup::create($group);
            } catch (\Exception $e) {
                $this->command->error('Failed to create community group: ' . $e->getMessage());
            }
        }

        $this->command->info('Created ' . count($groups) . ' community groups.');
    }
}
