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
        // Add foreign key constraints for members table
        Schema::table('members', function (Blueprint $table) {
            $table->foreign('family_id')->references('id')->on('families')->onDelete('set null');
            $table->foreign('parent_id')->references('id')->on('members')->onDelete('set null');
            $table->foreign('godparent_id')->references('id')->on('members')->onDelete('set null');
            $table->foreign('minister_id')->references('id')->on('members')->onDelete('set null');
        });

        // Add foreign key constraints for families table
        Schema::table('families', function (Blueprint $table) {
            $table->foreign('head_of_family_id')->references('id')->on('members')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['family_id']);
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['godparent_id']);
            $table->dropForeign(['minister_id']);
        });

        Schema::table('families', function (Blueprint $table) {
            $table->dropForeign(['head_of_family_id']);
            $table->dropForeign(['created_by']);
        });
    }
};