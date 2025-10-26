<?php

namespace Database\Seeders;

use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class Enhanced30MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates exactly 30 diverse members representing all types in the parish system
     */
    public function run(): void
    {
        $this->command->info('🌱 Creating 30 comprehensive test members for complete system testing...');

        // Clear existing members to avoid conflicts during testing
        $this->command->info('Clearing existing member data...');

        // Handle foreign key constraints based on database type
        $connection = \DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            // For SQLite, disable foreign key constraints
            \DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            // For MySQL/MariaDB
            \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        // Clear related tables first (in reverse dependency order)
        \DB::table('activity_participants')->delete();
        \DB::table('baptism_records')->delete();
        \DB::table('marriage_records')->delete();
        \DB::table('tithes')->delete();

        // Now clear members table
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
            }
        }

        $this->command->info('🎉 Successfully created 30 diverse parish members!');
        $this->displaySummary();
    }

    /**
     * Get exactly 30 comprehensive member data representing all system types
     */
    private function get30ComprehensiveMemberData(): array
    {
        return [
            // 1. Parish Leader - Married Male (Church Marriage)
            [
                'category' => 'Parish Leader (Married - Church)',
                'first_name' => 'Joseph',
                'middle_name' => 'Wanjiku',
                'last_name' => 'Njoroge',
                'date_of_birth' => '1975-03-15',
                'gender' => 'Male',
                'id_number' => '12345678',
                'phone' => '0722123456',
                'email' => 'joseph.njoroge@gmail.com',
                'residence' => 'Kandara, Muranga',
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => 'St. Joseph',
                'church_group' => 'CMA',
                'membership_status' => 'active',
                'membership_date' => '1995-01-15',
                'baptism_date' => '1975-04-20',
                'confirmation_date' => '1988-05-12',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '2000-12-25',
                'marriage_location' => 'Sacred Heart Kandara',
                'marriage_county' => 'Muranga',
                'marriage_sub_county' => 'Kandara',
                'member_marriage_residence' => 'Kandara, Muranga County',
                'occupation' => 'employed',
                'education_level' => 'degree',
                'tribe' => 'Kikuyu',
                'clan' => 'Anjiru',
                'father_name' => 'Samuel Njoroge Kamau',
                'mother_name' => 'Mary Wanjiku Njoroge',
                'sponsor' => 'Peter Mwangi Kariuki',
                'minister' => 'Fr. Francis Githinji',
                'notes' => 'Parish council chairman and catechist leader',
            ],

            // 2. Single Female Youth Leader
            [
                'category' => 'Youth Leader (Single)',
                'first_name' => 'Mary',
                'middle_name' => 'Njeri',
                'last_name' => 'Kamau',
                'date_of_birth' => '1998-07-22',
                'gender' => 'Female',
                'id_number' => '34567890',
                'phone' => '0733456789',
                'email' => 'mary.kamau@yahoo.com',
                'residence' => 'Thika, Kiambu',
                'local_church' => 'St. Joseph Thika',
                'small_christian_community' => 'St. Mary',
                'church_group' => 'Youth',
                'membership_status' => 'active',
                'membership_date' => '2015-03-10',
                'baptism_date' => '1998-08-15',
                'confirmation_date' => '2012-04-28',
                'matrimony_status' => 'single',
                'occupation' => 'employed',
                'education_level' => 'degree',
                'tribe' => 'Kikuyu',
                'clan' => 'Agachiku',
                'father_name' => 'David Kamau Mwangi',
                'mother_name' => 'Jane Wanjiru Kamau',
                'sponsor' => 'Catherine Nyokabi',
                'minister' => 'Fr. Michael Kiarie',
                'notes' => 'Youth group chairperson and university graduate',
            ],

            // 3. Married Female (Church Marriage) - Women's Group Leader
            [
                'category' => 'Women Leader (Married - Church)',
                'first_name' => 'Grace',
                'middle_name' => 'Wangari',
                'last_name' => 'Mwangi',
                'date_of_birth' => '1980-11-08',
                'gender' => 'Female',
                'id_number' => '23456789',
                'phone' => '0711234567',
                'email' => 'grace.mwangi@hotmail.com',
                'residence' => 'Nyeri Town',
                'local_church' => 'St. Peter Nyeri',
                'small_christian_community' => 'St. Anne',
                'church_group' => 'C.W.A',
                'membership_status' => 'active',
                'membership_date' => '1997-06-20',
                'baptism_date' => '1980-12-25',
                'confirmation_date' => '1993-05-15',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '2005-08-12',
                'marriage_location' => 'St. Peter Nyeri',
                'marriage_county' => 'Nyeri',
                'marriage_sub_county' => 'Nyeri Central',
                'member_marriage_residence' => 'Nyeri Town, Nyeri County',
                'occupation' => 'self_employed',
                'education_level' => 'diploma',
                'tribe' => 'Kikuyu',
                'clan' => 'Ethaga',
                'father_name' => 'Paul Mwangi Githinji',
                'mother_name' => 'Ruth Nyawira Mwangi',
                'sponsor' => 'Agnes Wambui',
                'minister' => 'Fr. Daniel Muturi',
                'notes' => 'C.W.A chairperson and small business owner',
            ],

            // 4. Widowed Male - Senior Member
            [
                'category' => 'Senior Member (Widowed)',
                'first_name' => 'Peter',
                'middle_name' => 'Kimani',
                'last_name' => 'Kariuki',
                'date_of_birth' => '1950-02-18',
                'gender' => 'Male',
                'id_number' => '11111111',
                'phone' => '0700111111',
                'email' => null,
                'residence' => 'Kerugoya, Kirinyaga',
                'local_church' => 'Holy Family Kiambu',
                'small_christian_community' => 'St. Peter',
                'church_group' => 'Pioneer',
                'membership_status' => 'active',
                'membership_date' => '1970-01-01',
                'baptism_date' => '1950-03-25',
                'confirmation_date' => '1965-04-10',
                'matrimony_status' => 'widowed',
                'marriage_type' => 'church',
                'occupation' => 'not_employed',
                'education_level' => 'primary',
                'tribe' => 'Kikuyu',
                'clan' => 'Anjiru',
                'father_name' => 'Kariuki wa Kimani',
                'mother_name' => 'Wanjiku wa Kariuki',
                'sponsor' => 'Unknown',
                'minister' => 'Fr. Unknown',
                'notes' => 'Founding member and elder of the parish',
            ],

            // 5. Child Member - Male
            [
                'category' => 'Child Member',
                'first_name' => 'John',
                'middle_name' => 'Muturi',
                'last_name' => 'Githinji',
                'date_of_birth' => '2015-05-12',
                'gender' => 'Male',
                'id_number' => null,
                'phone' => null,
                'email' => null,
                'residence' => 'Kandara, Muranga',
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => 'St. John',
                'church_group' => 'PMC',
                'membership_status' => 'active',
                'membership_date' => '2015-06-01',
                'baptism_date' => '2015-06-14',
                'matrimony_status' => 'single',
                'occupation' => 'not_employed',
                'education_level' => 'primary',
                'tribe' => 'Kikuyu',
                'clan' => 'Agachiku',
                'father_name' => 'Samuel Githinji Muturi',
                'mother_name' => 'Elizabeth Wanjiru Githinji',
                'sponsor' => 'Joseph Njoroge Wanjiku',
                'minister' => 'Fr. Francis Githinji',
                'notes' => 'Active in children ministry and Sunday school',
            ],

            // 6. Divorced Female
            [
                'category' => 'Member (Divorced)',
                'first_name' => 'Ruth',
                'middle_name' => 'Nyawira',
                'last_name' => 'Wairimu',
                'date_of_birth' => '1985-09-30',
                'gender' => 'Female',
                'id_number' => '98765432',
                'phone' => '0722987654',
                'email' => 'ruth.wairimu@gmail.com',
                'residence' => 'Nairobi, Kenya',
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => 'St. Ruth',
                'church_group' => 'C.W.A',
                'membership_status' => 'active',
                'membership_date' => '2005-01-15',
                'baptism_date' => '1985-10-31',
                'confirmation_date' => '1998-03-25',
                'matrimony_status' => 'divorced',
                'marriage_type' => 'civil',
                'occupation' => 'employed',
                'education_level' => 'masters',
                'tribe' => 'Kikuyu',
                'clan' => 'Ethaga',
                'father_name' => 'Michael Wairimu Kimani',
                'mother_name' => 'Joyce Wangui Wairimu',
                'sponsor' => 'Sarah Njoki',
                'minister' => 'Fr. Paul Kariuki',
                'notes' => 'Working professional, single mother',
            ],

            // 7. Married Male (Civil Marriage)
            [
                'category' => 'Member (Married - Civil)',
                'first_name' => 'David',
                'middle_name' => 'Otieno',
                'last_name' => 'Ochieng',
                'date_of_birth' => '1978-08-14',
                'gender' => 'Male',
                'id_number' => '67890123',
                'phone' => '0777345678',
                'email' => 'david.ochieng@gmail.com',
                'residence' => 'Kandara, Muranga',
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => 'St. David',
                'church_group' => 'CMA',
                'membership_status' => 'active',
                'membership_date' => '2005-09-10',
                'baptism_date' => '1978-09-17',
                'confirmation_date' => '1991-06-02',
                'matrimony_status' => 'married',
                'marriage_type' => 'civil',
                'occupation' => 'self_employed',
                'education_level' => 'certificate',
                'tribe' => 'Luo',
                'clan' => 'Kogelo',
                'father_name' => 'Peter Ochieng Otieno',
                'mother_name' => 'Grace Achieng Ochieng',
                'sponsor' => 'Joseph Njoroge Wanjiku',
                'minister' => 'Fr. Francis Githinji',
                'notes' => 'Business owner and cultural bridge in the community',
            ],

            // 8. Married Male (Customary Marriage)
            [
                'category' => 'Member (Married - Customary)',
                'first_name' => 'Francis',
                'middle_name' => 'Muturi',
                'last_name' => 'Wachira',
                'date_of_birth' => '1970-06-20',
                'gender' => 'Male',
                'id_number' => '89012345',
                'phone' => '0799567890',
                'email' => null,
                'residence' => 'Kandara Rural, Muranga',
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => 'St. Francis',
                'church_group' => 'Farmers Group',
                'membership_status' => 'active',
                'membership_date' => '1988-12-25',
                'baptism_date' => '1970-07-25',
                'confirmation_date' => '1983-05-29',
                'matrimony_status' => 'married',
                'marriage_type' => 'customary',
                'occupation' => 'self_employed',
                'education_level' => 'kcpe',
                'tribe' => 'Kikuyu',
                'clan' => 'Anjiru',
                'father_name' => 'Wachira wa Muturi',
                'mother_name' => 'Nyawira wa Wachira',
                'sponsor' => 'Peter Kariuki',
                'minister' => 'Fr. John Kamau',
                'notes' => 'Farmer and agricultural ministry volunteer',
            ],

            // 9. Single Male Professional
            [
                'category' => 'Young Professional (Single)',
                'first_name' => 'James',
                'middle_name' => 'Kariuki',
                'last_name' => 'Maina',
                'date_of_birth' => '1995-10-08',
                'gender' => 'Male',
                'id_number' => '90123456',
                'phone' => '0710678901',
                'email' => 'james.maina@tech.com',
                'residence' => 'Nairobi, Kenya',
                'local_church' => 'St. Joseph Thika',
                'small_christian_community' => 'St. James',
                'church_group' => 'Youth',
                'membership_status' => 'active',
                'membership_date' => '2013-01-20',
                'baptism_date' => '1995-11-12',
                'confirmation_date' => '2008-04-27',
                'matrimony_status' => 'single',
                'occupation' => 'employed',
                'education_level' => 'degree',
                'tribe' => 'Kikuyu',
                'clan' => 'Agachiku',
                'father_name' => 'John Maina Kariuki',
                'mother_name' => 'Ann Wangui Maina',
                'sponsor' => 'David Kamau',
                'minister' => 'Fr. Michael Kiarie',
                'notes' => 'Software engineer, tech ministry coordinator',
            ],

            // 10. Widowed Female
            [
                'category' => 'Widow/Single Parent',
                'first_name' => 'Elizabeth',
                'middle_name' => 'Nyokabi',
                'last_name' => 'Muturi',
                'date_of_birth' => '1965-12-15',
                'gender' => 'Female',
                'id_number' => '01234567',
                'phone' => '0721789012',
                'email' => null,
                'residence' => 'Kandara, Muranga',
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => 'St. Elizabeth',
                'church_group' => 'C.W.A',
                'membership_status' => 'active',
                'membership_date' => '1985-05-14',
                'baptism_date' => '1966-01-18',
                'confirmation_date' => '1979-03-31',
                'matrimony_status' => 'widowed',
                'marriage_type' => 'church',
                'occupation' => 'self_employed',
                'education_level' => 'kcse',
                'tribe' => 'Kikuyu',
                'clan' => 'Ethaga',
                'father_name' => 'Samuel Muturi Githinji',
                'mother_name' => 'Grace Wanjiku Muturi',
                'sponsor' => 'Margaret Wangari',
                'minister' => 'Fr. Paul Kariuki',
                'notes' => 'Widow, small business owner, active in widows ministry',
            ],

            // 11. Teenage Member - Female
            [
                'category' => 'Teenager (Female)',
                'first_name' => 'Ann',
                'middle_name' => 'Wangui',
                'last_name' => 'Mwangi',
                'date_of_birth' => '2008-06-18',
                'gender' => 'Female',
                'id_number' => null,
                'phone' => '0787345678',
                'email' => null,
                'residence' => 'Kandara, Muranga',
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => 'St. Ann',
                'church_group' => 'Youth',
                'membership_status' => 'active',
                'membership_date' => '2008-07-01',
                'baptism_date' => '2008-07-22',
                'confirmation_date' => '2022-05-10',
                'matrimony_status' => 'single',
                'occupation' => 'not_employed',
                'education_level' => 'kcse',
                'tribe' => 'Kikuyu',
                'clan' => 'Agachiku',
                'father_name' => 'James Kariuki Mwangi',
                'mother_name' => 'Grace Wangari Mwangi',
                'sponsor' => 'Catherine Nyokabi',
                'minister' => 'Fr. Francis Githinji',
                'notes' => 'High school student, youth group active member',
            ],

            // 12. Married Female (Church Marriage) - Nurse
            [
                'category' => 'Healthcare Professional (Married)',
                'first_name' => 'Joyce',
                'middle_name' => 'Wanjiru',
                'last_name' => 'Gitau',
                'date_of_birth' => '1987-07-05',
                'gender' => 'Female',
                'id_number' => '23456098',
                'phone' => '0754012345',
                'email' => 'joyce.gitau@health.go.ke',
                'residence' => 'Thika, Kiambu',
                'local_church' => 'St. Joseph Thika',
                'small_christian_community' => 'St. Joyce',
                'church_group' => 'Health Ministry',
                'membership_status' => 'active',
                'membership_date' => '2005-02-20',
                'baptism_date' => '1987-08-08',
                'confirmation_date' => '2000-05-14',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '2012-08-18',
                'marriage_location' => 'St. Joseph Thika',
                'marriage_county' => 'Kiambu',
                'marriage_sub_county' => 'Thika',
                'member_marriage_residence' => 'Thika Town, Kiambu County',
                'occupation' => 'employed',
                'education_level' => 'diploma',
                'tribe' => 'Kikuyu',
                'clan' => 'Agachiku',
                'father_name' => 'Paul Gitau Maina',
                'mother_name' => 'Mary Wangui Gitau',
                'sponsor' => 'Ruth Nyawira',
                'minister' => 'Fr. Michael Kiarie',
                'notes' => 'Parish health coordinator and nursing professional',
            ],

            // 13. Single Male Student
            [
                'category' => 'University Student (Male)',
                'first_name' => 'Daniel',
                'middle_name' => 'Wachira',
                'last_name' => 'Njoroge',
                'date_of_birth' => '2003-09-12',
                'gender' => 'Male',
                'id_number' => null,
                'phone' => '0743901234',
                'email' => 'daniel.njoroge@student.ac.ke',
                'residence' => 'Kandara, Muranga',
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => 'St. Daniel',
                'church_group' => 'Youth',
                'membership_status' => 'active',
                'membership_date' => '2003-10-15',
                'baptism_date' => '2003-10-19',
                'confirmation_date' => '2017-04-24',
                'matrimony_status' => 'single',
                'occupation' => 'not_employed',
                'education_level' => 'degree',
                'tribe' => 'Kikuyu',
                'clan' => 'Anjiru',
                'father_name' => 'Joseph Njoroge Wanjiku',
                'mother_name' => 'Grace Wangari Njoroge',
                'sponsor' => 'Peter Kariuki',
                'minister' => 'Fr. Francis Githinji',
                'notes' => 'University student, altar server, youth leader',
            ],

            // 14. Married Male Business Owner
            [
                'category' => 'Business Owner (Married)',
                'first_name' => 'Samuel',
                'middle_name' => 'Kimani',
                'last_name' => 'Wanjiku',
                'date_of_birth' => '1972-11-22',
                'gender' => 'Male',
                'id_number' => '34567098',
                'phone' => '0765123456',
                'email' => 'samuel.wanjiku@business.co.ke',
                'residence' => 'Kiambu Town',
                'local_church' => 'Holy Family Kiambu',
                'small_christian_community' => 'St. Samuel',
                'church_group' => 'CMA',
                'membership_status' => 'active',
                'membership_date' => '1990-04-08',
                'baptism_date' => '1972-12-25',
                'confirmation_date' => '1985-06-16',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '1998-01-17',
                'marriage_location' => 'Holy Family Kiambu',
                'marriage_county' => 'Kiambu',
                'marriage_sub_county' => 'Kiambu',
                'member_marriage_residence' => 'Kiambu Town, Kiambu County',
                'occupation' => 'self_employed',
                'education_level' => 'certificate',
                'tribe' => 'Kikuyu',
                'clan' => 'Ethaga',
                'father_name' => 'Wanjiku wa Kimani',
                'mother_name' => 'Nyawira wa Wanjiku',
                'sponsor' => 'John Mwangi',
                'minister' => 'Fr. Joseph Muturi',
                'notes' => 'Hardware store owner and parish finance committee member',
            ],

            // 15. Single Male Recent Convert
            [
                'category' => 'Recent Convert (Single)',
                'first_name' => 'Michael',
                'middle_name' => 'Kuria',
                'last_name' => 'Gichuki',
                'date_of_birth' => '1980-04-10',
                'gender' => 'Male',
                'id_number' => '45678098',
                'phone' => '0776234567',
                'email' => 'michael.gichuki@gmail.com',
                'residence' => 'Kandara, Muranga',
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => 'St. Michael',
                'church_group' => 'Catechumenate',
                'membership_status' => 'active',
                'membership_date' => '2023-01-15',
                'baptism_date' => '2023-04-16',
                'matrimony_status' => 'single',
                'occupation' => 'employed',
                'education_level' => 'certificate',
                'tribe' => 'Kikuyu',
                'clan' => 'Anjiru',
                'father_name' => 'Gichuki wa Kuria',
                'mother_name' => 'Wanjiku wa Gichuki',
                'sponsor' => 'Joseph Njoroge Wanjiku',
                'minister' => 'Fr. Francis Githinji',
                'notes' => 'Recent adult convert, completed RCIA program in 2023',
            ],

            // 16. Senior Married Female
            [
                'category' => 'Senior Couple (Married)',
                'first_name' => 'Agnes',
                'middle_name' => 'Wambui',
                'last_name' => 'Kariuki',
                'date_of_birth' => '1955-08-30',
                'gender' => 'Female',
                'id_number' => '67890098',
                'phone' => '0798456789',
                'email' => null,
                'residence' => 'Kandara, Muranga',
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => 'St. Agnes',
                'church_group' => 'C.W.A',
                'membership_status' => 'active',
                'membership_date' => '1975-12-08',
                'baptism_date' => '1955-09-30',
                'confirmation_date' => '1969-05-25',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '1975-11-29',
                'marriage_location' => 'Sacred Heart Kandara',
                'marriage_county' => 'Muranga',
                'marriage_sub_county' => 'Kandara',
                'member_marriage_residence' => 'Kandara Village, Muranga County',
                'occupation' => 'not_employed',
                'education_level' => 'primary',
                'tribe' => 'Kikuyu',
                'clan' => 'Ethaga',
                'father_name' => 'Kariuki wa Wambui',
                'mother_name' => 'Nyawira wa Kariuki',
                'sponsor' => 'Unknown',
                'minister' => 'Fr. Paul Kariuki',
                'notes' => 'Golden jubilee couple, married 50 years, founding member',
            ],

            // 17. Single Female Teacher
            [
                'category' => 'Education Professional (Single)',
                'first_name' => 'Catherine',
                'middle_name' => 'Wangui',
                'last_name' => 'Kimani',
                'date_of_birth' => '1990-03-28',
                'gender' => 'Female',
                'id_number' => '12345098',
                'phone' => '0732890123',
                'email' => 'catherine.kimani@education.go.ke',
                'residence' => 'Nyeri, Nyeri',
                'local_church' => 'St. Peter Nyeri',
                'small_christian_community' => 'St. Catherine',
                'church_group' => 'C.W.A',
                'membership_status' => 'active',
                'membership_date' => '2008-08-15',
                'baptism_date' => '1990-04-30',
                'confirmation_date' => '2003-05-12',
                'matrimony_status' => 'single',
                'occupation' => 'employed',
                'education_level' => 'masters',
                'tribe' => 'Kikuyu',
                'clan' => 'Anjiru',
                'father_name' => 'Francis Kimani Wachira',
                'mother_name' => 'Joyce Nyokabi Kimani',
                'sponsor' => 'Sarah Wanjiku',
                'minister' => 'Fr. Daniel Muturi',
                'notes' => 'Primary school teacher and education ministry coordinator',
            ],

            // 18. Married Male with Disability
            [
                'category' => 'Special Needs Member (Married)',
                'first_name' => 'Paul',
                'middle_name' => 'Wachira',
                'last_name' => 'Kimani',
                'date_of_birth' => '1985-12-03',
                'gender' => 'Male',
                'id_number' => '45678901',
                'phone' => '0755123456',
                'email' => null,
                'residence' => 'Thika, Kiambu',
                'local_church' => 'St. Joseph Thika',
                'small_christian_community' => 'St. Paul',
                'church_group' => 'Special Group',
                'membership_status' => 'active',
                'membership_date' => '2008-07-20',
                'baptism_date' => '1986-01-06',
                'confirmation_date' => '1998-05-18',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '2015-06-20',
                'marriage_location' => 'St. Joseph Thika',
                'marriage_county' => 'Kiambu',
                'marriage_sub_county' => 'Thika',
                'member_marriage_residence' => 'Thika Town, Kiambu County',
                'occupation' => 'self_employed',
                'education_level' => 'kcse',
                'tribe' => 'Kikuyu',
                'clan' => 'Anjiru',
                'father_name' => 'Francis Kimani Wachira',
                'mother_name' => 'Catherine Nyokabi Kimani',
                'sponsor' => 'James Muturi',
                'minister' => 'Fr. Michael Kiarie',
                'is_differently_abled' => true,
                'disability_description' => 'Mobility impairment - uses wheelchair',
                'notes' => 'Active in disability ministry and handcraft group',
            ],

            // 19. Single Female Choir Member
            [
                'category' => 'Choir Member (Single)',
                'first_name' => 'Sarah',
                'middle_name' => 'Wanjiku',
                'last_name' => 'Maina',
                'date_of_birth' => '1985-04-16',
                'gender' => 'Female',
                'id_number' => '56789012',
                'phone' => '0766234567',
                'email' => 'sarah.maina@music.com',
                'residence' => 'Nyeri, Nyeri',
                'local_church' => 'St. Peter Nyeri',
                'small_christian_community' => 'St. Cecilia',
                'church_group' => 'Choir',
                'membership_status' => 'active',
                'membership_date' => '2003-02-14',
                'baptism_date' => '1985-05-19',
                'confirmation_date' => '1998-04-12',
                'matrimony_status' => 'single',
                'occupation' => 'employed',
                'education_level' => 'diploma',
                'tribe' => 'Kikuyu',
                'clan' => 'Agachiku',
                'father_name' => 'John Maina Kariuki',
                'mother_name' => 'Ann Wangui Maina',
                'sponsor' => 'Mary Njoki',
                'minister' => 'Fr. Daniel Muturi',
                'notes' => 'Choir director and music teacher',
            ],

            // 20. Inactive Member
            [
                'category' => 'Inactive Member',
                'first_name' => 'Margaret',
                'middle_name' => 'Wanjiru',
                'last_name' => 'Kinyua',
                'date_of_birth' => '1988-01-25',
                'gender' => 'Female',
                'id_number' => '78901234',
                'phone' => '0788456789',
                'email' => 'margaret.kinyua@yahoo.com',
                'residence' => 'Mombasa, Coast',
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => 'St. Margaret',
                'church_group' => 'Youth',
                'membership_status' => 'inactive',
                'membership_date' => '2006-03-12',
                'baptism_date' => '1988-02-28',
                'confirmation_date' => '2001-04-15',
                'matrimony_status' => 'single',
                'occupation' => 'not_employed',
                'education_level' => 'kcse',
                'tribe' => 'Kikuyu',
                'clan' => 'Ethaga',
                'father_name' => 'Simon Kinyua Muturi',
                'mother_name' => 'Rose Wangui Kinyua',
                'sponsor' => 'Grace Wangari',
                'minister' => 'Fr. Francis Githinji',
                'notes' => 'Moved to coast for work, lost contact with parish',
            ],

            // 21. Transferred Member
            [
                'category' => 'Transferred Member',
                'first_name' => 'Caroline',
                'middle_name' => 'Njeri',
                'last_name' => 'Kiprotich',
                'date_of_birth' => '1992-03-14',
                'gender' => 'Female',
                'id_number' => '87654321',
                'phone' => '0745123789',
                'email' => 'caroline.kiprotich@gmail.com',
                'residence' => 'Eldoret, Uasin Gishu',
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => 'St. Caroline',
                'church_group' => 'Youth',
                'membership_status' => 'transferred',
                'membership_date' => '2010-05-20',
                'baptism_date' => '1992-04-18',
                'confirmation_date' => '2005-06-12',
                'matrimony_status' => 'single',
                'occupation' => 'employed',
                'education_level' => 'degree',
                'tribe' => 'Kalenjin',
                'clan' => 'Kapsowar',
                'father_name' => 'David Kiprotich Kiptoo',
                'mother_name' => 'Mary Cheptoo Kiprotich',
                'sponsor' => 'Grace Wangari',
                'minister' => 'Fr. Francis Githinji',
                'notes' => 'Transferred to St. Teresa Eldoret for work',
            ],

            // 22. Deceased Member (for testing system)
            [
                'category' => 'Deceased Member',
                'first_name' => 'Stephen',
                'middle_name' => 'Mwangi',
                'last_name' => 'Githui',
                'date_of_birth' => '1930-01-05',
                'gender' => 'Male',
                'id_number' => '55555555',
                'phone' => null,
                'email' => null,
                'residence' => 'Kandara, Muranga',
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => 'St. Stephen',
                'church_group' => 'Pioneer',
                'membership_status' => 'deceased',
                'membership_date' => '1950-01-01',
                'baptism_date' => '1930-02-10',
                'confirmation_date' => '1945-05-15',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'occupation' => 'not_employed',
                'education_level' => 'primary',
                'tribe' => 'Kikuyu',
                'clan' => 'Anjiru',
                'father_name' => 'Githui wa Mwangi',
                'mother_name' => 'Wanjiku wa Githui',
                'sponsor' => 'Unknown',
                'minister' => 'Fr. Unknown',
                'notes' => 'Founding member, passed away 2020, memorial record',
            ],

            // 23. Young Adult Male - Recent Graduate
            [
                'category' => 'Recent Graduate (Male)',
                'first_name' => 'Kevin',
                'middle_name' => 'Kamau',
                'last_name' => 'Ndungu',
                'date_of_birth' => '2000-08-25',
                'gender' => 'Male',
                'id_number' => '40028756',
                'phone' => '0712456789',
                'email' => 'kevin.ndungu@graduate.ac.ke',
                'residence' => 'Nairobi, Kenya',
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => 'St. Kevin',
                'church_group' => 'Youth',
                'membership_status' => 'active',
                'membership_date' => '2018-01-10',
                'baptism_date' => '2000-09-30',
                'confirmation_date' => '2014-04-20',
                'matrimony_status' => 'single',
                'occupation' => 'employed',
                'education_level' => 'degree',
                'tribe' => 'Kikuyu',
                'clan' => 'Agachiku',
                'father_name' => 'Samuel Ndungu Kamau',
                'mother_name' => 'Jane Wanjiru Ndungu',
                'sponsor' => 'Joseph Njoroge',
                'minister' => 'Fr. Francis Githinji',
                'notes' => 'Recent engineering graduate, youth technology coordinator',
            ],

            // 24. Middle-aged Single Female Professional
            [
                'category' => 'Professional (Single Female)',
                'first_name' => 'Beatrice',
                'middle_name' => 'Wairimu',
                'last_name' => 'Ndung\'u',
                'date_of_birth' => '1982-11-12',
                'gender' => 'Female',
                'id_number' => '26789345',
                'phone' => '0723567890',
                'email' => 'beatrice.ndungu@legal.co.ke',
                'residence' => 'Kiambu, Kiambu',
                'local_church' => 'Holy Family Kiambu',
                'small_christian_community' => 'St. Beatrice',
                'church_group' => 'Catholic Action',
                'membership_status' => 'active',
                'membership_date' => '2000-03-15',
                'baptism_date' => '1982-12-25',
                'confirmation_date' => '1995-05-28',
                'matrimony_status' => 'single',
                'occupation' => 'employed',
                'education_level' => 'masters',
                'tribe' => 'Kikuyu',
                'clan' => 'Ethaga',
                'father_name' => 'Peter Ndung\'u Wairimu',
                'mother_name' => 'Grace Nyokabi Ndung\'u',
                'sponsor' => 'Margaret Wangari',
                'minister' => 'Fr. Joseph Muturi',
                'notes' => 'Lawyer, legal aid ministry coordinator',
            ],

            // 25. Married Couple - Husband (Farmer)
            [
                'category' => 'Rural Farmer (Married)',
                'first_name' => 'Simon',
                'middle_name' => 'Waweru',
                'last_name' => 'Mbugua',
                'date_of_birth' => '1968-02-28',
                'gender' => 'Male',
                'id_number' => '18901234',
                'phone' => '0734890123',
                'email' => null,
                'residence' => 'Kandara Rural, Muranga',
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => 'St. Simon',
                'church_group' => 'Farmers Group',
                'membership_status' => 'active',
                'membership_date' => '1986-01-20',
                'baptism_date' => '1968-03-31',
                'confirmation_date' => '1981-06-14',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '1992-11-28',
                'marriage_location' => 'Sacred Heart Kandara',
                'marriage_county' => 'Muranga',
                'marriage_sub_county' => 'Kandara',
                'member_marriage_residence' => 'Kandara Rural, Muranga County',
                'occupation' => 'self_employed',
                'education_level' => 'kcpe',
                'tribe' => 'Kikuyu',
                'clan' => 'Anjiru',
                'father_name' => 'Mbugua wa Waweru',
                'mother_name' => 'Nyawira wa Mbugua',
                'sponsor' => 'Francis Wachira',
                'minister' => 'Fr. John Kamau',
                'notes' => 'Coffee farmer, agricultural cooperative leader',
            ],

            // 26. Elderly Female - Pioneer Member
            [
                'category' => 'Pioneer Member (Elderly)',
                'first_name' => 'Hannah',
                'middle_name' => 'Njoki',
                'last_name' => 'Wanjiru',
                'date_of_birth' => '1945-07-15',
                'gender' => 'Female',
                'id_number' => '09876543',
                'phone' => '0756789012',
                'email' => null,
                'residence' => 'Kandara, Muranga',
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => 'St. Hannah',
                'church_group' => 'Pioneer',
                'membership_status' => 'active',
                'membership_date' => '1965-08-15',
                'baptism_date' => '1945-08-20',
                'confirmation_date' => '1960-05-22',
                'matrimony_status' => 'widowed',
                'marriage_type' => 'church',
                'occupation' => 'not_employed',
                'education_level' => 'primary',
                'tribe' => 'Kikuyu',
                'clan' => 'Ethaga',
                'father_name' => 'Wanjiru wa Njoki',
                'mother_name' => 'Wangari wa Wanjiru',
                'sponsor' => 'Unknown',
                'minister' => 'Fr. Paul Kariuki',
                'notes' => 'Pioneer member, church construction contributor',
            ],

            // 27. Young Married Couple - Wife
            [
                'category' => 'Young Married (Female)',
                'first_name' => 'Lydia',
                'middle_name' => 'Wangui',
                'last_name' => 'Kamanja',
                'date_of_birth' => '1996-05-08',
                'gender' => 'Female',
                'id_number' => '36789012',
                'phone' => '0767890123',
                'email' => 'lydia.kamanja@gmail.com',
                'residence' => 'Thika, Kiambu',
                'local_church' => 'St. Joseph Thika',
                'small_christian_community' => 'St. Lydia',
                'church_group' => 'Youth',
                'membership_status' => 'active',
                'membership_date' => '2014-02-14',
                'baptism_date' => '1996-06-12',
                'confirmation_date' => '2009-04-19',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '2020-02-29',
                'marriage_location' => 'St. Joseph Thika',
                'marriage_county' => 'Kiambu',
                'marriage_sub_county' => 'Thika',
                'member_marriage_residence' => 'Thika Estate, Kiambu County',
                'occupation' => 'employed',
                'education_level' => 'diploma',
                'tribe' => 'Kikuyu',
                'clan' => 'Agachiku',
                'father_name' => 'John Kamanja Wangui',
                'mother_name' => 'Mary Njeri Kamanja',
                'sponsor' => 'Joyce Gitau',
                'minister' => 'Fr. Michael Kiarie',
                'notes' => 'Newlywed, youth ministry coordinator',
            ],

            // 28. Male Student - Secondary School
            [
                'category' => 'Secondary Student (Male)',
                'first_name' => 'Brian',
                'middle_name' => 'Kimani',
                'last_name' => 'Mwangi',
                'date_of_birth' => '2009-10-20',
                'gender' => 'Male',
                'id_number' => null,
                'phone' => '0778901234',
                'email' => null,
                'residence' => 'Nyeri, Nyeri',
                'local_church' => 'St. Peter Nyeri',
                'small_christian_community' => 'St. Brian',
                'church_group' => 'Youth',
                'membership_status' => 'active',
                'membership_date' => '2009-11-15',
                'baptism_date' => '2009-11-25',
                'confirmation_date' => '2023-05-14',
                'matrimony_status' => 'single',
                'occupation' => 'not_employed',
                'education_level' => 'kcse',
                'tribe' => 'Kikuyu',
                'clan' => 'Agachiku',
                'father_name' => 'Samuel Mwangi Kimani',
                'mother_name' => 'Catherine Wangui Mwangi',
                'sponsor' => 'James Maina',
                'minister' => 'Fr. Daniel Muturi',
                'notes' => 'Form 4 student, altar server, football team captain',
            ],

            // 29. Single Female - Social Worker
            [
                'category' => 'Social Worker (Single)',
                'first_name' => 'Esther',
                'middle_name' => 'Nyawira',
                'last_name' => 'Kariuki',
                'date_of_birth' => '1989-01-18',
                'gender' => 'Female',
                'id_number' => '29876543',
                'phone' => '0789012345',
                'email' => 'esther.kariuki@social.go.ke',
                'residence' => 'Kandara, Muranga',
                'local_church' => 'Sacred Heart Kandara',
                'small_christian_community' => 'St. Esther',
                'church_group' => 'Catholic Action',
                'membership_status' => 'active',
                'membership_date' => '2007-01-20',
                'baptism_date' => '1989-02-22',
                'confirmation_date' => '2002-03-31',
                'matrimony_status' => 'single',
                'occupation' => 'employed',
                'education_level' => 'degree',
                'tribe' => 'Kikuyu',
                'clan' => 'Anjiru',
                'father_name' => 'Peter Kariuki Nyawira',
                'mother_name' => 'Agnes Wambui Kariuki',
                'sponsor' => 'Elizabeth Muturi',
                'minister' => 'Fr. Francis Githinji',
                'notes' => 'Social worker, community outreach coordinator',
            ],

            // 30. Male Retiree - Volunteer
            [
                'category' => 'Retired Volunteer (Male)',
                'first_name' => 'Timothy',
                'middle_name' => 'Muturi',
                'last_name' => 'Wachira',
                'date_of_birth' => '1958-04-12',
                'gender' => 'Male',
                'id_number' => '08765432',
                'phone' => '0700987654',
                'email' => null,
                'residence' => 'Kiambu, Kiambu',
                'local_church' => 'Holy Family Kiambu',
                'small_christian_community' => 'St. Timothy',
                'church_group' => 'Pioneer',
                'membership_status' => 'active',
                'membership_date' => '1976-05-30',
                'baptism_date' => '1958-05-15',
                'confirmation_date' => '1971-06-20',
                'matrimony_status' => 'married',
                'marriage_type' => 'church',
                'marriage_date' => '1985-08-24',
                'marriage_location' => 'Holy Family Kiambu',
                'marriage_county' => 'Kiambu',
                'marriage_sub_county' => 'Kiambu',
                'member_marriage_residence' => 'Kiambu Town, Kiambu County',
                'occupation' => 'not_employed',
                'education_level' => 'diploma',
                'tribe' => 'Kikuyu',
                'clan' => 'Anjiru',
                'father_name' => 'Wachira wa Muturi',
                'mother_name' => 'Nyokabi wa Wachira',
                'sponsor' => 'Samuel Wanjiku',
                'minister' => 'Fr. Joseph Muturi',
                'notes' => 'Retired civil servant, catechist, parish volunteer coordinator',
            ],
        ];
    }

    /**
     * Display summary of created members
     */
    private function displaySummary(): void
    {
        $this->command->info('');
        $this->command->info('📊 30-MEMBER SEEDING SUMMARY');
        $this->command->info('='.str_repeat('=', 60));

        $stats = [
            'Total Members' => Member::count(),
            'Male Members' => Member::where('gender', 'Male')->count(),
            'Female Members' => Member::where('gender', 'Female')->count(),
            'Active Members' => Member::where('membership_status', 'active')->count(),
            'Inactive Members' => Member::where('membership_status', 'inactive')->count(),
            'Transferred Members' => Member::where('membership_status', 'transferred')->count(),
            'Deceased Members' => Member::where('membership_status', 'deceased')->count(),
        ];

        foreach ($stats as $label => $count) {
            $this->command->info(sprintf('%-25s: %d', $label, $count));
        }

        $this->command->info('');
        $this->command->info('💒 MATRIMONY STATUS BREAKDOWN:');
        $matrimonyStats = [
            'Single Members' => Member::where('matrimony_status', 'single')->count(),
            'Married Members' => Member::where('matrimony_status', 'married')->count(),
            '  - Church Marriages' => Member::where('matrimony_status', 'married')->where('marriage_type', 'church')->count(),
            '  - Civil Marriages' => Member::where('matrimony_status', 'married')->where('marriage_type', 'civil')->count(),
            '  - Customary Marriages' => Member::where('matrimony_status', 'married')->where('marriage_type', 'customary')->count(),
            'Widowed Members' => Member::where('matrimony_status', 'widowed')->count(),
            'Divorced Members' => Member::where('matrimony_status', 'divorced')->count(),
        ];

        foreach ($matrimonyStats as $label => $count) {
            $this->command->info(sprintf('%-25s: %d', $label, $count));
        }

        $this->command->info('');
        $this->command->info('🎓 EDUCATION LEVELS:');
        $educationStats = [
            'Primary' => Member::where('education_level', 'primary')->count(),
            'KCPE' => Member::where('education_level', 'kcpe')->count(),
            'KCSE' => Member::where('education_level', 'kcse')->count(),
            'Certificate' => Member::where('education_level', 'certificate')->count(),
            'Diploma' => Member::where('education_level', 'diploma')->count(),
            'Degree' => Member::where('education_level', 'degree')->count(),
            'Masters' => Member::where('education_level', 'masters')->count(),
        ];

        foreach ($educationStats as $label => $count) {
            $this->command->info(sprintf('%-25s: %d', $label, $count));
        }

        $this->command->info('');
        $this->command->info('💼 OCCUPATION BREAKDOWN:');
        $occupationStats = [
            'Employed' => Member::where('occupation', 'employed')->count(),
            'Self-Employed' => Member::where('occupation', 'self_employed')->count(),
            'Not Employed' => Member::where('occupation', 'not_employed')->count(),
        ];

        foreach ($occupationStats as $label => $count) {
            $this->command->info(sprintf('%-25s: %d', $label, $count));
        }

        $this->command->info('');
        $this->command->info('📅 AGE GROUPS:');
        $now = Carbon::now();
        $ageStats = [
            'Children (0-12)' => Member::whereRaw("(julianday('now') - julianday(date_of_birth)) / 365.25 BETWEEN 0 AND 12")->count(),
            'Teenagers (13-19)' => Member::whereRaw("(julianday('now') - julianday(date_of_birth)) / 365.25 BETWEEN 13 AND 19")->count(),
            'Young Adults (20-35)' => Member::whereRaw("(julianday('now') - julianday(date_of_birth)) / 365.25 BETWEEN 20 AND 35")->count(),
            'Middle-aged (36-60)' => Member::whereRaw("(julianday('now') - julianday(date_of_birth)) / 365.25 BETWEEN 36 AND 60")->count(),
            'Seniors (60+)' => Member::whereRaw("(julianday('now') - julianday(date_of_birth)) / 365.25 > 60")->count(),
        ];

        foreach ($ageStats as $label => $count) {
            $this->command->info(sprintf('%-25s: %d', $label, $count));
        }

        $this->command->info('');
        $this->command->info('✨ SPECIAL FEATURES TESTED:');
        $specialFeatures = [
            'Members with Disabilities' => Member::where('is_differently_abled', true)->count(),
            'Recent Converts (2023+)' => Member::where('baptism_date', '>=', '2023-01-01')->count(),
            'Marriage Residence Data' => Member::whereNotNull('member_marriage_residence')->count(),
            'Email Addresses' => Member::whereNotNull('email')->count(),
            'Phone Numbers' => Member::whereNotNull('phone')->count(),
        ];

        foreach ($specialFeatures as $label => $count) {
            $this->command->info(sprintf('%-25s: %d', $label, $count));
        }

        $this->command->info('');
        $this->command->info('🎯 COMPREHENSIVE TESTING READY:');
        $this->command->info('  • All matrimony statuses represented (single, married, widowed, divorced)');
        $this->command->info('  • All marriage types covered (church, civil, customary)');
        $this->command->info('  • Complete age spectrum (children to seniors)');
        $this->command->info('  • Diverse occupations and education levels');
        $this->command->info('  • Special needs member included');
        $this->command->info('  • All membership statuses (active, inactive, transferred, deceased)');
        $this->command->info('  • Cultural diversity represented');
        $this->command->info('');
        $this->command->info('🔗 LOGIN TO TEST:');
        $this->command->info('  • URL: http://127.0.0.1:8000/login');
        $this->command->info('  • Super Admin: admin@parish.local / parish123');
        $this->command->info('  • Backup Admin: admin@parish.com / admin123');
        $this->command->info('  • Staff User: staff@parish.local / staff123');
        $this->command->info('  • Secretary: secretary@parish.local / secretary123');
    }
}
