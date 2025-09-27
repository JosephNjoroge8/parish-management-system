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
        Schema::table('families', function (Blueprint $table) {
            // Make head_of_family_id nullable if it exists and isn't already nullable
            if (Schema::hasColumn('families', 'head_of_family_id')) {
                $table->unsignedBigInteger('head_of_family_id')->nullable()->change();
            }
            
            // Drop the old head_of_family string column if it still exists
            if (Schema::hasColumn('families', 'head_of_family')) {
                $table->dropColumn('head_of_family');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            // Re-add the head_of_family string column
            if (!Schema::hasColumn('families', 'head_of_family')) {
                $table->string('head_of_family')->nullable()->after('family_name');
            }
        });
    }
};
