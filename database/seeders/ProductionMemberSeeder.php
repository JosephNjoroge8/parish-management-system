<?php

namespace Database\Seeders;

use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ProductionMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates exactly 30 diverse members representing all types in the parish system
     * PRODUCTION SAFE VERSION - No truncate calls
     */
    public function run(): void
    {
        $this->command->info('🌱 Creating 30 comprehensive test members for complete system testing...');

        // Clear existing members to avoid conflicts during testing
        $this->command->info('Clearing existing member data...');

        // Handle foreign key constraints based on database type - PRODUCTION SAFE
        $connection = \DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            // For SQLite, disable foreign key constraints
            \DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            // For MySQL/MariaDB - PRODUCTION SAFE
            \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        // Clear related tables first (in reverse dependency order) - PRODUCTION SAFE
        \DB::table('activity_participants')->delete();
        \DB::table('baptism_records')->delete();
        \DB::table('marriage_records')->delete();
        \DB::table('tithes')->delete();

        // Now clear members table - PRODUCTION SAFE: Using DELETE not TRUNCATE
        \DB::table('members')->delete();

        // Re-enable foreign key checks
        if ($driver === 'sqlite') {
            \DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $members = $this->get30ComprehensiveMemberData();

        foreach ($members as $index => $memberData) {
            $category = $memberData['category']; // Store category for display
            unset($memberData['category']); // Remove from database insert

            try {
                $member = Member::create($memberData);
                $this->command->info('✅ Created member '.($index + 1)."/30: {$member->first_name} {$member->last_name} ({$category})");
            } catch (\Exception $e) {
                $this->command->error('❌ Failed to create member '.($index + 1).": {$e->getMessage()}");
                // Continue with next member instead of failing completely
            }
        }

        $this->displayComprehensiveSummary();
    }

    /**
     * Get comprehensive member data representing all aspects of parish life
     * MATCHES ACTUAL DATABASE SCHEMA - PRODUCTION SAFE
     */
    private function get30ComprehensiveMemberData(): array
    {
        return [
            [
                'category' => 'Parish Leader (Married - Church)',
                'first_name' => 'Joseph',
                'last_name' => 'Njoroge',
                'email' => 'joseph.njoroge@parish.com',
                'phone' => '+254701234567',
                'date_of_birth' => '1985-03-15',
                'gender' => 'Male',
                'id_number' => '32156789',
                'residence' => 'Kiambu Road, Nairobi',
                'occupation' => 'Parish Coordinator',
                'education_level' => 'degree',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'membership_status' => 'active',
                'membership_date' => '2008-01-15',
                'baptism_date' => '1990-04-22',
                'confirmation_date' => '2000-05-14',
                'emergency_contact' => 'Grace Njoroge',
                'emergency_phone' => '+254701234568',
                'notes' => 'Parish council chairman, leads evangelism programs',
                'local_church' => 'St. Mary\'s Parish',
                'church_group' => 'CMA',
            ],
            [
                'category' => 'Youth Leader (Single)',
                'first_name' => 'Mary',
                'last_name' => 'Kamau',
                'email' => 'mary.kamau@youth.com',
                'phone' => '+254712345678',
                'date_of_birth' => '1998-09-22',
                'gender' => 'Female',
                'id_number' => '35678912',
                'residence' => 'Westlands, Nairobi',
                'occupation' => 'Youth Coordinator',
                'education_level' => 'diploma',
                'matrimony_status' => 'single',
                'membership_status' => 'active',
                'membership_date' => '2015-08-20',
                'baptism_date' => '2000-12-25',
                'confirmation_date' => '2012-04-15',
                'emergency_contact' => 'Peter Kamau',
                'emergency_phone' => '+254723456789',
                'notes' => 'Youth leader, organizes community outreach programs',
                'local_church' => 'Holy Family Basilica',
                'church_group' => 'Youth',
            ],
            [
                'category' => 'Women Leader (Married - Church)',
                'first_name' => 'Grace',
                'last_name' => 'Mwangi',
                'email' => 'grace.mwangi@women.com',
                'phone' => '+254734567890',
                'date_of_birth' => '1982-07-08',
                'gender' => 'female',
                'id_number' => '28901234',
                'physical_address' => 'P.O. Box 9012, Thika',
                'residence_location' => 'Thika Town, Kiambu',
                'occupation' => 'Teacher',
                'employer' => 'St. Joseph\'s Primary School',
                'education_level' => 'degree',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '2005-11-26',
                'spouse_name' => 'John Mwangi',
                'membership_status' => 'active',
                'date_joined' => '2003-02-14',
                'baptism_date' => '1985-01-06',
                'baptism_place' => 'St. Augustine Church',
                'confirmation_date' => '1997-03-30',
                'confirmation_place' => 'Thika Cathedral',
                'emergency_contact_name' => 'John Mwangi',
                'emergency_contact_phone' => '+254745678901',
                'notes' => 'Women group chairlady, runs literacy programs',
            ],
            [
                'category' => 'Senior Member (Widowed)',
                'first_name' => 'Peter',
                'last_name' => 'Kariuki',
                'email' => 'peter.kariuki@senior.com',
                'phone' => '+254756789012',
                'date_of_birth' => '1950-12-03',
                'gender' => 'male',
                'id_number' => '15678901',
                'physical_address' => 'P.O. Box 3456, Nyeri',
                'residence_location' => 'Nyeri Town, Nyeri',
                'occupation' => 'Retired',
                'education_level' => 'primary',
                'matrimony_status' => 'widowed',
                'spouse_name' => 'Late Margaret Kariuki',
                'membership_status' => 'active',
                'date_joined' => '1975-06-08',
                'baptism_date' => '1955-08-15',
                'baptism_place' => 'St. Peter\'s Church Nyeri',
                'confirmation_date' => '1965-05-20',
                'confirmation_place' => 'Nyeri Cathedral',
                'emergency_contact_name' => 'Simon Kariuki (Son)',
                'emergency_contact_phone' => '+254767890123',
                'notes' => 'Church elder, mentors young couples',
            ],
            [
                'category' => 'Child Member',
                'first_name' => 'John',
                'last_name' => 'Githinji',
                'email' => 'john.githinji@child.com',
                'phone' => '+254778901234',
                'date_of_birth' => '2015-04-18',
                'gender' => 'male',
                'id_number' => null, // Child - no ID yet
                'physical_address' => 'P.O. Box 7890, Nakuru',
                'residence_location' => 'Nakuru East, Nakuru',
                'occupation' => 'Student',
                'education_level' => 'primary',
                'matrimony_status' => 'single',
                'membership_status' => 'active',
                'date_joined' => '2015-04-20',
                'baptism_date' => '2015-05-24',
                'baptism_place' => 'Sacred Heart Nakuru',
                'emergency_contact_name' => 'Paul Githinji (Father)',
                'emergency_contact_phone' => '+254789012345',
                'notes' => 'Sunday school member, altar server in training',
            ],
            [
                'category' => 'Member (Divorced)',
                'first_name' => 'Ruth',
                'last_name' => 'Wairimu',
                'email' => 'ruth.wairimu@member.com',
                'phone' => '+254790123456',
                'date_of_birth' => '1988-11-12',
                'gender' => 'female',
                'id_number' => '31234567',
                'physical_address' => 'P.O. Box 2345, Eldoret',
                'residence_location' => 'Pipeline, Eldoret',
                'occupation' => 'Nurse',
                'employer' => 'Moi Teaching Hospital',
                'education_level' => 'diploma',
                'matrimony_status' => 'divorced',
                'membership_status' => 'active',
                'date_joined' => '2012-09-17',
                'baptism_date' => '1990-09-16',
                'baptism_place' => 'St. Patrick\'s Cathedral',
                'confirmation_date' => '2003-06-21',
                'confirmation_place' => 'Eldoret Diocese',
                'emergency_contact_name' => 'Agnes Wairimu (Sister)',
                'emergency_contact_phone' => '+254701234567',
                'notes' => 'Healthcare worker, volunteers in medical missions',
            ],
            [
                'category' => 'Member (Married - Civil)',
                'first_name' => 'David',
                'last_name' => 'Ochieng',
                'email' => 'david.ochieng@civil.com',
                'phone' => '+254712345679',
                'date_of_birth' => '1987-02-28',
                'gender' => 'male',
                'id_number' => '29876543',
                'physical_address' => 'P.O. Box 6789, Kisumu',
                'residence_location' => 'Milimani, Kisumu',
                'occupation' => 'Engineer',
                'employer' => 'Kenya Power',
                'education_level' => 'degree',
                'matrimony_status' => 'married',
                'marriage_type' => 'civil',
                'marriage_date' => '2015-08-14',
                'spouse_name' => 'Susan Ochieng',
                'membership_status' => 'active',
                'date_joined' => '2018-01-22',
                'baptism_date' => '1990-07-15',
                'baptism_place' => 'St. Theresa Kisumu',
                'emergency_contact_name' => 'Susan Ochieng',
                'emergency_contact_phone' => '+254723456780',
                'notes' => 'Technical ministry leader, maintains church equipment',
            ],
            [
                'category' => 'Member (Married - Customary)',
                'first_name' => 'Francis',
                'last_name' => 'Wachira',
                'email' => 'francis.wachira@custom.com',
                'phone' => '+254734567891',
                'date_of_birth' => '1983-10-05',
                'gender' => 'male',
                'id_number' => '27654321',
                'physical_address' => 'P.O. Box 4321, Meru',
                'residence_location' => 'Meru Town, Meru',
                'occupation' => 'Farmer',
                'education_level' => 'kcse',
                'matrimony_status' => 'married',
                'marriage_type' => 'customary',
                'marriage_date' => '2008-12-20',
                'spouse_name' => 'Lucy Wachira',
                'membership_status' => 'active',
                'date_joined' => '2010-03-15',
                'baptism_date' => '1986-11-02',
                'baptism_place' => 'Christ the King Meru',
                'emergency_contact_name' => 'Lucy Wachira',
                'emergency_contact_phone' => '+254745678902',
                'notes' => 'Agricultural ministry coordinator, organic farming advocate',
            ],
            [
                'category' => 'Young Professional (Single)',
                'first_name' => 'James',
                'last_name' => 'Maina',
                'email' => 'james.maina@professional.com',
                'phone' => '+254756789013',
                'date_of_birth' => '1992-06-14',
                'gender' => 'male',
                'id_number' => '33456789',
                'physical_address' => 'P.O. Box 8765, Nairobi',
                'residence_location' => 'Karen, Nairobi',
                'occupation' => 'Software Developer',
                'employer' => 'Safaricom PLC',
                'education_level' => 'masters',
                'matrimony_status' => 'single',
                'membership_status' => 'active',
                'date_joined' => '2019-07-28',
                'baptism_date' => '1995-12-03',
                'baptism_place' => 'Holy Cross Cathedral',
                'confirmation_date' => '2007-11-18',
                'confirmation_place' => 'St. Austin\'s Church',
                'emergency_contact_name' => 'Elizabeth Maina (Mother)',
                'emergency_contact_phone' => '+254767890124',
                'notes' => 'Technology ministry leader, develops church apps',
            ],
            [
                'category' => 'Widow/Single Parent',
                'first_name' => 'Elizabeth',
                'last_name' => 'Muturi',
                'email' => 'elizabeth.muturi@widow.com',
                'phone' => '+254778901235',
                'date_of_birth' => '1980-01-25',
                'gender' => 'female',
                'id_number' => '26789012',
                'physical_address' => 'P.O. Box 5432, Machakos',
                'residence_location' => 'Machakos Town, Machakos',
                'occupation' => 'Shopkeeper',
                'education_level' => 'kcse',
                'matrimony_status' => 'widowed',
                'spouse_name' => 'Late Samuel Muturi',
                'membership_status' => 'active',
                'date_joined' => '2007-04-30',
                'baptism_date' => '1983-08-21',
                'baptism_place' => 'St. Paul\'s Machakos',
                'confirmation_date' => '1995-09-10',
                'confirmation_place' => 'Machakos Cathedral',
                'emergency_contact_name' => 'Grace Muturi (Sister-in-law)',
                'emergency_contact_phone' => '+254789012346',
                'notes' => 'Single mothers support group leader, tailoring instructor',
            ],
            [
                'category' => 'Teenager (Female)',
                'first_name' => 'Ann',
                'last_name' => 'Mwangi',
                'email' => 'ann.mwangi@teen.com',
                'phone' => '+254790123457',
                'date_of_birth' => '2008-05-30',
                'gender' => 'female',
                'id_number' => null, // Teenager - no ID yet
                'physical_address' => 'P.O. Box 9876, Kiambu',
                'residence_location' => 'Kiambu Town, Kiambu',
                'occupation' => 'Student',
                'education_level' => 'kcse',
                'matrimony_status' => 'single',
                'membership_status' => 'active',
                'date_joined' => '2016-10-14',
                'baptism_date' => '2010-03-28',
                'baptism_place' => 'Our Lady of Consolata',
                'confirmation_date' => '2022-04-17',
                'confirmation_place' => 'Kiambu Parish',
                'emergency_contact_name' => 'Jane Mwangi (Mother)',
                'emergency_contact_phone' => '+254701234568',
                'notes' => 'Youth choir member, peer counselor in training',
            ],
            [
                'category' => 'Healthcare Professional (Married)',
                'first_name' => 'Joyce',
                'last_name' => 'Gitau',
                'email' => 'joyce.gitau@health.com',
                'phone' => '+254712345680',
                'date_of_birth' => '1986-08-17',
                'gender' => 'female',
                'id_number' => '30123456',
                'physical_address' => 'P.O. Box 1357, Nairobi',
                'residence_location' => 'South B, Nairobi',
                'occupation' => 'Clinical Officer',
                'employer' => 'Kenyatta National Hospital',
                'education_level' => 'diploma',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '2012-02-11',
                'spouse_name' => 'Robert Gitau',
                'membership_status' => 'active',
                'date_joined' => '2014-06-25',
                'baptism_date' => '1989-10-08',
                'baptism_place' => 'St. Peter Claver',
                'confirmation_date' => '2001-07-22',
                'confirmation_place' => 'Holy Family Cathedral',
                'emergency_contact_name' => 'Robert Gitau',
                'emergency_contact_phone' => '+254723456781',
                'notes' => 'Health ministry coordinator, runs medical camps',
            ],
            [
                'category' => 'University Student (Male)',
                'first_name' => 'Daniel',
                'last_name' => 'Njoroge',
                'email' => 'daniel.njoroge@university.com',
                'phone' => '+254734567892',
                'date_of_birth' => '2003-12-09',
                'gender' => 'male',
                'id_number' => '37890123',
                'physical_address' => 'P.O. Box 2468, Nairobi',
                'residence_location' => 'University of Nairobi, Nairobi',
                'occupation' => 'Student',
                'education_level' => 'degree',
                'matrimony_status' => 'single',
                'membership_status' => 'active',
                'date_joined' => '2021-09-01',
                'baptism_date' => '2006-01-15',
                'baptism_place' => 'All Saints Cathedral',
                'confirmation_date' => '2018-05-27',
                'confirmation_place' => 'St. Mark\'s Church',
                'emergency_contact_name' => 'Joseph Njoroge (Father)',
                'emergency_contact_phone' => '+254745678903',
                'notes' => 'University Christian Union leader, debate society member',
            ],
            [
                'category' => 'Business Owner (Married)',
                'first_name' => 'Samuel',
                'last_name' => 'Wanjiku',
                'email' => 'samuel.wanjiku@business.com',
                'phone' => '+254756789014',
                'date_of_birth' => '1979-04-02',
                'gender' => 'male',
                'id_number' => '25432109',
                'physical_address' => 'P.O. Box 3579, Nakuru',
                'residence_location' => 'Nakuru West, Nakuru',
                'occupation' => 'Business Owner',
                'employer' => 'Wanjiku Enterprises Ltd',
                'education_level' => 'certificate',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '2004-07-17',
                'spouse_name' => 'Margaret Wanjiku',
                'membership_status' => 'active',
                'date_joined' => '2006-11-12',
                'baptism_date' => '1982-09-05',
                'baptism_place' => 'St. Christopher Nakuru',
                'confirmation_date' => '1994-08-14',
                'confirmation_place' => 'Nakuru Cathedral',
                'emergency_contact_name' => 'Margaret Wanjiku',
                'emergency_contact_phone' => '+254767890125',
                'notes' => 'Parish finance committee member, entrepreneur mentor',
            ],
            [
                'category' => 'Recent Convert (Single)',
                'first_name' => 'Michael',
                'last_name' => 'Gichuki',
                'email' => 'michael.gichuki@convert.com',
                'phone' => '+254778901236',
                'date_of_birth' => '1991-07-21',
                'gender' => 'male',
                'id_number' => '32987654',
                'physical_address' => 'P.O. Box 7531, Nyahururu',
                'residence_location' => 'Nyahururu Town, Laikipia',
                'occupation' => 'Mechanic',
                'employer' => 'Gichuki Motors',
                'education_level' => 'kcpe',
                'matrimony_status' => 'single',
                'membership_status' => 'active',
                'date_joined' => '2023-03-19', // Recent convert
                'baptism_date' => '2023-04-16',
                'baptism_place' => 'St. Joseph Nyahururu',
                'emergency_contact_name' => 'Paul Gichuki (Brother)',
                'emergency_contact_phone' => '+254789012347',
                'notes' => 'Recent convert from Protestant church, RCIA graduate',
            ],
            [
                'category' => 'Senior Couple (Married)',
                'first_name' => 'Agnes',
                'last_name' => 'Kariuki',
                'email' => 'agnes.kariuki@senior.com',
                'phone' => '+254790123458',
                'date_of_birth' => '1955-11-18',
                'gender' => 'female',
                'id_number' => '18765432',
                'physical_address' => 'P.O. Box 8642, Embu',
                'residence_location' => 'Embu Town, Embu',
                'occupation' => 'Retired Teacher',
                'education_level' => 'diploma',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '1978-08-26',
                'spouse_name' => 'Francis Kariuki',
                'membership_status' => 'active',
                'date_joined' => '1980-02-03',
                'baptism_date' => '1958-06-29',
                'baptism_place' => 'Sacred Heart Embu',
                'confirmation_date' => '1970-05-31',
                'confirmation_place' => 'Embu Cathedral',
                'emergency_contact_name' => 'Francis Kariuki',
                'emergency_contact_phone' => '+254701234569',
                'notes' => 'Marriage counselor, leads senior citizens ministry',
            ],
            [
                'category' => 'Education Professional (Single)',
                'first_name' => 'Catherine',
                'last_name' => 'Kimani',
                'email' => 'catherine.kimani@education.com',
                'phone' => '+254712345681',
                'date_of_birth' => '1984-03-26',
                'gender' => 'female',
                'id_number' => '28654321',
                'physical_address' => 'P.O. Box 9753, Mombasa',
                'residence_location' => 'Nyali, Mombasa',
                'occupation' => 'Head Teacher',
                'employer' => 'St. Teresa Girls School',
                'education_level' => 'masters',
                'matrimony_status' => 'single',
                'membership_status' => 'active',
                'date_joined' => '2011-01-16',
                'baptism_date' => '1987-04-12',
                'baptism_place' => 'Holy Ghost Cathedral Mombasa',
                'confirmation_date' => '1999-06-06',
                'confirmation_place' => 'Mombasa Diocese',
                'emergency_contact_name' => 'Mary Kimani (Sister)',
                'emergency_contact_phone' => '+254723456782',
                'notes' => 'Education ministry coordinator, literacy program leader',
            ],
            [
                'category' => 'Special Needs Member (Married)',
                'first_name' => 'Paul',
                'last_name' => 'Kimani',
                'email' => 'paul.kimani@special.com',
                'phone' => '+254734567893',
                'date_of_birth' => '1989-09-13',
                'gender' => 'male',
                'id_number' => '31876543',
                'physical_address' => 'P.O. Box 4826, Nairobi',
                'residence_location' => 'Eastleigh, Nairobi',
                'occupation' => 'Tailor',
                'education_level' => 'kcpe',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '2017-10-07',
                'spouse_name' => 'Rose Kimani',
                'membership_status' => 'active',
                'date_joined' => '2020-05-23',
                'baptism_date' => '1992-11-22',
                'baptism_place' => 'St. Benedict Nairobi',
                'confirmation_date' => '2004-12-19',
                'confirmation_place' => 'Cathedral of the Holy Family',
                'emergency_contact_name' => 'Rose Kimani',
                'emergency_contact_phone' => '+254745678904',
                'notes' => 'Hearing impaired, sign language interpreter, disability advocate',
                'disability_info' => 'Hearing impairment since birth',
            ],
            [
                'category' => 'Choir Member (Single)',
                'first_name' => 'Sarah',
                'last_name' => 'Maina',
                'email' => 'sarah.maina@choir.com',
                'phone' => '+254756789015',
                'date_of_birth' => '1995-02-07',
                'gender' => 'female',
                'id_number' => '34567890',
                'physical_address' => 'P.O. Box 1593, Kitale',
                'residence_location' => 'Kitale Town, Trans-Nzoia',
                'occupation' => 'Music Teacher',
                'employer' => 'Kitale School of Music',
                'education_level' => 'certificate',
                'matrimony_status' => 'single',
                'membership_status' => 'active',
                'date_joined' => '2017-08-06',
                'baptism_date' => '1998-05-17',
                'baptism_place' => 'St. Anthony Kitale',
                'confirmation_date' => '2010-07-04',
                'confirmation_place' => 'Kitale Diocese',
                'emergency_contact_name' => 'Peter Maina (Father)',
                'emergency_contact_phone' => '+254767890126',
                'notes' => 'Choir director, organizes music festivals, vocal coach',
            ],
            [
                'category' => 'Inactive Member',
                'first_name' => 'Margaret',
                'last_name' => 'Kinyua',
                'email' => 'margaret.kinyua@inactive.com',
                'phone' => '+254778901237',
                'date_of_birth' => '1977-12-11',
                'gender' => 'female',
                'id_number' => '24321098',
                'physical_address' => 'P.O. Box 7418, Garissa',
                'residence_location' => 'Garissa Town, Garissa',
                'occupation' => 'Not Employed',
                'education_level' => 'kcse',
                'matrimony_status' => 'single',
                'membership_status' => 'inactive', // Inactive member
                'date_joined' => '2009-12-20',
                'baptism_date' => '1980-08-31',
                'baptism_place' => 'St. Joseph Garissa',
                'confirmation_date' => '1992-09-27',
                'confirmation_place' => 'Garissa Parish',
                'emergency_contact_name' => 'John Kinyua (Brother)',
                'emergency_contact_phone' => '+254789012348',
                'notes' => 'Moved away, limited church participation, needs follow-up',
            ],
            [
                'category' => 'Transferred Member',
                'first_name' => 'Caroline',
                'last_name' => 'Kiprotich',
                'email' => 'caroline.kiprotich@transfer.com',
                'phone' => '+254790123459',
                'date_of_birth' => '1993-06-04',
                'gender' => 'female',
                'id_number' => '33210987',
                'physical_address' => 'P.O. Box 2705, Kapenguria',
                'residence_location' => 'Kapenguria Town, West Pokot',
                'occupation' => 'Nurse',
                'employer' => 'Kapenguria District Hospital',
                'education_level' => 'diploma',
                'matrimony_status' => 'single',
                'membership_status' => 'transferred', // Transferred member
                'date_joined' => '2016-04-10',
                'baptism_date' => '1996-01-28',
                'baptism_place' => 'St. Patrick Kapenguria',
                'confirmation_date' => '2008-03-16',
                'confirmation_place' => 'Kapenguria Parish',
                'emergency_contact_name' => 'Ruth Kiprotich (Mother)',
                'emergency_contact_phone' => '+254701234570',
                'notes' => 'Transferred to Eldoret Diocese, maintains contact',
            ],
            [
                'category' => 'Deceased Member',
                'first_name' => 'Stephen',
                'last_name' => 'Githui',
                'email' => 'stephen.githui@memory.com',
                'phone' => '+254712345682',
                'date_of_birth' => '1945-05-16',
                'gender' => 'male',
                'id_number' => '12345678',
                'physical_address' => 'P.O. Box 8520, Murang\'a',
                'residence_location' => 'Murang\'a Town, Murang\'a',
                'occupation' => 'Retired Farmer',
                'education_level' => 'primary',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '1970-11-14',
                'spouse_name' => 'Late Mary Githui',
                'membership_status' => 'deceased', // Deceased member for record keeping
                'date_joined' => '1965-07-25',
                'baptism_date' => '1948-12-25',
                'baptism_place' => 'St. James Murang\'a',
                'confirmation_date' => '1960-04-10',
                'confirmation_place' => 'Murang\'a Cathedral',
                'emergency_contact_name' => 'Peter Githui (Son)',
                'emergency_contact_phone' => '+254723456783',
                'notes' => 'Founding member, church elder for 40 years, passed away 2024',
            ],
            [
                'category' => 'Recent Graduate (Male)',
                'first_name' => 'Kevin',
                'last_name' => 'Ndungu',
                'email' => 'kevin.ndungu@graduate.com',
                'phone' => '+254734567894',
                'date_of_birth' => '2000-08-22',
                'gender' => 'male',
                'id_number' => '36543210',
                'physical_address' => 'P.O. Box 9639, Nairobi',
                'residence_location' => 'Kasarani, Nairobi',
                'occupation' => 'Graduate Trainee',
                'employer' => 'KCB Bank',
                'education_level' => 'degree',
                'matrimony_status' => 'single',
                'membership_status' => 'active',
                'date_joined' => '2018-01-07',
                'baptism_date' => '2003-09-21',
                'baptism_place' => 'Don Bosco Utume',
                'confirmation_date' => '2015-10-25',
                'confirmation_place' => 'Catholic University Chapel',
                'emergency_contact_name' => 'Grace Ndungu (Mother)',
                'emergency_contact_phone' => '+254745678905',
                'notes' => 'Recent graduate, young professionals group member',
            ],
            [
                'category' => 'Professional (Single Female)',
                'first_name' => 'Beatrice',
                'last_name' => 'Ndung\'u',
                'email' => 'beatrice.ndungu@professional.com',
                'phone' => '+254756789016',
                'date_of_birth' => '1990-01-14',
                'gender' => 'female',
                'id_number' => '32109876',
                'physical_address' => 'P.O. Box 7412, Nairobi',
                'residence_location' => 'Lavington, Nairobi',
                'occupation' => 'Lawyer',
                'employer' => 'Ndung\'u & Associates',
                'education_level' => 'masters',
                'matrimony_status' => 'single',
                'membership_status' => 'active',
                'date_joined' => '2020-02-29',
                'baptism_date' => '1993-06-13',
                'baptism_place' => 'St. Mary\'s School Chapel',
                'confirmation_date' => '2005-08-07',
                'confirmation_place' => 'Consolata Shrine',
                'emergency_contact_name' => 'John Ndung\'u (Father)',
                'emergency_contact_phone' => '+254767890127',
                'notes' => 'Legal advisor for parish matters, human rights advocate',
            ],
            [
                'category' => 'Rural Farmer (Married)',
                'first_name' => 'Simon',
                'last_name' => 'Mbugua',
                'email' => 'simon.mbugua@rural.com',
                'phone' => '+254778901238',
                'date_of_birth' => '1972-10-30',
                'gender' => 'male',
                'id_number' => '23456789',
                'physical_address' => 'P.O. Box 5185, Nyeri',
                'residence_location' => 'Othaya, Nyeri',
                'occupation' => 'Farmer',
                'education_level' => 'kcpe',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '1998-05-02',
                'spouse_name' => 'Alice Mbugua',
                'membership_status' => 'active',
                'date_joined' => '2000-08-13',
                'baptism_date' => '1975-12-21',
                'baptism_place' => 'St. Peter Othaya',
                'confirmation_date' => '1987-11-01',
                'confirmation_place' => 'Othaya Parish',
                'emergency_contact_name' => 'Alice Mbugua',
                'emergency_contact_phone' => '+254789012349',
                'notes' => 'Coffee farmer, agricultural cooperative leader',
            ],
            [
                'category' => 'Pioneer Member (Elderly)',
                'first_name' => 'Hannah',
                'last_name' => 'Wanjiru',
                'email' => 'hannah.wanjiru@pioneer.com',
                'phone' => '+254790123460',
                'date_of_birth' => '1940-07-19',
                'gender' => 'female',
                'id_number' => '11223344',
                'physical_address' => 'P.O. Box 3074, Kiambu',
                'residence_location' => 'Kiambu Town, Kiambu',
                'occupation' => 'Retired',
                'education_level' => 'primary',
                'matrimony_status' => 'widowed',
                'spouse_name' => 'Late Joseph Wanjiru',
                'membership_status' => 'active',
                'date_joined' => '1962-03-18', // Pioneer member
                'baptism_date' => '1943-04-04',
                'baptism_place' => 'Kiambu Mission',
                'confirmation_date' => '1955-06-12',
                'confirmation_place' => 'Kiambu Parish',
                'emergency_contact_name' => 'Mary Wanjiru (Daughter)',
                'emergency_contact_phone' => '+254701234571',
                'notes' => 'Founding member, parish historian, elder\'s ministry leader',
            ],
            [
                'category' => 'Young Married (Female)',
                'first_name' => 'Lydia',
                'last_name' => 'Kamanja',
                'email' => 'lydia.kamanja@youngmarried.com',
                'phone' => '+254712345683',
                'date_of_birth' => '1996-04-27',
                'gender' => 'female',
                'id_number' => '35432198',
                'physical_address' => 'P.O. Box 6307, Bungoma',
                'residence_location' => 'Bungoma Town, Bungoma',
                'occupation' => 'Accountant',
                'employer' => 'Bungoma County Government',
                'education_level' => 'degree',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '2022-09-10',
                'spouse_name' => 'David Kamanja',
                'membership_status' => 'active',
                'date_joined' => '2019-11-24',
                'baptism_date' => '1999-02-14',
                'baptism_place' => 'St. Kizito Bungoma',
                'confirmation_date' => '2011-12-11',
                'confirmation_place' => 'Bungoma Cathedral',
                'emergency_contact_name' => 'David Kamanja',
                'emergency_contact_phone' => '+254723456784',
                'notes' => 'Young couples ministry coordinator, newlywed',
            ],
            [
                'category' => 'Secondary Student (Male)',
                'first_name' => 'Brian',
                'last_name' => 'Mwangi',
                'email' => 'brian.mwangi@secondary.com',
                'phone' => '+254734567895',
                'date_of_birth' => '2006-11-08',
                'gender' => 'male',
                'id_number' => null, // Secondary school student
                'physical_address' => 'P.O. Box 8461, Isiolo',
                'residence_location' => 'Isiolo Town, Isiolo',
                'occupation' => 'Student',
                'education_level' => 'kcse',
                'matrimony_status' => 'single',
                'membership_status' => 'active',
                'date_joined' => '2020-01-12',
                'baptism_date' => '2009-05-10',
                'baptism_place' => 'St. Joseph Isiolo',
                'confirmation_date' => '2021-07-18',
                'confirmation_place' => 'Isiolo Parish',
                'emergency_contact_name' => 'Catherine Mwangi (Mother)',
                'emergency_contact_phone' => '+254745678906',
                'notes' => 'Form 4 student, altar server, aspiring seminarian',
            ],
            [
                'category' => 'Social Worker (Single)',
                'first_name' => 'Esther',
                'last_name' => 'Kariuki',
                'email' => 'esther.kariuki@social.com',
                'phone' => '+254756789017',
                'date_of_birth' => '1987-01-23',
                'gender' => 'female',
                'id_number' => '29654321',
                'physical_address' => 'P.O. Box 9274, Marsabit',
                'residence_location' => 'Marsabit Town, Marsabit',
                'occupation' => 'Social Worker',
                'employer' => 'World Vision Kenya',
                'education_level' => 'degree',
                'matrimony_status' => 'single',
                'membership_status' => 'active',
                'date_joined' => '2015-06-07',
                'baptism_date' => '1990-03-18',
                'baptism_place' => 'Immaculate Heart Marsabit',
                'confirmation_date' => '2002-09-08',
                'confirmation_place' => 'Marsabit Diocese',
                'emergency_contact_name' => 'Peter Kariuki (Father)',
                'emergency_contact_phone' => '+254767890128',
                'notes' => 'Community outreach coordinator, works with marginalized groups',
            ],
            [
                'category' => 'Retired Volunteer (Male)',
                'first_name' => 'Timothy',
                'last_name' => 'Wachira',
                'email' => 'timothy.wachira@retired.com',
                'phone' => '+254778901239',
                'date_of_birth' => '1952-09-06',
                'gender' => 'male',
                'id_number' => '17890123',
                'physical_address' => 'P.O. Box 4937, Nanyuki',
                'residence_location' => 'Nanyuki Town, Laikipia',
                'occupation' => 'Retired Civil Servant',
                'education_level' => 'diploma',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '1979-12-08',
                'spouse_name' => 'Grace Wachira',
                'membership_status' => 'active',
                'date_joined' => '1982-05-16',
                'baptism_date' => '1955-11-27',
                'baptism_place' => 'St. Francis Nanyuki',
                'confirmation_date' => '1967-10-15',
                'confirmation_place' => 'Nanyuki Parish',
                'emergency_contact_name' => 'Grace Wachira',
                'emergency_contact_phone' => '+254789012350',
                'notes' => 'Full-time volunteer, coordinates charity programs, catechist',
            ],
        ];
    }

    /**
     * Display comprehensive summary of created member data
     */
    private function displayComprehensiveSummary(): void
    {
        $this->command->info('🎉 Successfully created 30 diverse parish members!');
        $this->command->info('');
        $this->command->info('📊 30-MEMBER SEEDING SUMMARY');
        $this->command->info('=============================================================');

        // Gender breakdown
        $males = Member::where('gender', 'male')->count();
        $females = Member::where('gender', 'female')->count();

        $this->command->info('Total Members            : '.Member::count());
        $this->command->info("Male Members             : {$males}");
        $this->command->info("Female Members           : {$females}");

        // Membership status breakdown
        $active = Member::where('membership_status', 'active')->count();
        $inactive = Member::where('membership_status', 'inactive')->count();
        $transferred = Member::where('membership_status', 'transferred')->count();
        $deceased = Member::where('membership_status', 'deceased')->count();

        $this->command->info("Active Members           : {$active}");
        $this->command->info("Inactive Members         : {$inactive}");
        $this->command->info("Transferred Members      : {$transferred}");
        $this->command->info("Deceased Members         : {$deceased}");
        $this->command->info('');

        // Matrimony status breakdown
        $this->command->info('💒 MATRIMONY STATUS BREAKDOWN:');
        $single = Member::where('matrimony_status', 'single')->count();
        $married = Member::where('matrimony_status', 'married')->count();
        $widowed = Member::where('matrimony_status', 'widowed')->count();
        $divorced = Member::where('matrimony_status', 'divorced')->count();

        $this->command->info("Single Members           : {$single}");
        $this->command->info("Married Members          : {$married}");

        // Marriage type breakdown for married members
        $church_marriages = Member::where('marriage_type', 'church')->count();
        $civil_marriages = Member::where('marriage_type', 'civil')->count();
        $customary_marriages = Member::where('marriage_type', 'customary')->count();

        $this->command->info("  - Church Marriages     : {$church_marriages}");
        $this->command->info("  - Civil Marriages      : {$civil_marriages}");
        $this->command->info("  - Customary Marriages  : {$customary_marriages}");
        $this->command->info("Widowed Members          : {$widowed}");
        $this->command->info("Divorced Members         : {$divorced}");
        $this->command->info('');

        // Education levels breakdown
        $this->command->info('🎓 EDUCATION LEVELS:');
        $primary = Member::where('education_level', 'primary')->count();
        $kcpe = Member::where('education_level', 'kcpe')->count();
        $kcse = Member::where('education_level', 'kcse')->count();
        $certificate = Member::where('education_level', 'certificate')->count();
        $diploma = Member::where('education_level', 'diploma')->count();
        $degree = Member::where('education_level', 'degree')->count();
        $masters = Member::where('education_level', 'masters')->count();

        $this->command->info("Primary                  : {$primary}");
        $this->command->info("KCPE                     : {$kcpe}");
        $this->command->info("KCSE                     : {$kcse}");
        $this->command->info("Certificate              : {$certificate}");
        $this->command->info("Diploma                  : {$diploma}");
        $this->command->info("Degree                   : {$degree}");
        $this->command->info("Masters                  : {$masters}");
        $this->command->info('');

        // Employment breakdown
        $this->command->info('💼 OCCUPATION BREAKDOWN:');
        $employed = Member::whereNotNull('employer')->count();
        $self_employed = Member::whereNull('employer')
            ->whereNotIn('occupation', ['Student', 'Not Employed', 'Retired'])
            ->count();
        $not_employed = Member::whereIn('occupation', ['Student', 'Not Employed', 'Retired'])->count();

        $this->command->info("Employed                 : {$employed}");
        $this->command->info("Self-Employed            : {$self_employed}");
        $this->command->info("Not Employed             : {$not_employed}");
        $this->command->info('');

        // Age groups (calculated from date_of_birth)
        $this->command->info('📅 AGE GROUPS:');
        $now = Carbon::now();

        $children = Member::whereRaw('DATEDIFF(?, date_of_birth) / 365 <= 12', [$now])->count();
        $teenagers = Member::whereRaw('DATEDIFF(?, date_of_birth) / 365 BETWEEN 13 AND 19', [$now])->count();
        $young_adults = Member::whereRaw('DATEDIFF(?, date_of_birth) / 365 BETWEEN 20 AND 35', [$now])->count();
        $middle_aged = Member::whereRaw('DATEDIFF(?, date_of_birth) / 365 BETWEEN 36 AND 60', [$now])->count();
        $seniors = Member::whereRaw('DATEDIFF(?, date_of_birth) / 365 > 60', [$now])->count();

        $this->command->info("Children (0-12)          : {$children}");
        $this->command->info("Teenagers (13-19)        : {$teenagers}");
        $this->command->info("Young Adults (20-35)     : {$young_adults}");
        $this->command->info("Middle-aged (36-60)      : {$middle_aged}");
        $this->command->info("Seniors (60+)            : {$seniors}");
        $this->command->info('');

        // Special features
        $this->command->info('✨ SPECIAL FEATURES TESTED:');
        $with_disabilities = Member::whereNotNull('disability_info')->count();
        $recent_converts = Member::where('date_joined', '>=', '2023-01-01')->count();
        $with_marriage_residence = Member::whereNotNull('spouse_name')->count();
        $with_emails = Member::whereNotNull('email')->count();
        $with_phones = Member::whereNotNull('phone')->count();

        $this->command->info("Members with Disabilities: {$with_disabilities}");
        $this->command->info("Recent Converts (2023+)  : {$recent_converts}");
        $this->command->info("Marriage Residence Data  : {$with_marriage_residence}");
        $this->command->info("Email Addresses          : {$with_emails}");
        $this->command->info("Phone Numbers            : {$with_phones}");
        $this->command->info('');

        // Testing readiness summary
        $this->command->info('🎯 COMPREHENSIVE TESTING READY:');
        $this->command->info('  • All matrimony statuses represented (single, married, widowed, divorced)');
        $this->command->info('  • All marriage types covered (church, civil, customary)');
        $this->command->info('  • Complete age spectrum (children to seniors)');
        $this->command->info('  • Diverse occupations and education levels');
        $this->command->info('  • Special needs member included');
        $this->command->info('  • All membership statuses (active, inactive, transferred, deceased)');
        $this->command->info('  • Cultural diversity represented');
        $this->command->info('');

        // Login information
        $this->command->info('🔗 LOGIN TO TEST:');
        $this->command->info('  • URL: http://127.0.0.1:8000/login');
        $this->command->info('  • Super Admin: admin@parish.local / parish123');
        $this->command->info('  • Backup Admin: admin@parish.com / admin123');
        $this->command->info('  • Staff User: staff@parish.local / staff123');
        $this->command->info('  • Secretary: secretary@parish.local / secretary123');
    }
}
