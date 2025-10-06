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
        Schema::table('members', function (Blueprint $table) {
            // Add marital_status column (the code expects this column)
            if (!Schema::hasColumn('members', 'marital_status')) {
                $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])
                      ->default('single')
                      ->after('gender')
                      ->comment('Marital status of the member');
                
                // Add index for better query performance
                $table->index('marital_status');
            }
        });
        
        // Migrate data from matrimony_status to marital_status if matrimony_status exists
        if (Schema::hasColumn('members', 'matrimony_status')) {
            // Convert matrimony_status values to marital_status
            DB::statement("
                UPDATE members 
                SET marital_status = CASE 
                    WHEN matrimony_status = 'Single' THEN 'single'
                    WHEN matrimony_status = 'Married' THEN 'married'
                    WHEN matrimony_status = 'Divorced' THEN 'divorced'
                    WHEN matrimony_status = 'Widowed' THEN 'widowed'
                    WHEN matrimony_status LIKE '%single%' THEN 'single'
                    WHEN matrimony_status LIKE '%married%' THEN 'married'
                    WHEN matrimony_status LIKE '%divorce%' THEN 'divorced'
                    WHEN matrimony_status LIKE '%widow%' THEN 'widowed'
                    ELSE 'single'
                END
                WHERE matrimony_status IS NOT NULL
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'marital_status')) {
                $table->dropIndex(['marital_status']);
                $table->dropColumn('marital_status');
            }
        });
    }
};
