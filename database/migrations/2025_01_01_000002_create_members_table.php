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
            $table->string('id_number', 20)->unique()->nullable();
            
            // Contact Information
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('residence')->nullable();
            
            // Church Information
            $table->string('local_church')->nullable();
            $table->string('small_christian_community')->nullable();
            $table->enum('church_group', ['PMC', 'Youth', 'C.W.A', 'CMA', 'Choir', 'Catholic Action', 'Pioneer'])->nullable();
            $table->json('additional_church_groups')->nullable(); // For multiple groups
            
            // Membership Information
            $table->enum('membership_status', ['active', 'inactive', 'transferred', 'deceased'])->default('active');
            $table->date('membership_date')->nullable();
            $table->date('baptism_date')->nullable();
            $table->date('confirmation_date')->nullable();
            
            // Marriage Information
            $table->enum('matrimony_status', ['single', 'married', 'widowed', 'separated'])->default('single');
            $table->enum('marriage_type', ['customary', 'church', 'civil'])->nullable();
            
            // Disability Information
            $table->boolean('is_differently_abled')->default(false);
            $table->text('disability_description')->nullable();
            
            // Personal Details
            $table->string('occupation')->nullable();
            $table->enum('education_level', ['none', 'primary', 'kcpe', 'secondary', 'kcse', 'certificate', 'diploma', 'degree', 'masters', 'phd'])->nullable();
            
            // Family and Cultural Information
            $table->unsignedBigInteger('family_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable(); // Self-referential for family hierarchy
            $table->unsignedBigInteger('godparent_id')->nullable();
            $table->unsignedBigInteger('minister_id')->nullable(); // Who baptized/married them
            $table->string('tribe')->nullable();
            $table->string('clan')->nullable();
            
            // Baptism Record Fields
            $table->string('birth_village')->nullable();
            $table->string('county')->nullable();
            $table->string('baptism_location')->nullable();
            $table->string('baptized_by')->nullable();
            $table->string('sponsor')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('godfather_name')->nullable();
            $table->string('godmother_name')->nullable();
            $table->string('parent')->nullable(); // Legacy parent field
            $table->string('godparent')->nullable(); // Legacy godparent field
            $table->string('minister')->nullable(); // Legacy minister field
            
            // Sacrament Fields
            $table->string('eucharist_location')->nullable();
            $table->date('eucharist_date')->nullable();
            $table->string('confirmation_location')->nullable();
            $table->string('confirmation_register_number', 50)->nullable();
            $table->string('confirmation_number', 50)->nullable();
            
            // Marriage Record Fields
            $table->string('marriage_spouse')->nullable();
            $table->string('marriage_location')->nullable();
            $table->date('marriage_date')->nullable();
            $table->string('marriage_register_number', 50)->nullable();
            $table->string('marriage_number', 50)->nullable();
            $table->string('married_by')->nullable();
            $table->string('witness_1_name')->nullable();
            $table->string('witness_2_name')->nullable();
            $table->string('marriage_certificate_number')->nullable();
            
            // Spouse Information
            $table->string('spouse_name')->nullable();
            $table->string('spouse_father_name')->nullable();
            $table->string('spouse_mother_name')->nullable();
            $table->string('spouse_tribe')->nullable();
            $table->string('spouse_clan')->nullable();
            $table->string('spouse_birth_place')->nullable();
            $table->string('spouse_domicile')->nullable();
            $table->string('spouse_baptized_at')->nullable();
            $table->date('spouse_baptism_date')->nullable();
            $table->string('spouse_widower_widow_of')->nullable();
            $table->enum('spouse_parent_consent', ['Yes', 'No'])->nullable();
            
            // Banas Information
            $table->string('banas_number')->nullable();
            $table->string('banas_church_1')->nullable();
            $table->date('banas_date_1')->nullable();
            $table->string('banas_church_2')->nullable();
            $table->date('banas_date_2')->nullable();
            $table->string('dispensation_from')->nullable();
            $table->string('dispensation_given_by')->nullable();
            
            // Dispensation Information
            $table->string('dispensation_impediment')->nullable();
            $table->string('dispensation_authority')->nullable();
            $table->date('dispensation_date')->nullable();
            
            // Marriage Contract Details
            $table->string('marriage_church')->nullable();
            $table->string('district')->nullable();
            $table->string('province')->nullable();
            $table->string('presence_of')->nullable();
            $table->string('delegated_by')->nullable();
            $table->date('delegation_date')->nullable();
            
            // Witness Information
            $table->string('male_witness_full_name')->nullable();
            $table->string('male_witness_father')->nullable();
            $table->string('male_witness_clan')->nullable();
            $table->string('female_witness_full_name')->nullable();
            $table->string('female_witness_father')->nullable();
            $table->string('female_witness_clan')->nullable();
            
            // Additional Documents
            $table->text('other_documents')->nullable();
            $table->string('civil_marriage_certificate_number')->nullable();
            
            // Additional Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance optimization
            $table->index(['first_name', 'last_name']);
            $table->index(['local_church', 'membership_status']);
            $table->index('church_group');
            $table->index('small_christian_community');
            $table->index('membership_status');
            $table->index('gender');
            $table->index(['date_of_birth', 'gender']);
            $table->index('family_id');
            $table->index('parent_id');
            $table->index('id_number');
            $table->index('phone');
            $table->index('email');
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