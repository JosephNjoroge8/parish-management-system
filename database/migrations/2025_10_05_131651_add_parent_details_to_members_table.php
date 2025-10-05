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
        Schema::table('members', function (Blueprint $table) {
            // Add parent occupation and residence fields for the registering member
            $table->string('father_occupation')->nullable()->after('father_name');
            $table->string('father_residence')->nullable()->after('father_occupation');
            $table->string('mother_occupation')->nullable()->after('mother_name');
            $table->string('mother_residence')->nullable()->after('mother_occupation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'father_occupation',
                'father_residence',
                'mother_occupation',
                'mother_residence',
            ]);
        });
    }
};
