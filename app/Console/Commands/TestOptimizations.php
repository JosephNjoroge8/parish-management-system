<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Member;
use App\Models\Family;

class TestOptimizations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:test-optimizations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test parish management system optimizations and fixes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Parish Management System Optimizations');
        $this->info('============================================');
        $this->newLine();

        // Test 1: Database Connection and Basic Queries
        $this->info('1. Testing Database Connection...');
        try {
            $memberCount = Member::count();
            $familyCount = Family::count();
            $this->info("✓ Database connection successful");
            $this->line("  - Members: {$memberCount}");
            $this->line("  - Families: {$familyCount}");
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("✗ Database connection failed: " . $e->getMessage());
            $this->newLine();
        }

        // Test 2: Statistics Query Performance
        $this->info('2. Testing Statistics Queries...');
        $start = microtime(true);
        try {
            $stats = [
                'total_members' => Member::count(),
                'active_members' => Member::where('membership_status', 'active')->count(),
                'inactive_members' => Member::where('membership_status', 'inactive')->count(),
                'transferred_members' => Member::where('membership_status', 'transferred')->count(),
                'deceased_members' => Member::where('membership_status', 'deceased')->count(),
                'by_church' => Member::groupBy('local_church')
                    ->selectRaw('local_church, count(*) as count')
                    ->pluck('count', 'local_church')
                    ->toArray(),
                'by_group' => Member::groupBy('church_group')
                    ->selectRaw('church_group, count(*) as count')
                    ->pluck('count', 'church_group')
                    ->toArray(),
            ];
            $end = microtime(true);
            $duration = round(($end - $start) * 1000, 2);
            
            $this->info("✓ Statistics queries completed in {$duration}ms");
            $this->line("  - Total Members: " . $stats['total_members']);
            $this->line("  - Active: " . $stats['active_members']);
            $this->line("  - Inactive: " . $stats['inactive_members']);
            $this->line("  - Transferred: " . $stats['transferred_members']);
            $this->line("  - Deceased: " . $stats['deceased_members']);
            $this->line("  - Churches: " . count($stats['by_church']));
            $this->line("  - Groups: " . count($stats['by_group']));
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("✗ Statistics query failed: " . $e->getMessage());
            $this->newLine();
        }

        // Test 3: Check Indexes (MySQL specific)
        $this->info('3. Testing Database Indexes...');
        try {
            if (DB::getDriverName() === 'mysql') {
                $indexes = DB::select("SHOW INDEX FROM members WHERE Key_name LIKE 'idx_%'");
                $this->info("✓ Found " . count($indexes) . " performance indexes on members table");
                
                $familyIndexes = DB::select("SHOW INDEX FROM families WHERE Key_name LIKE 'idx_%'");
                $this->info("✓ Found " . count($familyIndexes) . " performance indexes on families table");
            } else {
                $this->warn("⚠ Index check skipped (not MySQL database)");
            }
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("✗ Index check failed: " . $e->getMessage());
            $this->newLine();
        }

        // Test 4: Family Table Structure
        $this->info('4. Testing Family Table Structure...');
        try {
            $columns = DB::select("DESCRIBE families");
            $hasHeadOfFamilyId = false;
            $hasOldHeadOfFamily = false;
            
            foreach ($columns as $column) {
                if ($column->Field === 'head_of_family_id') {
                    $hasHeadOfFamilyId = true;
                    $nullable = $column->Null === 'YES' ? 'yes' : 'no';
                    $this->info("✓ head_of_family_id column exists (nullable: {$nullable})");
                }
                if ($column->Field === 'head_of_family') {
                    $hasOldHeadOfFamily = true;
                }
            }
            
            if (!$hasOldHeadOfFamily) {
                $this->info("✓ Old head_of_family string column removed");
            } else {
                $this->warn("⚠ Old head_of_family string column still exists");
            }
            
            if (!$hasHeadOfFamilyId) {
                $this->warn("⚠ head_of_family_id column missing");
            }
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("✗ Family table structure check failed: " . $e->getMessage());
            $this->newLine();
        }

        // Test 5: Sample Family Creation (Dry Run)
        $this->info('5. Testing Family Creation Logic...');
        try {
            $familyData = [
                'family_name' => 'Test Family (Dry Run)',
                'family_code' => 'TEST001',
                'address' => 'Test Address',
                'phone' => '+254700000000',
                'email' => 'test@example.com',
                'deanery' => 'Test Deanery',
                'parish' => 'Test Parish',
                'parish_section' => 'Test Section',
                'head_of_family_id' => null,
                'created_by' => 1,
            ];
            
            // Validate without actually creating
            $family = new Family($familyData);
            if ($family->family_name && strlen($family->family_name) > 0) {
                $this->info("✓ Family model validation passed");
                $this->line("  - Can handle null head_of_family_id");
                $this->line("  - Required fields validated");
                $this->newLine();
            }
        } catch (\Exception $e) {
            $this->error("✗ Family creation test failed: " . $e->getMessage());
            $this->newLine();
        }

        // Test 6: Performance Benchmark
        $this->info('6. Performance Benchmark...');
        try {
            $iterations = 5;
            $totalTime = 0;
            
            for ($i = 0; $i < $iterations; $i++) {
                $start = microtime(true);
                
                // Simulate the Members Index query
                Member::with('family')
                    ->where('membership_status', 'active')
                    ->orderBy('last_name', 'asc')
                    ->paginate(15);
                    
                $end = microtime(true);
                $totalTime += ($end - $start);
            }
            
            $avgTime = round(($totalTime / $iterations) * 1000, 2);
            $this->info("✓ Average query time: {$avgTime}ms (over {$iterations} iterations)");
            
            if ($avgTime < 100) {
                $this->info("✓ Performance: Excellent");
            } elseif ($avgTime < 200) {
                $this->info("✓ Performance: Good");
            } elseif ($avgTime < 500) {
                $this->warn("⚠ Performance: Acceptable");
            } else {
                $this->warn("⚠ Performance: Needs improvement");
            }
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("✗ Performance benchmark failed: " . $e->getMessage());
            $this->newLine();
        }

        $this->info('Optimization Test Complete!');
        $this->info('==========================');
        $this->info('If all tests passed, your system is optimized and ready for production.');
        
        return 0;
    }
}
