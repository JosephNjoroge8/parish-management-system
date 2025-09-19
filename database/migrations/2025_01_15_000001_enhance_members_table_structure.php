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
        // First, handle education_level column modification
        if (Schema::hasColumn('members', 'education_level')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn('education_level');
            });
        }
        
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
            
            // Add enhanced education level as enum (Kenyan system)
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
            
            // Add multiple group membership support
            if (!Schema::hasColumn('members', 'additional_church_groups')) {
                $table->json('additional_church_groups')->nullable()->after('church_group');
            }
            
            // Ensure ID number is nullable (already should be)
            $table->string('id_number')->nullable()->change();
        });

        // First, update any existing "Young Parents" members to "Youth" BEFORE altering the column
        DB::table('members')
            ->where('church_group', 'Young Parents')
            ->update(['church_group' => 'Youth']);
            
        // Clean up any invalid church groups BEFORE altering the column
        DB::table('members')
            ->whereNotIn('church_group', ['PMC', 'Youth', 'C.W.A', 'CMA', 'Choir', 'Catholic Action', 'Pioneer'])
            ->update(['church_group' => 'Youth']);

        // Now update church_group enum to remove "Young Parents" and ensure 7 groups only
        DB::statement("ALTER TABLE members MODIFY COLUMN church_group ENUM('PMC', 'Youth', 'C.W.A', 'CMA', 'Choir', 'Catholic Action', 'Pioneer')");
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
                $table->dropColumn('marriage_type');
            }
            
            if (Schema::hasColumn('members', 'additional_church_groups')) {
                $table->dropColumn('additional_church_groups');
            }
            
            if (Schema::hasColumn('members', 'education_level')) {
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
};
