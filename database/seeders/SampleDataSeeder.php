<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\BaptismRecord;
use App\Models\CommunityGroup;
use App\Models\Family;
use App\Models\FamilyRelationship;
use App\Models\Member;
use App\Models\Sacrament;
use App\Models\Tithe;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample families using updateOrCreate to handle duplicates
        $family1 = Family::updateOrCreate(
            ['family_name' => 'The Kamau Family'],
            [
                'family_head' => 'John Mwangi Kamau',
                'family_address' => 'Kiambu County, Thika Town',
                'phone' => '+254712345678',
                'email' => 'kamau.family@email.com',
                'family_status' => 'active',
            ]
        );

        $family2 = Family::updateOrCreate(
            ['family_name' => 'The Wanjiku Family'],
            [
                'family_head' => 'Peter Wanjiku',
                'family_address' => 'Nairobi County, Kasarani',
                'phone' => '+254723456789',
                'email' => 'wanjiku.family@email.com',
                'family_status' => 'active',
            ]
        );

        // Create sample members with comprehensive fields
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
            'matrimony_status' => 'married',
            'marital_status' => 'married',
            'marriage_type' => 'church',
            'occupation' => 'Teacher',
            'education_level' => 'degree',
            'family_id' => $family1->id,
            'tribe' => 'Kikuyu',
            'clan' => 'Anjiru',
            'county' => 'Kiambu',
            'district' => 'Thika',
            'province' => 'Central',
            'birth_village' => 'Thika',
            'is_differently_abled' => false,
            // Parent information
            'parent' => $fatherName,
            'father_name' => $fatherName,
            'father_occupation' => 'Farmer',
            'father_residence' => 'Thika, Kiambu County',
            'mother_name' => $motherName,
            'mother_occupation' => 'Business Woman',
            'mother_residence' => 'Thika, Kiambu County',
            // Sacrament information
            'baptism_date' => '1975-04-20',
            'baptism_location' => 'St. Mary\'s Catholic Church',
            'baptized_by' => $ministerName,
            'sponsor' => $godparentName,
            'confirmation_date' => '1988-05-15',
            'confirmation_location' => 'St. Mary\'s Catholic Church',
            'confirmation_register_number' => 'CR-1988-045',
            'confirmation_number' => 'CON-045',
            'eucharist_date' => '1985-06-10',
            'eucharist_location' => 'St. Mary\'s Catholic Church',
            'godparent' => $godparentName,
            'godfather_name' => 'James Mwangi',
            'godmother_name' => 'Jane Wanjiku',
            'minister' => $ministerName,
            // Marriage information - John's perspective
            'marriage_date' => '2002-06-15',
            'marriage_location' => 'St. Mary\'s Catholic Church',
            'marriage_county' => 'Kiambu',
            'marriage_sub_county' => 'Thika',
            'marriage_entry_number' => 'MAR-2002-001-H', // H for Husband
            'marriage_certificate_number' => 'MC-2002-001-H',
            'marriage_religion' => 'Catholic',
            'marriage_license_number' => 'ML-2002-001',
            'marriage_officiant_name' => 'Fr. Michael Johnson',
            'marriage_witness1_name' => 'Peter Wanjiku',
            'marriage_witness2_name' => 'Alice Wanjiku',
            'member_marriage_residence' => 'Thika Town, Kiambu County',
            'spouse_name' => 'Mary Wanjiku Kamau',
            'spouse_age' => 25,
            'spouse_residence' => 'Thika Town',
            'spouse_county' => 'Kiambu',
            'spouse_marital_status' => 'Single',
            'spouse_occupation' => 'Nurse',
            'spouse_father_name' => 'Peter Wanjiku',
            'spouse_father_occupation' => 'Teacher',
            'spouse_father_residence' => 'Kasarani, Nairobi',
            'spouse_mother_name' => 'Alice Wanjiku',
            'spouse_mother_occupation' => 'Business',
            'spouse_mother_residence' => 'Kasarani, Nairobi',
            'spouse_tribe' => 'Kikuyu',
            'spouse_clan' => 'Acheera',
            'spouse_birth_place' => 'Nairobi',
            'spouse_domicile' => 'Thika',
            'spouse_baptized_at' => 'Holy Family Basilica',
            'spouse_baptism_date' => '1978-08-15',
            'spouse_parent_consent' => 'Yes',
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
            'matrimony_status' => 'married',
            'marital_status' => 'married',
            'marriage_type' => 'church',
            'occupation' => 'Nurse',
            'education_level' => 'diploma',
            'family_id' => $family1->id,
            'tribe' => 'Kikuyu',
            'clan' => 'Acheera',
            'county' => 'Nairobi',
            'district' => 'Kasarani',
            'province' => 'Nairobi',
            'birth_village' => 'Kasarani',
            'is_differently_abled' => false,
            // Parent information
            'parent' => 'Peter Wanjiku',
            'father_name' => 'Peter Wanjiku',
            'father_occupation' => 'Teacher',
            'father_residence' => 'Kasarani, Nairobi County',
            'mother_name' => 'Alice Wanjiku',
            'mother_occupation' => 'Business Woman',
            'mother_residence' => 'Kasarani, Nairobi County',
            // Sacrament information
            'baptism_date' => '1978-08-15',
            'baptism_location' => 'Holy Family Basilica',
            'baptized_by' => 'Fr. Paul Mbugua',
            'sponsor' => 'Elizabeth Wanjiru',
            'confirmation_date' => '1991-06-10',
            'confirmation_location' => 'Holy Family Basilica',
            'eucharist_date' => '1986-05-20',
            'eucharist_location' => 'Holy Family Basilica',
            'godparent' => 'Elizabeth Wanjiru',
            'godfather_name' => 'Paul Wanjiru',
            'godmother_name' => 'Elizabeth Wanjiru',
            'minister' => 'Fr. Paul Mbugua',
            // Marriage information - Mary's perspective
            'marriage_date' => '2002-06-15',
            'marriage_location' => 'St. Mary\'s Catholic Church',
            'marriage_county' => 'Kiambu',
            'marriage_sub_county' => 'Thika',
            'marriage_entry_number' => 'MAR-2002-001-W', // W for Wife
            'marriage_certificate_number' => 'MC-2002-001-W',
            'marriage_religion' => 'Catholic',
            'marriage_license_number' => 'ML-2002-001',
            'marriage_officiant_name' => 'Fr. Michael Johnson',
            'marriage_witness1_name' => 'James Mwangi',
            'marriage_witness2_name' => 'Grace Wanjiku',
            'member_marriage_residence' => 'Thika Town, Kiambu County',
            'spouse_name' => 'John Mwangi Kamau',
            'spouse_age' => 27,
            'spouse_residence' => 'Thika Town',
            'spouse_county' => 'Kiambu',
            'spouse_marital_status' => 'Single',
            'spouse_occupation' => 'Teacher',
            'spouse_father_name' => $fatherName,
            'spouse_father_occupation' => 'Farmer',
            'spouse_father_residence' => 'Thika, Kiambu County',
            'spouse_mother_name' => $motherName,
            'spouse_mother_occupation' => 'Business Woman',
            'spouse_mother_residence' => 'Thika, Kiambu County',
            'spouse_tribe' => 'Kikuyu',
            'spouse_clan' => 'Anjiru',
            'spouse_birth_place' => 'Thika',
            'spouse_domicile' => 'Thika',
            'spouse_baptized_at' => 'St. Mary\'s Catholic Church',
            'spouse_baptism_date' => '1975-04-20',
            'spouse_parent_consent' => 'Yes',
        ]);

        $peterKamau = Member::create([
            'first_name' => 'Peter',
            'middle_name' => 'Mwangi',
            'last_name' => 'Kamau',
            'date_of_birth' => '2005-09-10',
            'gender' => 'Male',
            'id_number' => '34567890',
            'phone' => '+254712345680',
            'residence' => 'Thika Town, Kiambu County',
            'local_church' => 'St. Mary\'s Catholic Church',
            'small_christian_community' => 'Tumaini SCC',
            'church_group' => 'Youth',
            'membership_status' => 'active',
            'membership_date' => '2005-10-01',
            'matrimony_status' => 'single',
            'marital_status' => 'single',
            'occupation' => 'Student',
            'education_level' => 'secondary',
            'family_id' => $family1->id,
            'parent_id' => $johnKamau->id,
            'tribe' => 'Kikuyu',
            'clan' => 'Anjiru',
            'county' => 'Kiambu',
            'district' => 'Thika',
            'province' => 'Central',
            'birth_village' => 'Thika',
            'is_differently_abled' => false,
            // Parent information
            'parent' => 'John Mwangi Kamau',
            'father_name' => 'John Mwangi Kamau',
            'father_occupation' => 'Teacher',
            'father_residence' => 'Thika Town, Kiambu County',
            'mother_name' => 'Mary Wanjiku Kamau',
            'mother_occupation' => 'Nurse',
            'mother_residence' => 'Thika Town, Kiambu County',
            // Sacrament information
            'baptism_date' => '2005-10-15',
            'baptism_location' => 'St. Mary\'s Catholic Church',
            'baptized_by' => 'Fr. Michael Johnson',
            'sponsor' => 'James Mwangi',
            'confirmation_date' => '2018-04-22',
            'confirmation_location' => 'St. Mary\'s Catholic Church',
            'confirmation_register_number' => 'CR-2018-046',
            'confirmation_number' => 'CON-003',
            'eucharist_date' => '2013-05-15',
            'eucharist_location' => 'St. Mary\'s Catholic Church',
            'godparent' => 'James Mwangi',
            'godfather_name' => 'James Mwangi',
            'godmother_name' => 'Grace Wanjiku',
            'minister' => 'Fr. Michael Johnson',
        ]);

        // Update family head
        $family1->update(['family_head' => 'John Mwangi Kamau']);

        // Create family relationships
        FamilyRelationship::create([
            'family_id' => $family1->id,
            'member_id' => $johnKamau->id,
            'relationship_type' => 'head',
            'is_primary' => true,
        ]);

        FamilyRelationship::create([
            'family_id' => $family1->id,
            'member_id' => $maryKamau->id,
            'relationship_type' => 'spouse',
            'is_primary' => true,
        ]);

        FamilyRelationship::create([
            'family_id' => $family1->id,
            'member_id' => $peterKamau->id,
            'relationship_type' => 'child',
            'is_primary' => false,
        ]);

        // Create community groups
        $youthGroup = CommunityGroup::create([
            'group_name' => 'St. Mary\'s Youth Group',
            'description' => 'Active youth ministry group focusing on spiritual growth and community service',
            'group_leader' => 'Peter Kamau',
            'contact_phone' => '+254712345680',
            'contact_email' => 'youth@parish.com',
            'meeting_day' => '2024-12-07', // Saturday
            'meeting_time' => '14:00:00',
            'meeting_location' => 'Parish Hall',
            'group_status' => 'active',
            'max_members' => 50,
        ]);

        $cwaGroup = CommunityGroup::create([
            'group_name' => 'Catholic Women Association',
            'description' => 'Women\'s fellowship and development group',
            'group_leader' => 'Mary Kamau',
            'contact_phone' => '+254712345679',
            'contact_email' => 'cwa@parish.com',
            'meeting_day' => '2024-12-03', // Tuesday
            'meeting_time' => '15:00:00',
            'meeting_location' => 'Church Hall',
            'group_status' => 'active',
            'max_members' => 100,
        ]);

        $cmaGroup = CommunityGroup::create([
            'group_name' => 'Catholic Men Association',
            'description' => 'Men\'s fellowship and parish development group',
            'group_leader' => 'John Kamau',
            'contact_phone' => '+254712345678',
            'contact_email' => 'cma@parish.com',
            'meeting_day' => '2024-12-08', // Sunday
            'meeting_time' => '16:00:00',
            'meeting_location' => 'Parish Boardroom',
            'group_status' => 'active',
            'max_members' => 80,
        ]);

        // Create sample activities
        Activity::create([
            'activity_name' => 'Sunday Mass - English',
            'description' => 'English mass service for the parish community',
            'activity_date' => Carbon::now()->next(Carbon::SUNDAY),
            'start_time' => '09:00:00',
            'end_time' => '10:30:00',
            'location' => 'Main Church',
            'organizer' => 'Father John Smith',
            'activity_type' => 'worship',
            'activity_status' => 'planned',
            'expected_participants' => 200,
        ]);

        Activity::create([
            'activity_name' => 'Youth Fellowship Meeting',
            'description' => 'Monthly youth fellowship and planning meeting',
            'activity_date' => Carbon::now()->addDays(7),
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'location' => 'Parish Hall',
            'organizer' => 'Peter Kamau',
            'activity_type' => 'meeting',
            'activity_status' => 'planned',
            'expected_participants' => 50,
            'budget' => 5000.00,
        ]);

        Activity::create([
            'activity_name' => 'Parish Annual Retreat',
            'description' => 'Three-day spiritual retreat for all parishioners',
            'activity_date' => Carbon::now()->addDays(30),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'location' => 'Retreat Center - Limuru',
            'organizer' => 'Father John Smith',
            'activity_type' => 'education',
            'activity_status' => 'planned',
            'expected_participants' => 100,
            'budget' => 50000.00,
        ]);

        // Create sacrament records
        $baptismSacrament = Sacrament::create([
            'member_id' => $peterKamau->id,
            'sacrament_type' => 'baptism',
            'sacrament_date' => '2005-10-15',
            'location' => 'St. Mary\'s Catholic Church',
            'officiant' => 'Father Michael Johnson',
            'register_number' => 'BAP-2005-00001',
        ]);

        $confirmationSacrament = Sacrament::create([
            'member_id' => $peterKamau->id,
            'sacrament_type' => 'confirmation',
            'sacrament_date' => '2018-04-22',
            'location' => 'St. Mary\'s Catholic Church',
            'officiant' => 'Bishop Anthony Muheria',
            'register_number' => 'CON-2018-00045',
        ]);

        // Create baptism record
        BaptismRecord::create([
            'member_id' => $peterKamau->id,
            'baptism_date' => '2005-10-15',
            'baptism_location' => 'St. Mary\'s Catholic Church',
            'baptized_by' => 'Father Michael Johnson',
            'father_name' => 'John Mwangi Kamau',
            'mother_name' => 'Mary Wanjiku Kamau',
            'sponsor' => 'James Mwangi & Grace Wanjiku',
            'register_number' => 'BAP-2005-001',
            'certificate_number' => 'BC-2005-001',
        ]);

        // Create tithe records
        Tithe::create([
            'member_id' => $johnKamau->id,
            'contributor_name' => 'John Mwangi Kamau',
            'amount' => 2000.00,
            'contribution_date' => Carbon::now()->subDays(7),
            'payment_method' => 'cash',
            'tithe_type' => 'regular',
            'reference_number' => 'TIT-2024-001',
        ]);

        Tithe::create([
            'member_id' => $maryKamau->id,
            'contributor_name' => 'Mary Wanjiku Kamau',
            'amount' => 500.00,
            'contribution_date' => Carbon::now()->subDays(14),
            'payment_method' => 'mpesa',
            'tithe_type' => 'thanksgiving',
            'reference_number' => 'MPESA123456789',
        ]);

        Tithe::create([
            'member_id' => $johnKamau->id,
            'contributor_name' => 'John Mwangi Kamau',
            'amount' => 5000.00,
            'contribution_date' => Carbon::now()->subDays(30),
            'payment_method' => 'bank_transfer',
            'tithe_type' => 'special',
            'reference_number' => 'BT20241015001',
        ]);

        // Create more sample members for testing
        for ($i = 1; $i <= 10; $i++) {
            $gender = $i % 2 == 0 ? 'Female' : 'Male';
            $fatherName = 'Father'.$i.' Lastname'.$i;
            $motherName = 'Mother'.$i.' Lastname'.$i;
            $godparentName = 'Godparent'.$i.' Name'.$i;
            $ministerName = 'Fr. Minister'.$i;

            $member = Member::create([
                'first_name' => 'Member'.$i,
                'middle_name' => 'Middle'.$i,
                'last_name' => 'Lastname'.$i,
                'date_of_birth' => Carbon::now()->subYears(rand(18, 65))->format('Y-m-d'),
                'gender' => $gender,
                'id_number' => '1234567'.str_pad($i, 2, '0', STR_PAD_LEFT),
                'phone' => '+25470000'.str_pad($i, 4, '0', STR_PAD_LEFT),
                'email' => 'member'.$i.'@parish.com',
                'residence' => 'Nairobi County, Area '.$i,
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => $i <= 5 ? 'Tumaini SCC' : 'Upendo SCC',
                'church_group' => $this->getRandomChurchGroup($gender),
                'membership_status' => 'active',
                'membership_date' => Carbon::now()->subYears(rand(1, 10))->format('Y-m-d'),
                'matrimony_status' => rand(0, 1) ? 'married' : 'single',
                'marital_status' => rand(0, 1) ? 'married' : 'single',
                'marriage_type' => rand(0, 1) ? 'church' : 'civil',
                'baptism_date' => Carbon::now()->subYears(rand(15, 50))->format('Y-m-d'),
                'occupation' => $this->getRandomOccupation(),
                'education_level' => $this->getRandomEducationLevel(),
                'tribe' => $this->getRandomTribe(),
                'clan' => 'Clan'.$i,
                'county' => 'County'.$i,
                'district' => 'District'.$i,
                'province' => 'Province'.$i,
                'birth_village' => 'Village'.$i,
                'is_differently_abled' => rand(0, 10) == 0, // 10% chance
                'disability_description' => rand(0, 10) == 0 ? 'Sample disability description' : null,
                // Parent information
                'parent' => $fatherName,
                'father_name' => $fatherName,
                'father_occupation' => $this->getRandomOccupation(),
                'father_residence' => 'Father Residence '.$i,
                'mother_name' => $motherName,
                'mother_occupation' => $this->getRandomOccupation(),
                'mother_residence' => 'Mother Residence '.$i,
                // Sacrament information
                'baptism_location' => 'Sacred Heart Kandara',
                'baptized_by' => $ministerName,
                'sponsor' => $godparentName,
                'godparent' => $godparentName,
                'minister' => $ministerName,
            ]);

            // Create some random tithe records for these members
            if (rand(0, 1)) {
                Tithe::create([
                    'member_id' => $member->id,
                    'contributor_name' => $member->first_name.' '.$member->last_name,
                    'amount' => rand(500, 5000),
                    'contribution_date' => Carbon::now()->subDays(rand(1, 90))->format('Y-m-d'),
                    'payment_method' => $this->getRandomPaymentMethod(),
                    'tithe_type' => $this->getRandomTitheType(),
                    'reference_number' => 'REC-'.date('Y').'-'.str_pad($i + 100, 3, '0', STR_PAD_LEFT),
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

    private function getRandomOccupation()
    {
        $occupations = ['Teacher', 'Nurse', 'Engineer', 'Farmer', 'Business Owner', 'Doctor', 'Lawyer', 'Accountant', 'Student', 'Retired'];

        return $occupations[array_rand($occupations)];
    }

    private function getRandomEducationLevel()
    {
        $levels = ['primary', 'kcpe', 'secondary', 'kcse', 'certificate', 'diploma', 'degree', 'masters', 'phd'];

        return $levels[array_rand($levels)];
    }

    private function getRandomTribe()
    {
        $tribes = ['Kikuyu', 'Luo', 'Luhya', 'Kalenjin', 'Kamba', 'Kisii', 'Meru', 'Mijikenda', 'Turkana', 'Maasai'];

        return $tribes[array_rand($tribes)];
    }

    private function getRandomTitheType()
    {
        $types = ['regular', 'thanksgiving', 'special', 'pledge'];

        return $types[array_rand($types)];
    }

    private function getRandomPaymentMethod()
    {
        $methods = ['cash', 'mpesa', 'bank_transfer', 'cheque'];

        return $methods[array_rand($methods)];
    }
}
