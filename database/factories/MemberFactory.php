<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = $this->faker->randomElement(['Male', 'Female']);
        $isMarried = $this->faker->boolean(30); // 30% chance of being married
        $isBaptized = $this->faker->boolean(85); // 85% chance of being baptized
        $isConfirmed = $this->faker->boolean(70); // 70% chance of being confirmed

        // Kenyan names arrays
        $maleFirstNames = ['Joseph', 'Peter', 'John', 'Paul', 'David', 'Michael', 'Daniel', 'Samuel', 'James', 'Francis'];
        $femaleFirstNames = ['Mary', 'Grace', 'Joyce', 'Jane', 'Ruth', 'Elizabeth', 'Sarah', 'Catherine', 'Margaret', 'Ann'];
        $kikuyuLastNames = ['Njoroge', 'Kamau', 'Mwangi', 'Wanjiku', 'Kariuki', 'Githinji', 'Muturi', 'Wangari', 'Kimani', 'Wairimu'];

        $firstName = $gender === 'Male'
            ? $this->faker->randomElement($maleFirstNames)
            : $this->faker->randomElement($femaleFirstNames);

        $lastName = $this->faker->randomElement($kikuyuLastNames);

        // Main family data (entered once, auto-synced to other fields)
        $fatherName = $this->faker->randomElement($maleFirstNames).' '.$lastName;
        $motherName = $this->faker->randomElement($femaleFirstNames).' '.$this->faker->randomElement($kikuyuLastNames);
        $godparentName = $this->faker->name();
        $ministerName = 'Fr. '.$this->faker->randomElement($maleFirstNames).' '.$this->faker->lastName();

        return [
            // Core personal information
            'first_name' => $firstName,
            'middle_name' => $this->faker->optional(0.7)->randomElement($maleFirstNames + $femaleFirstNames),
            'last_name' => $lastName,
            'date_of_birth' => $this->faker->dateTimeBetween('-80 years', '-1 year')->format('Y-m-d'),
            'gender' => $gender,
            'id_number' => $this->faker->optional(0.8)->numerify('########'),

            // Contact information
            'phone' => $this->faker->optional(0.9)->regexify('07[0-9]{8}'),
            'email' => $this->faker->optional(0.6)->safeEmail(),
            'residence' => $this->faker->optional(0.9)->city().', '.$this->faker->randomElement(['Murang\'a', 'Kiambu', 'Nyeri', 'Kirinyaga']),

            // Church information
            'local_church' => $this->faker->randomElement(['Sacred Heart Kandara', 'St. Joseph Thika', 'Holy Family Kiambu', 'St. Peter Nyeri']),
            'small_christian_community' => $this->faker->optional(0.8)->randomElement(['St. John', 'St. Mary', 'St. Joseph', 'St. Peter', 'St. Paul']),
            'church_group' => $this->faker->randomElement(['PMC', 'Youth', 'C.W.A', 'CMA', 'Choir', 'Catholic Action', 'Pioneer']),
            'additional_church_groups' => $this->faker->optional(0.3)->randomElements(['Choir', 'Pioneer', 'Catholic Action'], 2),

            // Membership information
            'membership_status' => $this->faker->randomElement(['active', 'inactive', 'transferred', 'deceased']),
            'membership_date' => $this->faker->dateTimeBetween('-20 years', 'now')->format('Y-m-d'),
            'matrimony_status' => $isMarried ? 'married' : $this->faker->randomElement(['single', 'widowed', 'separated']),
            'marriage_type' => $isMarried ? $this->faker->randomElement(['church', 'civil', 'customary']) : null,

            // Personal details
            'occupation' => $this->faker->randomElement(['teacher', 'farmer', 'business', 'civil_servant', 'student', 'not_employed']),
            'education_level' => $this->faker->randomElement(['primary', 'kcpe', 'secondary', 'kcse', 'certificate', 'diploma', 'degree']),

            // Cultural information
            'tribe' => $this->faker->randomElement(['Kikuyu', 'Luo', 'Luhya', 'Kamba', 'Kalenjin']),
            'clan' => $this->faker->optional(0.7)->word(),

            // Family data - only fields that exist in members table
            'parent' => $fatherName, // Father's name
            'godparent' => $isBaptized ? $godparentName : null,
            'minister' => $isBaptized ? $ministerName : null,

            // Sacrament information - only dates stored in members table
            'baptism_date' => $isBaptized ? $this->faker->dateTimeBetween('-30 years', '-1 month')->format('Y-m-d') : null,
            'confirmation_date' => $isConfirmed ? $this->faker->dateTimeBetween('-25 years', 'now')->format('Y-m-d') : null,

            // Notes
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => 'Male',
            'first_name' => $this->faker->randomElement(['Joseph', 'Peter', 'John', 'Paul', 'David', 'Michael', 'Daniel', 'Samuel', 'James', 'Francis']),
        ]);
    }

    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => 'Female',
            'first_name' => $this->faker->randomElement(['Mary', 'Grace', 'Joyce', 'Jane', 'Ruth', 'Elizabeth', 'Sarah', 'Catherine', 'Margaret', 'Ann']),
        ]);
    }

    public function married(): static
    {
        return $this->state(fn (array $attributes) => [
            'matrimony_status' => 'married',
            'marriage_type' => $this->faker->randomElement(['church', 'civil', 'customary']),
            'marriage_date' => $this->faker->dateTimeBetween('-20 years', 'now')->format('Y-m-d'),
            'marriage_location' => $this->faker->randomElement(['Sacred Heart Kandara', 'St. Joseph Thika', 'Murang\'a AG Office']),
            'spouse_name' => $this->faker->name(),
            'spouse_age' => $this->faker->numberBetween(18, 70),
        ]);
    }

    public function baptized(): static
    {
        $godparentName = $this->faker->name();
        $ministerName = 'Fr. '.$this->faker->name();

        return $this->state(fn (array $attributes) => [
            'baptism_date' => $this->faker->dateTimeBetween('-30 years', '-1 month')->format('Y-m-d'),
            'baptism_location' => $this->faker->randomElement(['Sacred Heart Kandara', 'St. Joseph Thika', 'Holy Family Kiambu']),
            'godparent' => $godparentName,
            'minister' => $ministerName,
            'sponsor' => $godparentName, // Auto-synced
            'baptized_by' => $ministerName, // Auto-synced
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'membership_status' => 'active',
        ]);
    }
}
