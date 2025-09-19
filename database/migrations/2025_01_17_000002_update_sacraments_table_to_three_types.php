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
        // Delete any existing sacrament records that are not in the 3 allowed types
        DB::table('sacraments')
            ->whereNotIn('sacrament_type', ['baptism', 'confirmation', 'marriage'])
            ->delete();
            
        // Update the enum to only allow 3 sacrament types as requested
        DB::statement("ALTER TABLE sacraments MODIFY COLUMN sacrament_type ENUM('baptism', 'confirmation', 'marriage')");
        
        // Drop the unique constraint since members can have multiple sacraments
        try {
            Schema::table('sacraments', function (Blueprint $table) {
                $table->dropUnique('unique_member_sacrament');
            });
        } catch (Exception $e) {
            // Constraint might not exist, ignore error
        }
        
        // Add composite unique constraint to allow only one of each sacrament type per member
        Schema::table('sacraments', function (Blueprint $table) {
            $table->unique(['member_id', 'sacrament_type'], 'unique_member_sacrament_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new unique constraint
        try {
            Schema::table('sacraments', function (Blueprint $table) {
                $table->dropUnique('unique_member_sacrament_type');
            });
        } catch (Exception $e) {
            // Constraint might not exist, ignore error
        }
        
        // Restore original enum with all 7 sacraments
        DB::statement("ALTER TABLE sacraments MODIFY COLUMN sacrament_type ENUM('baptism', 'eucharist', 'confirmation', 'reconciliation', 'anointing', 'marriage', 'holy_orders')");
        
        // Restore original unique constraint
        try {
            Schema::table('sacraments', function (Blueprint $table) {
                $table->unique(['member_id', 'sacrament_type'], 'unique_member_sacrament');
            });
        } catch (Exception $e) {
            // Constraint might not exist, ignore error
        }
    }
};
