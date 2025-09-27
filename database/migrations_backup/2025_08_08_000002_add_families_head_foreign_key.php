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
            // Add head_of_family_id as foreign key to members table
            if (!Schema::hasColumn('families', 'head_of_family_id')) {
                $table->unsignedBigInteger('head_of_family_id')->nullable()->after('family_name');
                $table->foreign('head_of_family_id')->references('id')->on('members')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            if (Schema::hasColumn('families', 'head_of_family_id')) {
                $table->dropForeign(['head_of_family_id']);
                $table->dropColumn('head_of_family_id');
            }
        });
    }
};
