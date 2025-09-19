<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the baptism_records table exists before trying to modify it
        if (!Schema::hasTable('baptism_records')) {
            // Table doesn't exist yet, skip this migration
            return;
        }
        
        Schema::table('baptism_records', function (Blueprint $table) {
            // Add new fields for enhanced baptism records
            if (!Schema::hasColumn('baptism_records', 'minister')) {
                $table->string('minister')->nullable()->after('baptism_date');
            }
            
            if (!Schema::hasColumn('baptism_records', 'place_of_baptism')) {
                $table->string('place_of_baptism')->nullable()->after('minister');
            }
            
            if (!Schema::hasColumn('baptism_records', 'godfather_name')) {
                $table->string('godfather_name')->nullable()->after('place_of_baptism');
            }
            
            if (!Schema::hasColumn('baptism_records', 'godmother_name')) {
                $table->string('godmother_name')->nullable()->after('godfather_name');
            }
            
            if (!Schema::hasColumn('baptism_records', 'godfather_religion')) {
                $table->string('godfather_religion')->nullable()->after('godmother_name');
            }
            
            if (!Schema::hasColumn('baptism_records', 'godmother_religion')) {
                $table->string('godmother_religion')->nullable()->after('godfather_religion');
            }
            
            if (!Schema::hasColumn('baptism_records', 'certificate_number')) {
                $table->string('certificate_number', 100)->nullable()->unique()->after('godmother_religion');
            }
            
            if (!Schema::hasColumn('baptism_records', 'remarks')) {
                $table->text('remarks')->nullable()->after('certificate_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if the baptism_records table exists before trying to modify it
        if (!Schema::hasTable('baptism_records')) {
            return;
        }
        
        Schema::table('baptism_records', function (Blueprint $table) {
            $table->dropColumn([
                'minister',
                'place_of_baptism', 
                'godfather_name',
                'godmother_name',
                'godfather_religion',
                'godmother_religion',
                'certificate_number',
                'remarks'
            ]);
        });
    }
};
