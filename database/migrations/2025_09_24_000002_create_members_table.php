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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            
            // Personal Information
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['Male', 'Female'])->nullable();
            $table->string('id_number')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('residence')->nullable();
            
            // Emergency Contact
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone')->nullable();
            
            // Church Information
            $table->string('local_church')->nullable();
            $table->string('small_christian_community')->nullable();
            $table->enum('church_group', [
                'PMC', 'Youth', 'C.W.A', 'CMA', 'Choir', 'Catholic Action', 'Pioneer'
            ])->nullable();
            $table->json('additional_church_groups')->nullable(); // For multiple groups
            
            // Membership Information
            $table->enum('membership_status', ['active', 'inactive', 'transferred', 'deceased'])
                   ->default('active');
            $table->date('membership_date')->nullable();
            
            // Sacrament Dates (for quick access)
            $table->date('baptism_date')->nullable();
            $table->date('confirmation_date')->nullable();
            
            // Marriage Information
            $table->enum('matrimony_status', ['single', 'married', 'widowed', 'divorced'])
                   ->default('single');
            $table->enum('marriage_type', ['customary', 'church'])->nullable();
            
            // Personal Details
            $table->string('occupation')->nullable();
            $table->enum('education_level', [
                'none', 'primary', 'kcpe', 'secondary', 'kcse', 
                'certificate', 'diploma', 'degree', 'masters', 'phd'
            ])->nullable();
            
            // Cultural Information
            $table->string('tribe')->nullable();
            $table->string('clan')->nullable();
            
            // Family Relationships
            $table->unsignedBigInteger('family_id')->nullable();
            $table->string('parent')->nullable(); // If child, parent's name
            $table->string('godparent')->nullable();
            $table->string('minister')->nullable(); // Minister who registered them
            
            // System Fields
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['first_name', 'last_name']);
            $table->index('family_id');
            $table->index('membership_status');
            $table->index('church_group');
            $table->index('small_christian_community');
            $table->index('phone');
            $table->index('email');
            $table->index('id_number');
            
            // Foreign key constraints
            $table->foreign('family_id')->references('id')->on('families')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};