<?php

namespace Database\Seeders;

use App\Models\Member;
use Faker\Factory;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class BasicMembersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Creating 20 diverse test members with basic fields...');

        // Clear existing members
        Member::truncate();

        $faker = Faker::create();

        // Available columns based on our earlier schema check
        $availableColumns = [
            'id', 'first_name', 'middle_name', 'last_name', 'date_of_birth',
            'gender', 'id_number', 'phone', 'email', 'residence', 'emergency_contact',
            'emergency_phone', 'local_church', 'small_christian_community', 'church_group',
            'additional_church_groups', 'membership_status', 'membership_date',
            'baptism_date', 'confirmation_date', 'matrimony_status', 'marriage_type',
            'occupation', 'education_level', 'tribe', 'clan', 'family_id',
            'parent', 'godparent', 'minister', 'notes', 'created_at', 'updated_at',
        ];

        // Valid values based on model constants
        $validChurchGroups = ['PMC', 'Youth', 'Young Parents', 'C.W.A', 'CMA', 'Choir', 'Catholic Action', 'Pioneer'];
        $validMarriageTypes = ['church', 'customary']; // Avoiding 'civil' due to constraint issues
        $validMembershipStatuses = ['active', 'inactive', 'transferred', 'deceased'];
        $validMatrimonyStatuses = ['single', 'married', 'widowed', 'separated'];
        $validEducationLevels = ['none', 'primary', 'kcpe', 'secondary', 'kcse', 'certificate', 'diploma', 'degree', 'masters', 'phd'];
        $validTribes = ['Kikuyu', 'Luo', 'Luhya', 'Kamba', 'Kalenjin'];
        $validOccupations = ['teacher', 'farmer', 'business', 'civil_servant', 'student', 'not_employed', 'nurse', 'mechanic', 'retired'];

        $kenyanNames = [
            'male' => ['Joseph', 'Peter', 'John', 'Paul', 'David', 'Michael', 'Daniel', 'Samuel', 'James', 'Francis'],
            'female' => ['Mary', 'Grace', 'Joyce', 'Jane', 'Ruth', 'Elizabeth', 'Sarah', 'Catherine', 'Margaret', 'Ann'],
        ];

        $lastNames = ['Njoroge', 'Kamau', 'Mwangi', 'Wanjiku', 'Kariuki', 'Githinji', 'Muturi', 'Wangari', 'Kimani', 'Wairimu'];

        for ($i = 1; $i <= 20; $i++) {
            $gender = $faker->randomElement(['Male', 'Female']);
            $isMarried = $faker->boolean(40);
            $isBaptized = $faker->boolean(85);
            $age = $faker->numberBetween(5, 80);

            $firstName = $faker->randomElement($kenyanNames[strtolower($gender)]);
            $lastName = $faker->randomElement($lastNames);

            $memberData = [
                'first_name' => $firstName,
                'middle_name' => $faker->optional(0.7)->randomElement(array_merge($kenyanNames['male'], $kenyanNames['female'])),
                'last_name' => $lastName,
                'date_of_birth' => $faker->dateTimeBetween("-{$age} years", '-'.($age - 1).' years')->format('Y-m-d'),
                'gender' => $gender,
                'id_number' => $age >= 18 ? $faker->numerify('########') : null,
                'phone' => $age >= 16 ? $faker->regexify('07[0-9]{8}') : null,
                'email' => $age >= 18 && $faker->boolean(60) ? $faker->safeEmail() : null,
                'residence' => $faker->randomElement(['Kandara, Muranga', 'Thika, Kiambu', 'Nyeri, Nyeri', 'Kiambu Town']),
                'emergency_contact' => $faker->name(),
                'emergency_phone' => $faker->regexify('07[0-9]{8}'),
                'local_church' => $faker->randomElement(['Sacred Heart Kandara', 'St. Joseph Thika', 'St. Peter Nyeri', 'Holy Family Kiambu']),
                'small_christian_community' => 'St. '.$firstName,
                'church_group' => $this->getAgeAppropriateGroup($age, $validChurchGroups),
                'additional_church_groups' => $faker->optional(0.3)->randomElements(['Choir', 'Catholic Action'], 1),
                'membership_status' => $faker->randomElement($validMembershipStatuses),
                'membership_date' => $faker->dateTimeBetween('-20 years', 'now')->format('Y-m-d'),
                'baptism_date' => $isBaptized ? $faker->dateTimeBetween("-{$age} years", 'now')->format('Y-m-d') : null,
                'confirmation_date' => $isBaptized && $age >= 12 ? $faker->dateTimeBetween('-'.($age - 12).' years', 'now')->format('Y-m-d') : null,
                'matrimony_status' => $age >= 18 ? ($isMarried ? 'married' : $faker->randomElement(['single', 'widowed'])) : 'single',
                'marriage_type' => $isMarried && $age >= 18 ? $faker->randomElement($validMarriageTypes) : null,
                'occupation' => $this->getAgeAppropriateOccupation($age, $validOccupations),
                'education_level' => $this->getAgeAppropriateEducation($age, $validEducationLevels),
                'tribe' => $faker->randomElement($validTribes),
                'clan' => $faker->optional(0.7)->word(),
                'parent' => $faker->name(),
                'godparent' => $isBaptized ? $faker->name() : null,
                'minister' => $isBaptized ? 'Fr. '.$faker->firstName().' '.$faker->lastName() : null,
                'notes' => $faker->optional(0.3)->sentence(),
            ];

            try {
                $member = Member::create($memberData);
                $this->command->info("✅ Created member {$i}: {$member->first_name} {$member->last_name} (Age: {$age}, {$member->church_group})");
            } catch (\Exception $e) {
                $this->command->error("❌ Failed to create member {$i}: ".$e->getMessage());
            }
        }

        $this->command->info('🎉 Successfully created diverse parish members!');
        $this->displaySummary();
    }

    private function getAgeAppropriateGroup($age, $groups)
    {
        if ($age <= 12) {
            return 'PMC';
        }
        if ($age <= 35) {
            return 'Youth';
        }
        if ($age <= 60) {
            return in_array('C.W.A', $groups) ? 'C.W.A' : 'CMA';
        }

        return 'Pioneer';
    }

    private function getAgeAppropriateOccupation($age, $occupations)
    {
        if ($age <= 15) {
            return 'student';
        }
        if ($age >= 65) {
            return 'retired';
        }

        return Factory::create()->randomElement($occupations);
    }

    private function getAgeAppropriateEducation($age, $levels)
    {
        if ($age <= 8) {
            return 'primary';
        }
        if ($age <= 14) {
            return 'primary';
        }
        if ($age <= 18) {
            return 'secondary';
        }

        return Factory::create()->randomElement(['certificate', 'diploma', 'degree']);
    }

    private function displaySummary(): void
    {
        $this->command->info('');
        $this->command->info('📊 MEMBER SEEDING SUMMARY');
        $this->command->info('='.str_repeat('=', 50));

        $stats = [
            'Total Members' => Member::count(),
            'Male Members' => Member::where('gender', 'Male')->count(),
            'Female Members' => Member::where('gender', 'Female')->count(),
            'Active Members' => Member::where('membership_status', 'active')->count(),
            'Married Members' => Member::where('matrimony_status', 'married')->count(),
            'Baptized Members' => Member::whereNotNull('baptism_date')->count(),
            'Confirmed Members' => Member::whereNotNull('confirmation_date')->count(),
        ];

        foreach ($stats as $label => $count) {
            $this->command->info(sprintf('%-25s: %d', $label, $count));
        }

        $this->command->info('');
        $this->command->info('🎯 Church Groups Distribution:');
        $groups = Member::selectRaw('church_group, COUNT(*) as count')
            ->groupBy('church_group')
            ->get();

        foreach ($groups as $group) {
            $this->command->info("  • {$group->church_group}: {$group->count} members");
        }

        $this->command->info('');
        $this->command->info('🔗 System Testing Ready:');
        $this->command->info('  • Login: http://127.0.0.1:8000/login');
        $this->command->info('  • Credentials: admin@parish.com / admin123');
        $this->command->info('  • Navigate to Members section to view test data');
        $this->command->info('  • Test all member management features with diverse data');
    }
}
