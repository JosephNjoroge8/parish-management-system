<?php

namespace Database\Seeders;

use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SimpleMemberSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing members
        Member::truncate();

        // Sample members data with different statuses for testing
        $membersData = [
            [
                'first_name' => 'Joseph',
                'middle_name' => 'Mwangi',
                'last_name' => 'Njoroge',
                'date_of_birth' => '1985-06-15',
                'gender' => 'male',
                'phone' => '+254701234567',
                'email' => 'joseph.njoroge@gmail.com',
                'id_number' => '28765432',
                'local_church' => 'St James Kangemi',
                'church_group' => 'CMA',
                'membership_status' => 'active',
                'membership_date' => '2020-01-15',
                'occupation' => 'employed',
                'education_level' => 'University',
                'matrimony_status' => 'married',
                'tribe' => 'Kikuyu',
                'clan' => 'Anjiru',
                'baptism_date' => '1986-01-10',
                'confirmation_date' => '1998-05-15',
                'residence' => 'Kangemi Estate, House No. 25',
                'sponsor' => 'Peter Kamau',
                'minister' => 'Fr. John Mukuria',
                'notes' => 'Software Developer, IT support volunteer',
            ],
            [
                'first_name' => 'Grace',
                'middle_name' => 'Wanjiru',
                'last_name' => 'Njoroge',
                'date_of_birth' => '1988-09-22',
                'gender' => 'female',
                'phone' => '+254787654321',
                'email' => 'grace.njoroge@gmail.com',
                'id_number' => '31234567',
                'local_church' => 'St James Kangemi',
                'church_group' => 'C.W.A',
                'membership_status' => 'active',
                'membership_date' => '2020-01-15',
                'occupation' => 'employed',
                'education_level' => 'University',
                'matrimony_status' => 'married',
                'tribe' => 'Kikuyu',
                'clan' => 'Agachiku',
                'baptism_date' => '1989-02-18',
                'confirmation_date' => '2001-04-22',
                'residence' => 'Kangemi Estate, House No. 25',
                'sponsor' => 'Mary Wanjiku',
                'minister' => 'Fr. John Mukuria',
                'notes' => 'Primary School Teacher, Choir member',
            ],
            [
                'first_name' => 'John',
                'middle_name' => 'Kamau',
                'last_name' => 'Njoroge',
                'date_of_birth' => '2010-03-10',
                'gender' => 'male',
                'local_church' => 'St James Kangemi',
                'church_group' => 'PMC',
                'membership_status' => 'active',
                'membership_date' => '2010-04-25',
                'occupation' => 'not_employed',
                'education_level' => 'Primary School',
                'tribe' => 'Kikuyu',
                'clan' => 'Anjiru',
                'baptism_date' => '2010-04-25',
                'residence' => 'Kangemi Estate, House No. 25',
                'sponsor' => 'Joseph Njoroge',
                'minister' => 'Fr. John Mukuria',
                'parent' => 'Joseph Njoroge & Grace Njoroge',
                'notes' => 'Primary school student, altar server',
            ],
            [
                'first_name' => 'Mary',
                'middle_name' => 'Nyokabi',
                'last_name' => 'Wanjiku',
                'date_of_birth' => '1982-12-08',
                'gender' => 'female',
                'phone' => '+254712345678',
                'email' => 'mary.wanjiku@yahoo.com',
                'id_number' => '25876543',
                'local_church' => 'St Veronica Pembe Tatu',
                'church_group' => 'C.W.A',
                'membership_status' => 'inactive',
                'membership_date' => '2019-03-20',
                'occupation' => 'employed',
                'education_level' => 'Diploma',
                'matrimony_status' => 'single',
                'tribe' => 'Kikuyu',
                'clan' => 'Aithega',
                'baptism_date' => '1983-03-20',
                'confirmation_date' => '1995-06-11',
                'residence' => 'Pembe Tatu, Plot 15',
                'sponsor' => 'Agnes Muthoni',
                'minister' => 'Fr. Patrick Muriuki',
                'notes' => 'Registered Nurse, single mother',
            ],
            [
                'first_name' => 'Peter',
                'middle_name' => 'Musyoki',
                'last_name' => 'Mutua',
                'date_of_birth' => '1975-04-25',
                'gender' => 'male',
                'phone' => '+254723456789',
                'email' => 'peter.mutua@gmail.com',
                'id_number' => '21345678',
                'local_church' => 'Our Lady of Consolata Cathedral',
                'church_group' => 'CMA',
                'membership_status' => 'pending',
                'membership_date' => '2021-07-10',
                'occupation' => 'self_employed',
                'education_level' => 'University',
                'matrimony_status' => 'married',
                'tribe' => 'Kamba',
                'clan' => 'Amutei',
                'baptism_date' => '1976-01-18',
                'confirmation_date' => '1988-09-04',
                'residence' => 'Cathedral Area, Building C, Apt 12',
                'sponsor' => 'James Kiprotich',
                'minister' => 'Fr. Francis Gatimu',
                'notes' => 'Business Owner, Finance committee member',
            ],
        ];

        // Create the sample members
        foreach ($membersData as $memberData) {
            Member::create($memberData);
        }

        // Generate additional random members with various statuses
        $this->generateRandomMembers(20);

        $this->command->info('Simple Member seeder completed successfully!');
        $this->command->info('Created '.(count($membersData) + 20).' members with various statuses for testing.');
    }

    private function generateRandomMembers(int $count = 20): void
    {
        $firstNames = [
            'male' => ['John', 'Peter', 'James', 'David', 'Michael', 'Paul', 'Daniel', 'Stephen', 'Andrew', 'Mark'],
            'female' => ['Mary', 'Grace', 'Ruth', 'Sarah', 'Rebecca', 'Rachel', 'Esther', 'Hannah', 'Elizabeth', 'Anne'],
        ];

        $middleNames = [
            'male' => ['Mwangi', 'Kariuki', 'Kamau', 'Otieno', 'Kiprop', 'Musyoki', 'Kimani', 'Wanjiku'],
            'female' => ['Wanjiru', 'Nyokabi', 'Adhiambo', 'Kawira', 'Cheptoo', 'Wanjiku', 'Njeri', 'Akinyi'],
        ];

        $lastNames = ['Kamau', 'Wanjiku', 'Ochieng', 'Akinyi', 'Mutua', 'Kiprotich', 'Mwangi', 'Otieno', 'Kimani', 'Kariuki'];

        $localChurches = [
            'St James Kangemi',
            'St Veronica Pembe Tatu',
            'Our Lady of Consolata Cathedral',
            'St Peter Kiawara',
            'Sacred Heart Kandara',
        ];

        $churchGroups = ['PMC', 'Youth', 'C.W.A', 'CMA', 'Choir', 'Catholic Action', 'Pioneer'];
        $membershipStatuses = ['active', 'inactive', 'pending', 'suspended'];
        $occupations = ['employed', 'self_employed', 'not_employed'];
        $educationLevels = ['Primary School', 'Secondary School', 'Diploma', 'University', 'Postgraduate'];
        $tribes = ['Kikuyu', 'Luo', 'Kamba', 'Kalenjin', 'Meru', 'Kisii', 'Luhya', 'Embu'];
        $matrimonyStatuses = ['single', 'married', 'divorced', 'widowed'];

        for ($i = 1; $i <= $count; $i++) {
            $gender = rand(0, 1) ? 'male' : 'female';
            $firstName = $firstNames[$gender][array_rand($firstNames[$gender])];
            $middleName = $middleNames[$gender][array_rand($middleNames[$gender])];
            $lastName = $lastNames[array_rand($lastNames)];
            $age = rand(16, 75);
            $localChurch = $localChurches[array_rand($localChurches)];
            $churchGroup = $churchGroups[array_rand($churchGroups)];

            $birthDate = Carbon::now()->subYears($age)->subDays(rand(1, 365));
            $baptismDate = $birthDate->copy()->addMonths(rand(2, 24));
            $confirmationDate = $age >= 14 ? $birthDate->copy()->addYears(rand(14, 16)) : null;
            $membershipDate = Carbon::now()->subDays(rand(30, 1095));

            Member::create([
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'date_of_birth' => $birthDate->format('Y-m-d'),
                'gender' => strtolower($gender),
                'phone' => $age >= 16 ? '+2547'.rand(10000000, 99999999) : null,
                'email' => $age >= 16 ? strtolower($firstName.'.'.$lastName.rand(1, 99).'@gmail.com') : null,
                'id_number' => $age >= 18 ? (string) rand(10000000, 99999999) : null,
                'local_church' => $localChurch,
                'church_group' => $churchGroup,
                'membership_status' => $membershipStatuses[array_rand($membershipStatuses)],
                'membership_date' => $membershipDate->format('Y-m-d'),
                'occupation' => $occupations[array_rand($occupations)],
                'education_level' => $educationLevels[array_rand($educationLevels)],
                'matrimony_status' => $age >= 18 ? $matrimonyStatuses[array_rand($matrimonyStatuses)] : null,
                'tribe' => $tribes[array_rand($tribes)],
                'clan' => 'Clan '.rand(1, 10),
                'baptism_date' => $baptismDate->format('Y-m-d'),
                'confirmation_date' => $confirmationDate?->format('Y-m-d'),
                'residence' => $localChurch.' Area, House '.rand(1, 100),
                'sponsor' => $firstName.' Sponsor',
                'minister' => 'Fr. '.['John', 'Patrick', 'Francis', 'Michael', 'Joseph'][array_rand(['John', 'Patrick', 'Francis', 'Michael', 'Joseph'])].' Mukuria',
                'parent' => $age < 18 ? 'Parent Name' : null,
                'notes' => 'Generated test member - Status: '.$membershipStatuses[array_rand($membershipStatuses)],
            ]);
        }

        $this->command->info('Generated '.$count.' additional random members with various statuses.');
    }
}
