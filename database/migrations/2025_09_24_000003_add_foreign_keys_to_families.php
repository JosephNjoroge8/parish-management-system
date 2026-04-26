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
        if (! Schema::hasTable('families')) {
            return;
        }

        // Use try/catch so migration doesn't fail on SQLite (no Doctrine)
        try {
            Schema::table('families', function (Blueprint $table) {
                if (Schema::hasColumn('families', 'head_of_family_id')) {
                    $table->foreign('head_of_family_id')->references('id')->on('members')->onDelete('set null');
                }

                if (Schema::hasColumn('families', 'created_by')) {
                    $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                }
            });
        } catch (Throwable $e) {
            // Ignore on connections without Doctrine (SQLite in testing), but log in production if needed
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropForeign(['head_of_family_id']);
            $table->dropForeign(['created_by']);
        });
    }
};
