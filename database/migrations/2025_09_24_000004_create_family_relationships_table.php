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
        Schema::create('family_relationships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('family_id');
            $table->unsignedBigInteger('member_id');
            $table->enum('relationship_type', [
                'head', 'spouse', 'child', 'parent', 'sibling', 
                'grandparent', 'grandchild', 'other'
            ]);
            $table->string('relationship_details')->nullable(); // e.g., "eldest son", "mother-in-law"
            $table->timestamps();

            // Indexes and constraints
            $table->foreign('family_id')->references('id')->on('families')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->unique(['family_id', 'member_id']); // Prevent duplicate relationships
            $table->index('relationship_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_relationships');
    }
};