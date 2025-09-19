<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update any existing "Young Parents" members to "Youth" BEFORE altering the column
        DB::table('members')
            ->where('church_group', 'Young Parents')
            ->update(['church_group' => 'Youth']);
            
        // Clean up any invalid church groups BEFORE altering the column
        DB::table('members')
            ->whereNotIn('church_group', ['PMC', 'Youth', 'C.W.A', 'CMA', 'Choir', 'Catholic Action', 'Pioneer'])
            ->update(['church_group' => 'Youth']);

        Schema::table('members', function (Blueprint $table) {
            // Add small Christian community field if not exists
            if (!Schema::hasColumn('members', 'small_christian_community')) {
                $table->string('small_christian_community')->nullable()->after('local_church');
                $table->index('small_christian_community');
            }
            
            // Add marriage type field if not exists
            if (!Schema::hasColumn('members', 'marriage_type')) {
                $table->enum('marriage_type', ['customary', 'church'])->nullable()->after('matrimony_status');
            }
            
            // Add multiple group membership support
            if (!Schema::hasColumn('members', 'additional_church_groups')) {
                $table->json('additional_church_groups')->nullable()->after('church_group');
            }
            
            // Change sponsor to godparent for consistency
            if (Schema::hasColumn('members', 'sponsor')) {
                $table->renameColumn('sponsor', 'godparent');
            } else if (!Schema::hasColumn('members', 'godparent')) {
                $table->string('godparent')->nullable()->after('id_number');
            }
            
            // Ensure ID number is nullable (already should be)
            if (Schema::hasColumn('members', 'id_number')) {
                $table->string('id_number')->nullable()->change();
            }
        });
        
        // Update education_level column to use enum with Kenyan system
        if (Schema::hasColumn('members', 'education_level')) {
            // First backup existing data
            $existingEducationData = DB::table('members')
                ->whereNotNull('education_level')
                ->where('education_level', '!=', '')
                ->get(['id', 'education_level']);
            
            // Drop the existing column
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn('education_level');
            });
        }
        
        // Add new education_level enum column
        Schema::table('members', function (Blueprint $table) {
            $table->enum('education_level', [
                'none',
                'primary',
                'kcpe', 
                'secondary',
                'kcse',
                'certificate',
                'diploma',
                'degree',
                'masters',
                'phd'
            ])->nullable()->after('occupation');
        });
        
        // Migrate existing education data to new format
        if (isset($existingEducationData)) {
            foreach ($existingEducationData as $member) {
                $newLevel = $this->mapEducationLevel($member->education_level);
                if ($newLevel) {
                    DB::table('members')
                        ->where('id', $member->id)
                        ->update(['education_level' => $newLevel]);
                }
            }
        }

        // Now update church_group enum to remove "Young Parents" and ensure 7 groups only
        DB::statement("ALTER TABLE members MODIFY COLUMN church_group ENUM('PMC', 'Youth', 'C.W.A', 'CMA', 'Choir', 'Catholic Action', 'Pioneer')");
        
        // Add indexes for better performance
        Schema::table('members', function (Blueprint $table) {
            if (!$this->hasIndex('members', 'education_level')) {
                $table->index('education_level');
            }
            if (!$this->hasIndex('members', 'marriage_type')) {
                $table->index('marriage_type');
            }
            if (!$this->hasIndex('members', 'additional_church_groups')) {
                $table->index('additional_church_groups');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'small_christian_community')) {
                $table->dropIndex(['small_christian_community']);
                $table->dropColumn('small_christian_community');
            }
            
            if (Schema::hasColumn('members', 'marriage_type')) {
                $table->dropIndex(['marriage_type']);
                $table->dropColumn('marriage_type');
            }
            
            if (Schema::hasColumn('members', 'additional_church_groups')) {
                $table->dropIndex(['additional_church_groups']);
                $table->dropColumn('additional_church_groups');
            }
            
            if (Schema::hasColumn('members', 'godparent')) {
                $table->renameColumn('godparent', 'sponsor');
            }
            
            if (Schema::hasColumn('members', 'education_level')) {
                $table->dropIndex(['education_level']);
                $table->dropColumn('education_level');
            }
        });
        
        // Add back education_level as string
        Schema::table('members', function (Blueprint $table) {
            $table->string('education_level')->nullable()->after('occupation');
        });
        
        // Restore original church_group enum (if needed)
        DB::statement("ALTER TABLE members MODIFY COLUMN church_group ENUM('PMC', 'Youth', 'Young Parents', 'C.W.A', 'CMA', 'Choir', 'Catholic Action', 'Pioneer')");
    }
    
    /**
     * Map old education levels to new enum values
     */
    private function mapEducationLevel($oldLevel): ?string
    {
        $oldLevel = strtolower(trim($oldLevel));
        
        $mapping = [
            'none' => 'none',
            'no formal education' => 'none',
            'primary' => 'primary',
            'primary school' => 'primary', 
            'primary education' => 'primary',
            'kcpe' => 'kcpe',
            'k.c.p.e' => 'kcpe',
            'secondary' => 'secondary',
            'secondary school' => 'secondary',
            'secondary education' => 'secondary',
            'high school' => 'secondary',
            'kcse' => 'kcse',
            'k.c.s.e' => 'kcse',
            'certificate' => 'certificate',
            'diploma' => 'diploma',
            'degree' => 'degree',
            'university' => 'degree',
            'bachelor' => 'degree',
            'bachelors' => 'degree',
            'undergraduate' => 'degree',
            'masters' => 'masters',
            'master' => 'masters',
            'postgraduate' => 'masters',
            'phd' => 'phd',
            'doctorate' => 'phd',
            'doctoral' => 'phd',
        ];
        
        return $mapping[$oldLevel] ?? null;
    }
    
    /**
     * Check if an index exists on a table
     */
    private function hasIndex($table, $column): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table}");
        foreach ($indexes as $index) {
            if ($index->Column_name === $column) {
                return true;
            }
        }
        return false;
    }
};
