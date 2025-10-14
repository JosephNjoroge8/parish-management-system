<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table to fix check constraints
        // First, create a new table with correct constraints
        Schema::create('members_new', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['Male', 'Female'])->nullable();
            $table->string('id_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('residence')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->string('local_church')->nullable();
            $table->string('small_christian_community')->nullable();
            $table->enum('church_group', ['men', 'women', 'youth', 'children', 'PMC', 'Youth', 'C.W.A', 'CMA', 'Choir', 'Catholic Action', 'Pioneer'])->nullable();
            $table->text('additional_church_groups')->nullable();
            $table->enum('membership_status', ['active', 'inactive', 'transferred', 'deceased'])->default('active');
            $table->date('membership_date')->nullable();
            $table->date('baptism_date')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->enum('matrimony_status', ['single', 'married', 'widowed', 'separated', 'divorced'])->default('single');
            $table->enum('marriage_type', ['church', 'civil', 'customary', 'come_we_stay'])->nullable();
            $table->string('occupation')->nullable();
            $table->enum('education_level', ['none', 'primary', 'kcpe', 'secondary', 'kcse', 'certificate', 'diploma', 'degree', 'masters', 'phd'])->nullable();
            $table->string('tribe')->nullable();
            $table->string('clan')->nullable();
            $table->unsignedBigInteger('family_id')->nullable();
            $table->string('parent')->nullable();
            $table->string('godparent')->nullable();
            $table->string('minister')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Add all the additional fields
            $table->string('mother_name')->nullable();
            $table->string('father_name')->nullable();
            $table->string('father_occupation')->nullable();
            $table->string('father_residence')->nullable();
            $table->string('mother_occupation')->nullable();
            $table->string('mother_residence')->nullable();
            $table->string('baptized_by')->nullable();
            $table->string('baptism_location')->nullable();
            $table->string('sponsor')->nullable();
            $table->string('confirmation_location')->nullable();
            $table->string('confirmation_register_number')->nullable();
            $table->string('confirmation_number')->nullable();
            $table->date('eucharist_date')->nullable();
            $table->string('eucharist_location')->nullable();
            $table->date('marriage_date')->nullable();
            $table->string('marriage_location')->nullable();
            $table->string('marriage_county')->nullable();
            $table->string('marriage_sub_county')->nullable();
            $table->string('marriage_entry_number')->nullable();
            $table->string('marriage_certificate_number')->nullable();
            $table->string('marriage_religion')->nullable();
            $table->string('marriage_license_number')->nullable();
            $table->string('marriage_officiant_name')->nullable();
            $table->string('marriage_witness1_name')->nullable();
            $table->string('marriage_witness2_name')->nullable();
            $table->string('member_marriage_residence')->nullable();
            $table->string('spouse_name')->nullable();
            $table->string('spouse_age')->nullable();
            $table->string('spouse_residence')->nullable();
            $table->string('spouse_county')->nullable();
            $table->string('spouse_marital_status')->nullable();
            $table->string('spouse_occupation')->nullable();
            $table->string('spouse_father_name')->nullable();
            $table->string('spouse_father_occupation')->nullable();
            $table->string('spouse_father_residence')->nullable();
            $table->string('spouse_mother_name')->nullable();
            $table->string('spouse_mother_occupation')->nullable();
            $table->string('spouse_mother_residence')->nullable();
            $table->string('bride_name')->nullable();
            $table->string('bride_age')->nullable();
            $table->string('bride_residence')->nullable();
            $table->string('bride_county')->nullable();
            $table->string('bride_marital_status')->nullable();
            $table->string('bride_occupation')->nullable();
            $table->string('bride_father_name')->nullable();
            $table->string('bride_father_occupation')->nullable();
            $table->string('bride_father_residence')->nullable();
            $table->string('bride_mother_name')->nullable();
            $table->string('bride_mother_occupation')->nullable();
            $table->string('bride_mother_residence')->nullable();
            $table->string('bridegroom_name')->nullable();
            $table->string('bridegroom_age')->nullable();
            $table->string('bridegroom_residence')->nullable();
            $table->string('bridegroom_county')->nullable();
            $table->string('bridegroom_marital_status')->nullable();
            $table->string('bridegroom_occupation')->nullable();
            $table->string('bridegroom_father_name')->nullable();
            $table->string('bridegroom_father_occupation')->nullable();
            $table->string('bridegroom_father_residence')->nullable();
            $table->string('bridegroom_mother_name')->nullable();
            $table->string('bridegroom_mother_occupation')->nullable();
            $table->string('bridegroom_mother_residence')->nullable();
            $table->string('birth_village')->nullable();
            $table->string('county')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('godparent_id')->nullable();
            $table->unsignedBigInteger('minister_id')->nullable();
            $table->string('husband_name')->nullable();
            $table->string('husband_age')->nullable();
            $table->string('husband_residence')->nullable();
            $table->string('husband_county')->nullable();
            $table->string('husband_marital_status')->nullable();
            $table->string('husband_occupation')->nullable();
            $table->string('husband_father_name')->nullable();
            $table->string('husband_father_occupation')->nullable();
            $table->string('husband_father_residence')->nullable();
            $table->string('husband_mother_name')->nullable();
            $table->string('husband_mother_occupation')->nullable();
            $table->string('husband_mother_residence')->nullable();
            $table->string('wife_name')->nullable();
            $table->string('wife_age')->nullable();
            $table->string('wife_residence')->nullable();
            $table->string('wife_county')->nullable();
            $table->string('wife_marital_status')->nullable();
            $table->string('wife_occupation')->nullable();
            $table->string('wife_father_name')->nullable();
            $table->string('wife_father_occupation')->nullable();
            $table->string('wife_father_residence')->nullable();
            $table->string('wife_mother_name')->nullable();
            $table->string('wife_mother_occupation')->nullable();
            $table->string('wife_mother_residence')->nullable();
            $table->string('sub_county')->nullable();
            $table->string('entry_number')->nullable();
            $table->string('certificate_number')->nullable();
            $table->string('officiant_name')->nullable();
            $table->string('witness1_name')->nullable();
            $table->string('witness2_name')->nullable();
            $table->string('religion')->nullable();
            $table->string('license_number')->nullable();
            $table->string('marriage_spouse')->nullable();
            $table->string('marriage_register_number')->nullable();
            $table->string('marriage_number')->nullable();
            $table->boolean('is_differently_abled')->default(false);
            $table->text('disability_description')->nullable();
            
            $table->foreign('family_id')->references('id')->on('families')->onDelete('set null');
        });
        
        // Copy data from old table to new table
        DB::statement('INSERT INTO members_new SELECT * FROM members');
        
        // Drop old table and rename new table
        Schema::drop('members');
        Schema::rename('members_new', 'members');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This would be complex to reverse, so we'll keep it simple
        // In production, you'd want to backup before running this migration
    }
};
