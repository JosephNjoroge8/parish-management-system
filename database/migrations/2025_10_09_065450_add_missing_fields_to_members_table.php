<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip this migration to avoid conflicts
        // The members table structure from the original migration is sufficient
        // Additional fields can be added via separate, targeted migrations if needed

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to reverse since we skipped the up() method

    }
};
