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
        Schema::dropIfExists('members');
        
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            
            // Church affiliation (required for all)
            $table->enum('local_church', [
                'St James Kangemi',
                'St Veronica Pembe Tatu', 
                'Our Lady of Consolata Cathedral',
                'St Peter Kiawara',
                'Sacred Heart Kandara'
            ]);
            
            $table->enum('church_group', [
                'PMC',
                'Youth',
                'Young Parents',
                'C.W.A',
                'CMA',
                'Choir',
                'Catholic Action',
                'Pioneer'
            ]);
            
            // Basic information (required for all)
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->enum('gender', ['Male', 'Female', 'Other']);
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('id_number')->nullable();
            $table->string('sponsor')->nullable();
            
            // Additional personal information
            $table->enum('occupation', ['employed', 'self_employed', 'not_employed'])->nullable();
            $table->string('education_level')->nullable();
            $table->text('residence')->nullable();
            
            // Family information
            $table->unsignedBigInteger('family_id')->nullable();
            $table->string('parent')->nullable();
            
            // Church-specific information
            $table->string('minister')->nullable();
            $table->string('tribe')->nullable();
            $table->string('clan')->nullable();
            $table->date('baptism_date')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->enum('matrimony_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            
            // Emergency contact information
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone')->nullable();
            
            // Additional notes
            $table->text('notes')->nullable();
            
            // Membership information
            $table->date('membership_date');
            $table->enum('membership_status', ['active', 'inactive', 'transferred', 'deceased'])->default('active');
            
            $table->timestamps();
            
            // Add indexes for better performance
            $table->index(['local_church', 'church_group']);
            $table->index('membership_status');
            $table->index(['last_name', 'first_name']);
            $table->index('date_of_birth');
            $table->index('church_group');
            $table->index('local_church');
            $table->index('family_id');
        });
        
        // Add foreign key constraint after table creation
        Schema::table('members', function (Blueprint $table) {
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
