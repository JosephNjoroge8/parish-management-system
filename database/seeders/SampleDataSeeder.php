<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Family;
use App\Models\Member;
use App\Models\CommunityGroup;
use App\Models\Activity;
use App\Models\Sacrament;
use App\Models\BaptismRecord;
use App\Models\Tithe;
use App\Models\FamilyRelationship;
use Carbon\Carbon;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample families using updateOrCreate to handle duplicates
        $family1 = Family::updateOrCreate(
            ['family_code' => 'FAM001'],
            [
                'family_name' => 'The Kamau Family',
                'address' => 'Kiambu County, Thika Town',
                'phone' => '+254712345678',
                'email' => 'kamau.family@email.com',
                'deanery' => 'Thika Deanery',
                'parish' => 'St. Mary\'s Parish',
                'parish_section' => 'Central',
                'created_by' => 1,
            ]
        );

        $family2 = Family::updateOrCreate(
            ['family_code' => 'FAM002'],
            [
                'family_name' => 'The Wanjiku Family',
                'address' => 'Nairobi County, Kasarani',
                'phone' => '+254723456789',
                'email' => 'wanjiku.family@email.com',
                'deanery' => 'Nairobi Deanery',
                'parish' => 'St. Mary\'s Parish',
                'parish_section' => 'North',
                'created_by' => 1,
            ]
        );

        // Create sample members with auto-sync fields
        $fatherName = 'Mwangi Kamau';
        $motherName = 'Grace Wanjiku';
        $godparentName = 'James Mwangi';
        $ministerName = 'Fr. Michael Johnson';
        
        $johnKamau = Member::create([
            'first_name' => 'John',
            'middle_name' => 'Mwangi',
            'last_name' => 'Kamau',
            'date_of_birth' => '1975-03-15',
            'gender' => 'Male',
            'id_number' => '12345678',
            'phone' => '+254712345678',
            'email' => 'john.kamau@email.com',
            'residence' => 'Thika Town, Kiambu County',
            'local_church' => 'St. Mary\'s Catholic Church',
            'small_christian_community' => 'Tumaini SCC',
            'church_group' => 'CMA',
            'membership_status' => 'active',
            'membership_date' => '2000-01-15',
            'baptism_date' => '1975-04-20',
            'confirmation_date' => '1988-05-15',
            'matrimony_status' => 'married',
            'marriage_type' => 'church',
            'occupation' => 'employed',
            'education_level' => 'degree',
            'family_id' => $family1->id,
            'tribe' => 'Kikuyu',
            'clan' => 'Anjiru',
            'is_differently_abled' => false,
            // Main family fields (entered once)
            'parent' => $fatherName, // Father's name (main field)
            'mother_name' => $motherName, // Mother's name (main field)
            'godparent' => $godparentName, // Godparent name (main field)
            'minister' => $ministerName, // Minister name (main field)
            // Auto-synced fields (populated automatically)
            'father_name' => $fatherName, // Auto-synced from 'parent'
            'baptized_by' => $ministerName, // Auto-synced from 'minister'
            'sponsor' => $godparentName, // Auto-synced from 'godparent'
            'baptism_location' => 'St. Mary\'s Catholic Church',
        ]);

        $maryKamau = Member::create([
            'first_name' => 'Mary',
            'middle_name' => 'Wanjiku',
            'last_name' => 'Kamau',
            'date_of_birth' => '1978-07-22',
            'gender' => 'Female',
            'id_number' => '23456789',
            'phone' => '+254712345679',
            'email' => 'mary.kamau@email.com',
            'residence' => 'Thika Town, Kiambu County',
            'local_church' => 'St. Mary\'s Catholic Church',
            'small_christian_community' => 'Tumaini SCC',
            'church_group' => 'C.W.A',
            'membership_status' => 'active',
            'membership_date' => '2000-01-15',
            'baptism_date' => '1978-08-15',
            'confirmation_date' => '1991-06-10',
            'matrimony_status' => 'married',
            'marriage_type' => 'church',
            'occupation' => 'employed',
            'education_level' => 'diploma',
            'family_id' => $family1->id,
            'tribe' => 'Kikuyu',
            'clan' => 'Acheera',
            'is_differently_abled' => false,
            // Main family fields
            'parent' => 'Peter Wanjiku',
            'mother_name' => 'Alice Wanjiku',
            'godparent' => 'Elizabeth Wanjiru',
            'minister' => 'Fr. Paul Mbugua',
            // Auto-synced fields
            'father_name' => 'Peter Wanjiku',
            'baptized_by' => 'Fr. Paul Mbugua',
            'sponsor' => 'Elizabeth Wanjiru',
            'baptism_location' => 'St. Mary\'s Catholic Church',
            // Marriage information
            'marriage_date' => '2002-06-15',
            'marriage_location' => 'St. Mary\'s Catholic Church',
            'marriage_county' => 'Kiambu',
            'marriage_sub_county' => 'Thika',
            'marriage_religion' => 'Catholic',
            'marriage_officiant_name' => 'Fr. Michael Johnson',
            'spouse_name' => 'John Mwangi Kamau',
            'spouse_age' => 27,
            'spouse_residence' => 'Thika Town',
            'spouse_county' => 'Kiambu',
            'spouse_marital_status' => 'Single',
            'spouse_occupation' => 'Teacher',
            'spouse_father_name' => $fatherName,
            'spouse_mother_name' => $motherName,
        ]);

        $peterKamau = Member::create([
            'first_name' => 'Peter',
            'middle_name' => 'Mwangi',
            'last_name' => 'Kamau',
            'date_of_birth' => '2005-09-10',
            'gender' => 'Male',
            'phone' => '+254712345680',
            'residence' => 'Thika Town, Kiambu County',
            'local_church' => 'St. Mary\'s Catholic Church',
            'small_christian_community' => 'Tumaini SCC',
            'church_group' => 'Youth',
            'membership_status' => 'active',
            'membership_date' => '2005-10-01',
            'baptism_date' => '2005-10-15',
            'confirmation_date' => '2018-04-22',
            'matrimony_status' => 'single',
            'occupation' => 'not_employed',
            'education_level' => 'secondary',
            'family_id' => $family1->id,
            'parent_id' => $johnKamau->id,
            'tribe' => 'Kikuyu',
            'clan' => 'Anjiru',
            'is_differently_abled' => false,
            // Main family fields
            'parent' => 'John Mwangi Kamau',
            'mother_name' => 'Mary Wanjiku Kamau',
            'godparent' => 'James Mwangi',
            'minister' => 'Fr. Michael Johnson',
            // Auto-synced fields
            'father_name' => 'John Mwangi Kamau',
            'baptized_by' => 'Fr. Michael Johnson',
            'sponsor' => 'James Mwangi',
            'baptism_location' => 'St. Mary\'s Catholic Church',
            'confirmation_location' => 'St. Mary\'s Catholic Church',
        ]);

        // Update family head
        $family1->update(['head_of_family_id' => $johnKamau->id]);

        // Create family relationships
        FamilyRelationship::create([
            'family_id' => $family1->id,
            'member_id' => $johnKamau->id,
            'relationship_type' => 'head',
            'primary_contact' => true,
            'emergency_contact' => true,
        ]);

        FamilyRelationship::create([
            'family_id' => $family1->id,
            'member_id' => $maryKamau->id,
            'relationship_type' => 'spouse',
            'primary_contact' => true,
            'emergency_contact' => true,
        ]);

        FamilyRelationship::create([
            'family_id' => $family1->id,
            'member_id' => $peterKamau->id,
            'relationship_type' => 'child',
            'primary_contact' => false,
            'emergency_contact' => false,
        ]);

        // Create community groups
        $youthGroup = CommunityGroup::create([
            'name' => 'St. Mary\'s Youth Group',
            'description' => 'Active youth ministry group focusing on spiritual growth and community service',
            'group_type' => 'youth',
            'leader_id' => $peterKamau->id,
            'meeting_day' => 'saturday',
            'meeting_time' => '14:00:00',
            'meeting_location' => 'Parish Hall',
            'is_active' => true,
            'created_by' => 1,
        ]);

        $cwaGroup = CommunityGroup::create([
            'name' => 'Catholic Women Association',
            'description' => 'Women\'s fellowship and development group',
            'group_type' => 'women',
            'leader_id' => $maryKamau->id,
            'meeting_day' => 'tuesday',
            'meeting_time' => '15:00:00',
            'meeting_location' => 'Church Hall',
            'is_active' => true,
            'created_by' => 1,
        ]);

        $cmaGroup = CommunityGroup::create([
            'name' => 'Catholic Men Association',
            'description' => 'Men\'s fellowship and parish development group',
            'group_type' => 'men',
            'leader_id' => $johnKamau->id,
            'meeting_day' => 'sunday',
            'meeting_time' => '16:00:00',
            'meeting_location' => 'Parish Boardroom',
            'is_active' => true,
            'created_by' => 1,
        ]);

        // Create sample activities
        Activity::create([
            'title' => 'Sunday Mass - English',
            'description' => 'English mass service for the parish community',
            'activity_type' => 'mass',
            'start_date' => Carbon::now()->next(Carbon::SUNDAY),
            'start_time' => '09:00:00',
            'end_time' => '10:30:00',
            'location' => 'Main Church',
            'organizer' => 'Father John Smith',
            'community_group_id' => null,
            'registration_required' => false,
            'status' => 'planned',
        ]);

        Activity::create([
            'title' => 'Youth Fellowship Meeting',
            'description' => 'Monthly youth fellowship and planning meeting',
            'activity_type' => 'meeting',
            'start_date' => Carbon::now()->addDays(7),
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'location' => 'Parish Hall',
            'organizer' => 'Peter Kamau',
            'community_group_id' => $youthGroup->id,
            'max_participants' => 50,
            'registration_required' => true,
            'registration_deadline' => Carbon::now()->addDays(5),
            'status' => 'planned',
        ]);

        Activity::create([
            'title' => 'Parish Annual Retreat',
            'description' => 'Three-day spiritual retreat for all parishioners',
            'activity_type' => 'retreat',
            'start_date' => Carbon::now()->addDays(30),
            'end_date' => Carbon::now()->addDays(32),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'location' => 'Retreat Center - Limuru',
            'organizer' => 'Father John Smith',
            'max_participants' => 100,
            'registration_required' => true,
            'registration_deadline' => Carbon::now()->addDays(20),
            'status' => 'planned',
        ]);

        // Create sacrament records
        $baptismSacrament = Sacrament::create([
            'member_id' => $peterKamau->id,
            'sacrament_type' => 'baptism',
            'sacrament_date' => '2005-10-15',
            'location' => 'St. Mary\'s Catholic Church',
            'celebrant' => 'Father Michael Johnson',
            'witness_1' => 'John Kamau',
            'witness_2' => 'Mary Kamau',
            'godparent_1' => 'James Mwangi',
            'godparent_2' => 'Grace Wanjiku',
            'certificate_number' => 'BAP-2005-00001',
            'book_number' => 'Book 3',
            'page_number' => '45',
            'recorded_by' => 1,
        ]);

        $confirmationSacrament = Sacrament::create([
            'member_id' => $peterKamau->id,
            'sacrament_type' => 'confirmation',
            'sacrament_date' => '2018-04-22',
            'location' => 'St. Mary\'s Catholic Church',
            'celebrant' => 'Bishop Anthony Muheria',
            'witness_1' => 'John Kamau',
            'witness_2' => 'Mary Kamau',
            'certificate_number' => 'CON-2018-00045',
            'book_number' => 'Book 7',
            'page_number' => '123',
            'recorded_by' => 1,
        ]);

        // Create baptism record
        BaptismRecord::create([
            'record_number' => 'BAP-2005-00001',
            'member_id' => $peterKamau->id,
            'father_name' => 'John Mwangi Kamau',
            'mother_name' => 'Mary Wanjiku Kamau',
            'tribe' => 'Kikuyu',
            'birth_village' => 'Thika',
            'county' => 'Kiambu',
            'birth_date' => '2005-09-10',
            'residence' => 'Thika Town, Kiambu County',
            'baptism_location' => 'St. Mary\'s Catholic Church',
            'baptism_date' => '2005-10-15',
            'baptized_by' => 'Father Michael Johnson',
            'sponsor' => 'James Mwangi & Grace Wanjiku',
            'confirmation_location' => 'St. Mary\'s Catholic Church',
            'confirmation_date' => '2018-04-22',
            'confirmation_register_number' => 'CR-2018-045',
            'confirmation_number' => 'CON-045',
            'baptism_sacrament_id' => $baptismSacrament->id,
            'confirmation_sacrament_id' => $confirmationSacrament->id,
        ]);

        // Create tithe records
        Tithe::create([
            'member_id' => $johnKamau->id,
            'amount' => 2000.00,
            'tithe_type' => 'tithe',
            'payment_method' => 'cash',
            'date_given' => Carbon::now()->subDays(7),
            'purpose' => 'Monthly Tithe - October 2024',
            'receipt_number' => 'TIT-2024-001',
            'recorded_by' => 1,
        ]);

        Tithe::create([
            'member_id' => $maryKamau->id,
            'amount' => 500.00,
            'tithe_type' => 'offering',
            'payment_method' => 'mobile_money',
            'date_given' => Carbon::now()->subDays(14),
            'purpose' => 'Sunday Offering',
            'receipt_number' => 'OFF-2024-045',
            'reference_number' => 'MPESA123456789',
            'recorded_by' => 1,
        ]);

        Tithe::create([
            'member_id' => $johnKamau->id,
            'amount' => 5000.00,
            'tithe_type' => 'project_contribution',
            'payment_method' => 'bank_transfer',
            'date_given' => Carbon::now()->subDays(30),
            'purpose' => 'New Church Building Fund',
            'receipt_number' => 'PRJ-2024-012',
            'reference_number' => 'BT20241015001',
            'recorded_by' => 1,
        ]);

        // Create more sample members for testing with auto-sync fields
        for ($i = 1; $i <= 10; $i++) {
            $gender = $i % 2 == 0 ? 'Female' : 'Male';
            $fatherName = 'Father' . $i . ' Lastname' . $i;
            $motherName = 'Mother' . $i . ' Lastname' . $i;
            $godparentName = 'Godparent' . $i . ' Name' . $i;
            $ministerName = 'Fr. Minister' . $i;
            
            $member = Member::create([
                'first_name' => 'Member' . $i,
                'middle_name' => 'Middle' . $i,
                'last_name' => 'Lastname' . $i,
                'date_of_birth' => Carbon::now()->subYears(rand(18, 65))->format('Y-m-d'),
                'gender' => $gender,
                'id_number' => '1234567' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'phone' => '+25470000' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'email' => 'member' . $i . '@parish.com',
                'residence' => 'Nairobi County, Area ' . $i,
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => $i <= 5 ? 'Tumaini SCC' : 'Upendo SCC',
                'church_group' => $this->getRandomChurchGroup($gender),
                'membership_status' => 'active',
                'membership_date' => Carbon::now()->subYears(rand(1, 10))->format('Y-m-d'),
                'baptism_date' => Carbon::now()->subYears(rand(15, 50))->format('Y-m-d'),
                'matrimony_status' => rand(0, 1) ? 'married' : 'single',
                'occupation' => $this->getRandomOccupationEnum(),
                'education_level' => $this->getRandomEducationLevel(),
                'tribe' => $this->getRandomTribe(),
                'clan' => 'Clan' . $i,
                'is_differently_abled' => rand(0, 10) == 0, // 10% chance
                'disability_description' => rand(0, 10) == 0 ? 'Sample disability description' : null,
                // Main family fields (entered once)
                'parent' => $fatherName,
                'mother_name' => $motherName,
                'godparent' => $godparentName,
                'minister' => $ministerName,
                // Auto-synced fields (populated automatically)
                'father_name' => $fatherName,
                'baptized_by' => $ministerName,
                'sponsor' => $godparentName,
                'baptism_location' => 'Sacred Heart Kandara',
            ]);

            // Create some random tithe records for these members
            if (rand(0, 1)) {
                Tithe::create([
                    'member_id' => $member->id,
                    'amount' => rand(500, 5000),
                    'tithe_type' => $this->getRandomTitheType(),
                    'payment_method' => $this->getRandomPaymentMethod(),
                    'date_given' => Carbon::now()->subDays(rand(1, 90))->format('Y-m-d'),
                    'purpose' => 'Monthly contribution',
                    'receipt_number' => 'REC-' . date('Y') . '-' . str_pad($i + 100, 3, '0', STR_PAD_LEFT),
                    'recorded_by' => 1,
                ]);
            }
        }
    }

    private function getRandomChurchGroup($gender)
    {
        $maleGroups = ['CMA', 'Youth', 'Choir', 'Catholic Action', 'Pioneer'];
        $femaleGroups = ['C.W.A', 'Youth', 'Choir', 'Catholic Action', 'Pioneer'];
        $groups = $gender === 'Male' ? $maleGroups : $femaleGroups;
        return $groups[array_rand($groups)];
    }

    private function getRandomOccupationEnum()
    {
        $occupations = ['employed', 'self_employed', 'not_employed'];
        return $occupations[array_rand($occupations)];
    }

    private function getRandomEducationLevel()
    {
        $levels = ['primary', 'kcpe', 'secondary', 'kcse', 'certificate', 'diploma', 'degree'];
        return $levels[array_rand($levels)];
    }

    private function getRandomTribe()
    {
        $tribes = ['Kikuyu', 'Luo', 'Luhya', 'Kalenjin', 'Kamba', 'Kisii', 'Meru', 'Mijikenda', 'Turkana', 'Maasai'];
        return $tribes[array_rand($tribes)];
    }

    private function getRandomTitheType()
    {
        $types = ['tithe', 'offering', 'special_collection', 'donation', 'thanksgiving'];
        return $types[array_rand($types)];
    }

    private function getRandomPaymentMethod()
    {
        $methods = ['cash', 'mobile_money', 'bank_transfer', 'check'];
        return $methods[array_rand($methods)];
    }
}