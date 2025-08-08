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
        // This migration is postponed to avoid circular dependency
        // Will be handled after members table is created
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to revert yet
    }
};
