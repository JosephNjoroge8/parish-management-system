<?php
// filepath: c:\Users\Joseph Njoroge\parish-system\database\seeders\MemberSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Member;
use App\Models\Family;
use App\Models\User;
use Carbon\Carbon;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing data
        Member::truncate();
        
        // Only truncate families if the table exists
        if (Schema::hasTable('families')) {
            Family::truncate();
        }
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Check what columns exist in the families table
        $familyColumns = Schema::hasTable('families') ? Schema::getColumnListing('families') : [];
        $this->command->info('Available family columns: ' . implode(', ', $familyColumns));

        // Create families using the correct column structure
        $families = [];
        if (Schema::hasTable('families')) {
            $familiesData = [
                [
                    'family_name' => 'Njoroge Family',
                    'head_of_family' => 'Joseph Mwangi Njoroge', // String field as required
                    'address' => 'P.O. Box 123, Kangemi, Nairobi',
                    'phone' => '+254701234567',
                    'email' => 'njoroge.family@gmail.com',
                    'deanery' => 'Nairobi West Deanery',
                    'parish' => 'St James Kangemi Parish',
                    'family_code' => 'FAM001',
                    'parish_section' => 'Kangemi Section',
                    'created_by' => 1,
                ],
                [
                    'family_name' => 'Wanjiku Family',
                    'head_of_family' => 'Mary Nyokabi Wanjiku',
                    'address' => 'P.O. Box 456, Pembe Tatu, Kiambu',
                    'phone' => '+254712345678',
                    'email' => 'wanjiku.family@yahoo.com',
                    'deanery' => 'Kiambu Deanery',
                    'parish' => 'St Veronica Pembe Tatu Parish',
                    'family_code' => 'FAM002',
                    'parish_section' => 'Pembe Tatu Section',
                    'created_by' => 1,
                ],
                [
                    'family_name' => 'Mutua Family',
                    'head_of_family' => 'Peter Musyoki Mutua',
                    'address' => 'P.O. Box 789, Cathedral, Nairobi',
                    'phone' => '+254723456789',
                    'email' => 'mutua.family@gmail.com',
                    'deanery' => 'Nairobi Central Deanery',
                    'parish' => 'Our Lady of Consolata Cathedral Parish',
                    'family_code' => 'FAM003',
                    'parish_section' => 'Cathedral Section',
                    'created_by' => 1,
                ],
                [
                    'family_name' => 'Ochieng Family',
                    'head_of_family' => 'James Otieno Ochieng',
                    'address' => 'P.O. Box 321, Kiawara, Kiambu',
                    'phone' => '+254734567890',
                    'email' => 'ochieng.family@hotmail.com',
                    'deanery' => 'Kiambu Deanery',
                    'parish' => 'St Peter Kiawara Parish',
                    'family_code' => 'FAM004',
                    'parish_section' => 'Kiawara Section',
                    'created_by' => 1,
                ],
                [
                    'family_name' => 'Akinyi Family',
                    'head_of_family' => 'Grace Adhiambo Akinyi',
                    'address' => 'P.O. Box 654, Kandara, Murang\'a',
                    'phone' => '+254745678901',
                    'email' => 'akinyi.family@gmail.com',
                    'deanery' => 'Murang\'a Deanery',
                    'parish' => 'Sacred Heart Kandara Parish',
                    'family_code' => 'FAM005',
                    'parish_section' => 'Kandara Section',
                    'created_by' => 1,
                ],
            ];

            // Create families
            foreach ($familiesData as $familyData) {
                $family = Family::create($familyData);
                $families[] = $family;
            }

            $this->command->info('Created ' . count($families) . ' families.');
        }

        // Sample members data - Using correct ENUM values for occupation
        $membersData = [
            // Njoroge Family - Head of Family
            [
                'local_church' => 'St James Kangemi',
                'church_group' => 'CMA',
                'first_name' => 'Joseph',
                'middle_name' => 'Mwangi',
                'last_name' => 'Njoroge',
                'date_of_birth' => '1985-06-15',
                'gender' => 'male',
                'phone' => '+254701234567',
                'email' => 'joseph.njoroge@gmail.com',
                'id_number' => '28765432',
                'sponsor' => 'Peter Kamau',
                'occupation' => 'employed', // Using correct ENUM value
                'education_level' => 'University',
                'family_id' => !empty($families) ? $families[0]->id : null,
                'parent' => null,
                'minister' => 'Fr. John Mukuria',
                'tribe' => 'Kikuyu',
                'clan' => 'Anjiru',
                'baptism_date' => '1986-01-10',
                'residence' => 'Kangemi Estate, House No. 25',
                'confirmation_date' => '1998-05-15',
                'matrimony_status' => 'married',
                'membership_date' => '2020-01-15',
                'membership_status' => 'active',
                'emergency_contact' => 'Grace Njoroge',
                'emergency_phone' => '+254787654321',
                'notes' => 'Software Developer, IT support volunteer, Finance committee member',
            ],
            
            // Njoroge Family - Spouse
            [
                'local_church' => 'St James Kangemi',
                'church_group' => 'C.W.A',
                'first_name' => 'Grace',
                'middle_name' => 'Wanjiru',
                'last_name' => 'Njoroge',
                'date_of_birth' => '1988-09-22',
                'gender' => 'female',
                'phone' => '+254787654321',
                'email' => 'grace.njoroge@gmail.com',
                'id_number' => '31234567',
                'sponsor' => 'Mary Wanjiku',
                'occupation' => 'employed',
                'education_level' => 'University',
                'family_id' => !empty($families) ? $families[0]->id : null,
                'parent' => null,
                'minister' => 'Fr. John Mukuria',
                'tribe' => 'Kikuyu',
                'clan' => 'Agachiku',
                'baptism_date' => '1989-02-18',
                'residence' => 'Kangemi Estate, House No. 25',
                'confirmation_date' => '2001-04-22',
                'matrimony_status' => 'married',
                'membership_date' => '2020-01-15',
                'membership_status' => 'active',
                'emergency_contact' => 'Joseph Njoroge',
                'emergency_phone' => '+254701234567',
                'notes' => 'Primary School Teacher, Choir member, Sunday school teacher',
            ],
            
            // Njoroge Family - Child
            [
                'local_church' => 'St James Kangemi',
                'church_group' => 'PMC',
                'first_name' => 'John',
                'middle_name' => 'Kamau',
                'last_name' => 'Njoroge',
                'date_of_birth' => '2010-03-10',
                'gender' => 'male',
                'phone' => null,
                'email' => null,
                'id_number' => null,
                'sponsor' => 'Joseph Njoroge',
                'occupation' => 'not_employed', // Child - not employed
                'education_level' => 'Primary School',
                'family_id' => !empty($families) ? $families[0]->id : null,
                'parent' => 'Joseph Njoroge & Grace Njoroge',
                'minister' => 'Fr. John Mukuria',
                'tribe' => 'Kikuyu',
                'clan' => 'Anjiru',
                'baptism_date' => '2010-04-25',
                'residence' => 'Kangemi Estate, House No. 25',
                'confirmation_date' => null,
                'matrimony_status' => null,
                'membership_date' => '2010-04-25',
                'membership_status' => 'active',
                'emergency_contact' => 'Joseph Njoroge',
                'emergency_phone' => '+254701234567',
                'notes' => 'Primary school student, Sunday school student, altar server',
            ],
            
            // Wanjiku Family - Head (Single Mother)
            [
                'local_church' => 'St Veronica Pembe Tatu',
                'church_group' => 'C.W.A',
                'first_name' => 'Mary',
                'middle_name' => 'Nyokabi',
                'last_name' => 'Wanjiku',
                'date_of_birth' => '1982-12-08',
                'gender' => 'female',
                'phone' => '+254712345678',
                'email' => 'mary.wanjiku@yahoo.com',
                'id_number' => '25876543',
                'sponsor' => 'Agnes Muthoni',
                'occupation' => 'employed',
                'education_level' => 'Diploma',
                'family_id' => !empty($families) && count($families) > 1 ? $families[1]->id : null,
                'parent' => null,
                'minister' => 'Fr. Patrick Muriuki',
                'tribe' => 'Kikuyu',
                'clan' => 'Aithega',
                'baptism_date' => '1983-03-20',
                'residence' => 'Pembe Tatu, Plot 15',
                'confirmation_date' => '1995-06-11',
                'matrimony_status' => 'single',
                'membership_date' => '2019-03-20',
                'membership_status' => 'active',
                'emergency_contact' => 'Sarah Wanjiku',
                'emergency_phone' => '+254798765432',
                'notes' => 'Registered Nurse, Healthcare ministry volunteer, single mother',
            ],
            
            // Wanjiku Family - Child
            [
                'local_church' => 'St Veronica Pembe Tatu',
                'church_group' => 'Youth',
                'first_name' => 'David',
                'middle_name' => 'Kariuki',
                'last_name' => 'Wanjiku',
                'date_of_birth' => '2008-05-18',
                'gender' => 'male',
                'phone' => null,
                'email' => null,
                'id_number' => null,
                'sponsor' => 'Mary Wanjiku',
                'occupation' => 'not_employed', // Student - not employed
                'education_level' => 'Secondary School',
                'family_id' => !empty($families) && count($families) > 1 ? $families[1]->id : null,
                'parent' => 'Mary Wanjiku',
                'minister' => 'Fr. Patrick Muriuki',
                'tribe' => 'Kikuyu',
                'clan' => 'Aithega',
                'baptism_date' => '2008-07-15',
                'residence' => 'Pembe Tatu, Plot 15',
                'confirmation_date' => '2021-11-28',
                'matrimony_status' => null,
                'membership_date' => '2008-07-15',
                'membership_status' => 'active',
                'emergency_contact' => 'Mary Wanjiku',
                'emergency_phone' => '+254712345678',
                'notes' => 'Secondary school student, Altar server, youth group member',
            ],
            
            // Mutua Family - Head
            [
                'local_church' => 'Our Lady of Consolata Cathedral',
                'church_group' => 'CMA',
                'first_name' => 'Peter',
                'middle_name' => 'Musyoki',
                'last_name' => 'Mutua',
                'date_of_birth' => '1975-04-25',
                'gender' => 'male',
                'phone' => '+254723456789',
                'email' => 'peter.mutua@gmail.com',
                'id_number' => '21345678',
                'sponsor' => 'James Kiprotich',
                'occupation' => 'self_employed', // Business owner
                'education_level' => 'University',
                'family_id' => !empty($families) && count($families) > 2 ? $families[2]->id : null,
                'parent' => null,
                'minister' => 'Fr. Francis Gatimu',
                'tribe' => 'Kamba',
                'clan' => 'Amutei',
                'baptism_date' => '1976-01-18',
                'residence' => 'Cathedral Area, Building C, Apt 12',
                'confirmation_date' => '1988-09-04',
                'matrimony_status' => 'married',
                'membership_date' => '2021-07-10',
                'membership_status' => 'active',
                'emergency_contact' => 'Ruth Mutua',
                'emergency_phone' => '+254765432109',
                'notes' => 'Business Owner, Finance committee member',
            ],
            
            // Mutua Family - Spouse
            [
                'local_church' => 'Our Lady of Consolata Cathedral',
                'church_group' => 'C.W.A',
                'first_name' => 'Ruth',
                'middle_name' => 'Kawira',
                'last_name' => 'Mutua',
                'date_of_birth' => '1978-11-30',
                'gender' => 'female',
                'phone' => '+254765432109',
                'email' => 'ruth.mutua@gmail.com',
                'id_number' => '23456789',
                'sponsor' => 'Grace Akinyi',
                'occupation' => 'employed',
                'education_level' => 'University',
                'family_id' => !empty($families) && count($families) > 2 ? $families[2]->id : null,
                'parent' => null,
                'minister' => 'Fr. Francis Gatimu',
                'tribe' => 'Meru',
                'clan' => 'Igoji',
                'baptism_date' => '1979-04-08',
                'residence' => 'Cathedral Area, Building C, Apt 12',
                'confirmation_date' => '1991-05-26',
                'matrimony_status' => 'married',
                'membership_date' => '2021-07-10',
                'membership_status' => 'active',
                'emergency_contact' => 'Peter Mutua',
                'emergency_phone' => '+254723456789',
                'notes' => 'Certified Public Accountant, Women\'s group secretary',
            ],
            
            // Individual members (not in families)
            [
                'local_church' => 'St Peter Kiawara',
                'church_group' => 'Youth',
                'first_name' => 'James',
                'middle_name' => 'Otieno',
                'last_name' => 'Ochieng',
                'date_of_birth' => '1998-08-12',
                'gender' => 'male',
                'phone' => '+254734567890',
                'email' => 'james.ochieng@hotmail.com',
                'id_number' => '35678901',
                'sponsor' => 'Samuel Kariuki',
                'occupation' => 'employed',
                'education_level' => 'University',
                'family_id' => null,
                'parent' => null,
                'minister' => 'Fr. Joseph Mwangi',
                'tribe' => 'Luo',
                'clan' => 'Joka-Jok',
                'baptism_date' => '1999-01-17',
                'residence' => 'Kiawara Township, Block 8',
                'confirmation_date' => '2011-04-17',
                'matrimony_status' => 'single',
                'membership_date' => '2018-11-05',
                'membership_status' => 'active',
                'emergency_contact' => 'Margaret Ochieng',
                'emergency_phone' => '+254776543210',
                'notes' => 'Civil Engineer, Youth ministry leader',
            ],
            
            [
                'local_church' => 'Sacred Heart Kandara',
                'church_group' => 'C.W.A',
                'first_name' => 'Grace',
                'middle_name' => 'Adhiambo',
                'last_name' => 'Akinyi',
                'date_of_birth' => '1983-01-28',
                'gender' => 'female',
                'phone' => '+254745678901',
                'email' => 'grace.akinyi@gmail.com',
                'id_number' => '26789012',
                'sponsor' => 'Ruth Mutua',
                'occupation' => 'employed',
                'education_level' => 'University',
                'family_id' => null,
                'parent' => null,
                'minister' => 'Fr. Michael Kimani',
                'tribe' => 'Luo',
                'clan' => 'Joka-Owiny',
                'baptism_date' => '1984-05-13',
                'residence' => 'Kandara Town, House 47',
                'confirmation_date' => '1996-03-31',
                'matrimony_status' => 'widowed',
                'membership_date' => '2022-02-14',
                'membership_status' => 'active',
                'emergency_contact' => 'Rose Akinyi',
                'emergency_phone' => '+254754321098',
                'notes' => 'Social Worker, Community outreach coordinator',
            ],
            
            [
                'local_church' => 'St James Kangemi',
                'church_group' => 'CMA',
                'first_name' => 'Samuel',
                'middle_name' => 'Mwangi',
                'last_name' => 'Kariuki',
                'date_of_birth' => '1965-10-05',
                'gender' => 'male',
                'phone' => '+254756789012',
                'email' => 'samuel.kariuki@gmail.com',
                'id_number' => '18901234',
                'sponsor' => 'Elder Patrick Maina',
                'occupation' => 'not_employed', // Retired
                'education_level' => 'University',
                'family_id' => null,
                'parent' => null,
                'minister' => 'Fr. John Mukuria',
                'tribe' => 'Kikuyu',
                'clan' => 'Acera',
                'baptism_date' => '1966-03-19',
                'residence' => 'Kangemi Old Estate, House 102',
                'confirmation_date' => '1978-05-14',
                'matrimony_status' => 'widowed',
                'membership_date' => '2015-05-20',
                'membership_status' => 'active',
                'emergency_contact' => 'Paul Kariuki',
                'emergency_phone' => '+254767890123',
                'notes' => 'Retired Teacher, Elder and catechist',
            ],

            // Additional individual members
            [
                'local_church' => 'St Veronica Pembe Tatu',
                'church_group' => 'C.W.A',
                'first_name' => 'Agnes',
                'middle_name' => 'Wanjugu',
                'last_name' => 'Muthoni',
                'date_of_birth' => '1992-02-14',
                'gender' => 'female',
                'phone' => '+254778901234',
                'email' => 'agnes.muthoni@yahoo.com',
                'id_number' => '33345678',
                'sponsor' => 'Mary Wanjiku',
                'occupation' => 'employed',
                'education_level' => 'University',
                'family_id' => null,
                'parent' => null,
                'minister' => 'Fr. Patrick Muriuki',
                'tribe' => 'Kikuyu',
                'clan' => 'Anjiru',
                'baptism_date' => '1992-05-10',
                'residence' => 'Pembe Tatu Heights, Flat 7B',
                'confirmation_date' => '2004-11-21',
                'matrimony_status' => 'married',
                'membership_date' => '2023-01-08',
                'membership_status' => 'active',
                'emergency_contact' => 'Rebecca Muthoni',
                'emergency_phone' => '+254789012345',
                'notes' => 'Marketing Executive, Communications team member',
            ],
            
            [
                'local_church' => 'Our Lady of Consolata Cathedral',
                'church_group' => 'Choir',
                'first_name' => 'Michael',
                'middle_name' => 'Kiprop',
                'last_name' => 'Kiprotich',
                'date_of_birth' => '1987-09-18',
                'gender' => 'male',
                'phone' => '+254790123456',
                'email' => 'michael.kiprotich@gmail.com',
                'id_number' => '29012345',
                'sponsor' => 'Peter Mutua',
                'occupation' => 'employed',
                'education_level' => 'University',
                'family_id' => null,
                'parent' => null,
                'minister' => 'Fr. Francis Gatimu',
                'tribe' => 'Kalenjin',
                'clan' => 'Kapsowar',
                'baptism_date' => '1988-01-03',
                'residence' => 'Cathedral Quarters, Room 23',
                'confirmation_date' => '2000-06-18',
                'matrimony_status' => 'single',
                'membership_date' => '2020-08-15',
                'membership_status' => 'active',
                'emergency_contact' => 'Sarah Kiprotich',
                'emergency_phone' => '+254801234567',
                'notes' => 'Music Teacher, Choir director, talented musician',
            ],
        ];

        // Create members
        foreach ($membersData as $index => $memberData) {
            $member = Member::create($memberData);
        }

        $this->command->info('Members seeder completed successfully!');
        $this->command->info('Created ' . count($membersData) . ' members.');
        $this->command->info('Created ' . count($families) . ' families.');

        // Generate additional random members (optional)
        $this->generateRandomMembers(10);
    }

    /**
     * Generate additional random members matching new schema
     */
    private function generateRandomMembers(int $count = 10): void
    {
        $firstNames = [
            'male' => ['John', 'Peter', 'James', 'David', 'Michael', 'Paul', 'Daniel', 'Stephen'],
            'female' => ['Mary', 'Grace', 'Ruth', 'Sarah', 'Rebecca', 'Rachel', 'Esther', 'Hannah']
        ];

        $middleNames = [
            'male' => ['Mwangi', 'Kariuki', 'Kamau', 'Otieno', 'Kiprop', 'Musyoki'],
            'female' => ['Wanjiru', 'Nyokabi', 'Adhiambo', 'Kawira', 'Cheptoo', 'Wanjiku']
        ];

        $lastNames = ['Kamau', 'Wanjiku', 'Ochieng', 'Akinyi', 'Mutua', 'Kiprotich', 'Mwangi', 'Otieno'];
        $localChurches = [
            'St James Kangemi', 
            'St Veronica Pembe Tatu', 
            'Our Lady of Consolata Cathedral', 
            'St Peter Kiawara', 
            'Sacred Heart Kandara'
        ];
        $churchGroups = [
            'PMC', 
            'Youth', 
            'C.W.A', 
            'CMA', 
            'Choir',
            'Catholic Action',
            'Pioneer'
        ];
        
        // Correct ENUM values for occupation
        $occupations = ['employed', 'self_employed', 'not_employed'];
        $educationLevels = ['Primary School', 'Secondary School', 'Diploma', 'University', 'Postgraduate'];
        $tribes = ['Kikuyu', 'Luo', 'Kamba', 'Kalenjin', 'Meru', 'Kisii'];
        $matrimonyStatuses = ['single', 'married', 'divorced', 'widowed'];

        for ($i = 1; $i <= $count; $i++) {
            $gender = rand(0, 1) ? 'male' : 'female';
            $firstName = $firstNames[$gender][array_rand($firstNames[$gender])];
            $middleName = $middleNames[$gender][array_rand($middleNames[$gender])];
            $lastName = $lastNames[array_rand($lastNames)];
            $age = rand(18, 70);
            $localChurch = $localChurches[array_rand($localChurches)];
            $churchGroup = $churchGroups[array_rand($churchGroups)];
            
            $birthDate = Carbon::now()->subYears($age)->subDays(rand(1, 365));
            $baptismDate = $birthDate->copy()->addMonths(rand(2, 24));
            $confirmationDate = $age >= 14 ? $birthDate->copy()->addYears(rand(14, 16)) : null;
            $membershipDate = Carbon::now()->subDays(rand(30, 1095));
            
            Member::create([
                'local_church' => $localChurch,
                'church_group' => $churchGroup,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'date_of_birth' => $birthDate->format('Y-m-d'),
                'gender' => $gender,
                'phone' => '+2547' . rand(10000000, 99999999),
                'email' => strtolower($firstName . '.' . $lastName . rand(1, 99) . '@gmail.com'),
                'id_number' => $age >= 18 ? (string) rand(10000000, 99999999) : null,
                'sponsor' => $firstName . ' Sponsor',
                'occupation' => $occupations[array_rand($occupations)], // Correct ENUM values
                'education_level' => $educationLevels[array_rand($educationLevels)],
                'family_id' => null,
                'parent' => $age < 18 ? 'Parent Name' : null,
                'minister' => 'Fr. ' . ['John', 'Patrick', 'Francis', 'Michael', 'Joseph'][array_rand(['John', 'Patrick', 'Francis', 'Michael', 'Joseph'])] . ' Mukuria',
                'tribe' => $tribes[array_rand($tribes)],
                'clan' => 'Clan Name',
                'baptism_date' => $baptismDate->format('Y-m-d'),
                'residence' => $localChurch . ' Area, House ' . rand(1, 100),
                'confirmation_date' => $confirmationDate?->format('Y-m-d'),
                'matrimony_status' => $age >= 18 ? $matrimonyStatuses[array_rand($matrimonyStatuses)] : null,
                'membership_date' => $membershipDate->format('Y-m-d'),
                'membership_status' => 'active',
                'emergency_contact' => $firstName . ' Emergency Contact',
                'emergency_phone' => '+2547' . rand(10000000, 99999999),
                'notes' => 'Generated test member with various professional backgrounds',
            ]);
        }

        $this->command->info('Generated ' . $count . ' additional random members.');
    }
}
