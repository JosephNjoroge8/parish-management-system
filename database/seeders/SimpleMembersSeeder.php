<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;

class SimpleMembersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Creating 20 diverse test members using factory...');

        // Clear existing members
        Member::truncate();

        // Create 20 members with different characteristics using the factory

        // 1-3: Parish Leaders (married, active, older)
        Member::factory(3)
            ->active()
            ->married()
            ->state(['church_group' => 'CMA'])
            ->create();

        // 4-6: Women's Group (active, various marriage statuses)
        Member::factory(3)
            ->female()
            ->active()
            ->state(['church_group' => 'C.W.A'])
            ->create();

        // 7-9: Youth (single, active, younger)
        Member::factory(3)
            ->active()
            ->state([
                'church_group' => 'Youth',
                'matrimony_status' => 'single',
                'date_of_birth' => fake()->dateTimeBetween('-25 years', '-16 years')->format('Y-m-d'),
            ])
            ->create();

        // 10-12: Children (PMC group)
        Member::factory(3)
            ->active()
            ->state([
                'church_group' => 'PMC',
                'matrimony_status' => 'single',
                'date_of_birth' => fake()->dateTimeBetween('-15 years', '-5 years')->format('Y-m-d'),
                'education_level' => 'primary',
            ])
            ->create();

        // 13-15: Choir members
        Member::factory(3)
            ->active()
            ->state(['church_group' => 'Choir'])
            ->create();

        // 16-17: Senior members (Pioneer group)
        Member::factory(2)
            ->active()
            ->state([
                'church_group' => 'Pioneer',
                'date_of_birth' => fake()->dateTimeBetween('-80 years', '-60 years')->format('Y-m-d'),
            ])
            ->create();

        // 18: Inactive member
        Member::factory(1)
            ->state([
                'membership_status' => 'inactive',
                'church_group' => 'Youth',
            ])
            ->create();

        // 19: Transferred member
        Member::factory(1)
            ->state([
                'membership_status' => 'transferred',
                'church_group' => 'C.W.A',
            ])
            ->create();

        // 20: Catholic Action member
        Member::factory(1)
            ->active()
            ->state(['church_group' => 'Catholic Action'])
            ->create();

        $this->command->info('✅ Successfully created 20 diverse parish members!');
        $this->displaySummary();
    }

    /**
     * Display summary of created members
     */
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
        $this->command->info('🎯 Church Groups Represented:');
        $groups = Member::distinct()->pluck('church_group')->filter()->sort();
        foreach ($groups as $group) {
            $count = Member::where('church_group', $group)->count();
            $this->command->info("  • {$group}: {$count} members");
        }

        $this->command->info('');
        $this->command->info('🔗 System Testing Ready:');
        $this->command->info('  • Login: http://127.0.0.1:8000/login');
        $this->command->info('  • Credentials: admin@parish.com / admin123');
        $this->command->info('  • Navigate to Members section to view test data');
        $this->command->info('  • Test search, filtering, and member management features');
    }
}
