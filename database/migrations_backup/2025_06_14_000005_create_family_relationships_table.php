<?php
// filepath: database/migrations/2025_06_14_000005_create_family_relationships_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('family_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->enum('relationship_type', [
                'head',
                'spouse',
                'child',
                'parent',
                'sibling',
                'grandparent',
                'grandchild',
                'uncle_aunt',
                'nephew_niece',
                'cousin',
                'other'
            ]);
            $table->boolean('primary_contact')->default(false);
            $table->boolean('emergency_contact')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes and constraints
            $table->index(['family_id', 'member_id']);
            $table->index(['relationship_type']);
            $table->index(['primary_contact']);
            $table->index(['emergency_contact']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('family_relationships');
    }
};