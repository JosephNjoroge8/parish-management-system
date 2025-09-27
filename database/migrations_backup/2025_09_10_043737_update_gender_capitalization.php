<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Update gender values to ensure consistent capitalization.
     */
    public function up(): void
    {
        // Update gender values in members table
        DB::statement("UPDATE members SET gender = 'Male' WHERE gender = 'male'");
        DB::statement("UPDATE members SET gender = 'Female' WHERE gender = 'female'");
        
        // Update gender values in users table
        DB::statement("UPDATE users SET gender = 'Male' WHERE gender = 'male'");
        DB::statement("UPDATE users SET gender = 'Female' WHERE gender = 'female'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // If needed, we could reverse the capitalization, but it's generally not necessary
        // DB::statement("UPDATE members SET gender = 'male' WHERE gender = 'Male'");
        // DB::statement("UPDATE members SET gender = 'female' WHERE gender = 'Female'");
        // DB::statement("UPDATE users SET gender = 'male' WHERE gender = 'Male'");
        // DB::statement("UPDATE users SET gender = 'female' WHERE gender = 'Female'");
    }
};
