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
        if (! Schema::hasTable('family_relationships')) {
            Schema::create('family_relationships', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('family_id')->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->enum('relationship_type', [
                    'head', 'spouse', 'child', 'parent', 'sibling', 'grandparent',
                    'grandchild', 'uncle', 'aunt', 'cousin', 'other',
                ])->index();
                $table->boolean('is_primary')->default(false); // Primary relationship in family
                $table->text('notes')->nullable();
                $table->timestamps();

                // Ensure unique member per family
                $table->unique(['family_id', 'member_id'], 'unique_family_member');

                // Indexes for performance
                $table->index(['family_id', 'relationship_type']);
                $table->index(['member_id', 'relationship_type']);
                $table->index(['is_primary', 'family_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_relationships');
    }
};
