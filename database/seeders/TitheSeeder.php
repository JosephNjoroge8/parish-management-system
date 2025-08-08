<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tithe;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;

class TitheSeeder extends Seeder
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

        // Clear existing tithe data to avoid duplicates
        Tithe::truncate();
        $this->command->info('Cleared existing tithe records.');

        $contributions = [];
        
        // Create systematic contributions for each member
        foreach ($members as $member) {
            // Skip children for tithe contributions (under 18)
            $age = Carbon::parse($member->date_of_birth)->age;
            if ($age < 16) {
                continue; // Children don't typically give tithes
            }
            
            // Random number of contributions per member (1-6 months)
            $contributionCount = rand(1, 6);
            
            for ($i = 0; $i < $contributionCount; $i++) {
                $contributionDate = Carbon::now()->subMonths(rand(0, 6));
                
                $contributions[] = [
                    'member_id' => $member->id,
                    'amount' => $this->getRandomAmount($age),
                    'tithe_type' => $this->getRandomContributionType(),
                    'payment_method' => $this->getRandomPaymentMethod(),
                    'date_given' => $contributionDate->format('Y-m-d'),
                    'purpose' => $this->getContributionPurpose(),
                    'receipt_number' => 'RCP' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    'reference_number' => 'REF' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT) . $contributionDate->format('y'),
                    'notes' => $this->getContributionNote(),
                    'recorded_by' => $admin ? $admin->id : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insert in batches for better performance
        $chunks = array_chunk($contributions, 50);
        foreach ($chunks as $chunk) {
            Tithe::insert($chunk);
        }

        $this->command->info('Created ' . count($contributions) . ' tithe records.');
    }

    private function getRandomAmount(int $age): float
    {
        if ($age < 18) {
            return rand(50, 200); // Young people contribute less
        } elseif ($age < 30) {
            return rand(500, 2000); // Young adults
        } elseif ($age < 50) {
            return rand(1000, 5000); // Working adults
        } else {
            return rand(800, 3000); // Older adults
        }
    }

    private function getRandomContributionType(): string
    {
        $types = [
            'tithe', 
            'offering', 
            'special_collection', 
            'donation', 
            'thanksgiving', 
            'project_contribution'
        ];
        $weights = [40, 30, 10, 8, 7, 5]; // Tithe most common
        
        $randomIndex = $this->weightedRandom($weights);
        return $types[$randomIndex];
    }

    private function getRandomPaymentMethod(): string
    {
        $methods = ['cash', 'mobile_money', 'bank_transfer', 'check', 'card'];
        $weights = [50, 30, 10, 5, 5]; // Cash most common
        
        $randomIndex = $this->weightedRandom($weights);
        return $methods[$randomIndex];
    }

    private function getContributionPurpose(): ?string
    {
        $purposes = [
            'Regular Sunday Offering',
            'Tithe - 10% of Income',
            'Special Thanksgiving',
            'Church Building Fund',
            'Community Outreach',
            'Easter Special Collection',
            'Christmas Offering',
            'Harvest Thanksgiving',
            'Youth Ministry Support',
            'Choir Ministry',
            'Church Maintenance',
            null, // Sometimes no specific purpose
        ];
        
        return $purposes[array_rand($purposes)];
    }

    private function getContributionNote(): ?string
    {
        $notes = [
            'Regular tithe contribution',
            'Sunday offering',
            'Special thanksgiving offering',
            'Building fund contribution',
            'Christmas special offering',
            'Easter offering',
            'Harvest thanksgiving',
            'Monthly pledge fulfillment',
            'Gratitude offering',
            null, // Sometimes no notes
        ];
        
        return $notes[array_rand($notes)];
    }

    private function weightedRandom(array $weights): int
    {
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($weights as $index => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return $index;
            }
        }
        
        return 0; // Fallback
    }
}
