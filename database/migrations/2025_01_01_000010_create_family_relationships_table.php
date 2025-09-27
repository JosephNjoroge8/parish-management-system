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
                'grandparent', 'grandchild', 'uncle_aunt', 
                'nephew_niece', 'cousin', 'other'
            ]);
            $table->boolean('primary_contact')->default(false);
            $table->boolean('emergency_contact')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('family_id')->references('id')->on('families')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');

            // Ensure unique relationship per family-member combination
            $table->unique(['family_id', 'member_id']);

            // Indexes
            $table->index(['family_id', 'relationship_type']);
            $table->index(['member_id', 'relationship_type']);
            $table->index('primary_contact');
            $table->index('emergency_contact');
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