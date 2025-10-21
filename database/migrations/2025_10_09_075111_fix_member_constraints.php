<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip this migration - it's causing conflicts
        // The members table structure is already fine from the original migration

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to reverse since we skipped the up() method

    }
};
