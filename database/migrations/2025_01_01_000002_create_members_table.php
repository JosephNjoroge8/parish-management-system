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

            // ======================================
            // CORE PERSONAL INFORMATION
            // ======================================
            $table->string('first_name', 100)->index();
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100)->index();
            $table->date('date_of_birth')->nullable()->index();
            $table->enum('gender', ['Male', 'Female'])->nullable()->index();
            $table->string('id_number', 20)->unique()->nullable();

            // ======================================
            // MARITAL STATUS INFORMATION (UNIFIED)
            // ======================================
            $table->enum('matrimony_status', ['single', 'married', 'widowed', 'separated', 'divorced'])
                ->default('single')->index();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])
                ->default('single')->index()->comment('Marital status compatibility field');
            $table->enum('marriage_type', ['customary', 'church', 'civil', 'both'])->nullable();

            // ======================================
            // CONTACT INFORMATION
            // ======================================
            $table->string('phone', 20)->nullable()->index();
            $table->string('email', 100)->nullable()->index();
            $table->text('residence')->nullable();

            // ======================================
            // CHURCH INFORMATION
            // ======================================
            $table->string('local_church', 100)->nullable()->index();
            $table->string('small_christian_community', 100)->nullable()->index();
            $table->enum('church_group', [
                'PMC', 'Youth', 'Young Parents', 'C.W.A', 'CMA', 'Choir', 'Catholic Action', 'Pioneer',
            ])->nullable()->index();
            $table->json('additional_church_groups')->nullable();

            // ======================================
            // MEMBERSHIP INFORMATION
            // ======================================
            $table->enum('membership_status', ['active', 'inactive', 'transferred', 'deceased'])
                ->default('active')->index();
            $table->date('membership_date')->nullable()->index();

            // ======================================
            // ACCESSIBILITY AND INCLUSION
            // ======================================
            $table->boolean('is_differently_abled')->default(false)->index();
            $table->text('disability_description')->nullable();

            // ======================================
            // EDUCATION AND OCCUPATION
            // ======================================
            $table->string('occupation', 100)->nullable()->index();
            $table->enum('education_level', [
                'none', 'primary', 'kcpe', 'secondary', 'kcse', 'certificate',
                'diploma', 'degree', 'masters', 'phd', 'other',
            ])->nullable()->index();

            // ======================================
            // FAMILY RELATIONSHIPS
            // ======================================
            $table->unsignedBigInteger('family_id')->nullable()->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->unsignedBigInteger('godparent_id')->nullable()->index();
            $table->unsignedBigInteger('minister_id')->nullable()->index();

            // ======================================
            // CULTURAL AND TRIBAL INFORMATION
            // ======================================
            $table->string('tribe', 50)->nullable()->index();
            $table->string('clan', 50)->nullable();

            // ======================================
            // FAMILY INFORMATION (STRING FIELDS)
            // ======================================
            $table->string('parent', 100)->nullable(); // Father's name
            $table->string('mother_name', 100)->nullable();
            $table->string('godparent', 100)->nullable();
            $table->string('minister', 100)->nullable();

            // ======================================
            // PARENT DETAILS (COMPREHENSIVE)
            // ======================================
            $table->string('father_name', 100)->nullable();
            $table->string('father_occupation', 100)->nullable();
            $table->string('father_residence', 200)->nullable();
            $table->string('mother_occupation', 100)->nullable();
            $table->string('mother_residence', 200)->nullable();

            // ======================================
            // GEOGRAPHICAL INFORMATION
            // ======================================
            $table->string('birth_village', 100)->nullable();
            $table->string('county', 50)->nullable()->index();
            $table->string('district', 50)->nullable();
            $table->string('province', 50)->nullable();

            // ======================================
            // SACRAMENT INFORMATION
            // ======================================

            // Baptism Information
            $table->date('baptism_date')->nullable()->index();
            $table->string('baptism_location', 100)->nullable();
            $table->string('baptized_by', 100)->nullable();
            $table->string('sponsor', 100)->nullable();

            // Confirmation Information
            $table->date('confirmation_date')->nullable()->index();
            $table->string('confirmation_location', 100)->nullable();
            $table->string('confirmation_register_number', 50)->nullable()->unique();
            $table->string('confirmation_number', 50)->nullable()->unique();

            // First Communion Information
            $table->date('eucharist_date')->nullable()->index();
            $table->string('eucharist_location', 100)->nullable();

            // Extended Godparent Information
            $table->string('godfather_name', 100)->nullable();
            $table->string('godmother_name', 100)->nullable();

            // ======================================
            // MARRIAGE INFORMATION (COMPREHENSIVE)
            // ======================================

            // Core Marriage Details
            $table->date('marriage_date')->nullable()->index();
            $table->string('marriage_location', 100)->nullable();
            $table->string('marriage_county', 50)->nullable();
            $table->string('marriage_sub_county', 50)->nullable();
            $table->string('marriage_entry_number', 50)->nullable()->unique();
            $table->string('marriage_certificate_number', 50)->nullable()->unique();
            $table->string('marriage_religion', 50)->nullable();
            $table->string('marriage_license_number', 50)->nullable();
            $table->string('marriage_officiant_name', 100)->nullable();
            $table->string('marriage_witness1_name', 100)->nullable();
            $table->string('marriage_witness2_name', 100)->nullable();
            $table->string('member_marriage_residence', 255)->nullable();

            // ======================================
            // SPOUSE INFORMATION (DETAILED)
            // ======================================
            $table->string('spouse_name', 100)->nullable()->index();
            $table->integer('spouse_age')->nullable();
            $table->string('spouse_residence', 200)->nullable();
            $table->string('spouse_county', 50)->nullable();
            $table->string('spouse_marital_status', 20)->nullable();
            $table->string('spouse_occupation', 100)->nullable();

            // Spouse Family Information
            $table->string('spouse_father_name', 100)->nullable();
            $table->string('spouse_father_occupation', 100)->nullable();
            $table->string('spouse_father_residence', 200)->nullable();
            $table->string('spouse_mother_name', 100)->nullable();
            $table->string('spouse_mother_occupation', 100)->nullable();
            $table->string('spouse_mother_residence', 200)->nullable();

            // Extended Spouse Details
            $table->string('spouse_tribe', 50)->nullable();
            $table->string('spouse_clan', 50)->nullable();
            $table->string('spouse_birth_place', 100)->nullable();
            $table->string('spouse_domicile', 100)->nullable();
            $table->string('spouse_baptized_at', 100)->nullable();
            $table->date('spouse_baptism_date')->nullable();
            $table->string('spouse_widower_widow_of', 100)->nullable();
            $table->enum('spouse_parent_consent', ['Yes', 'No'])->nullable();

            // ======================================
            // MARRIAGE CERTIFICATE TEMPLATE FIELDS
            // ======================================

            // Husband Fields (auto-populated based on gender)
            $table->string('husband_name', 100)->nullable();
            $table->string('husband_age', 10)->nullable();
            $table->string('husband_residence', 200)->nullable();
            $table->string('husband_county', 50)->nullable();
            $table->string('husband_marital_status', 20)->nullable();
            $table->string('husband_occupation', 100)->nullable();
            $table->string('husband_father_name', 100)->nullable();
            $table->string('husband_father_occupation', 100)->nullable();
            $table->string('husband_father_residence', 200)->nullable();
            $table->string('husband_mother_name', 100)->nullable();
            $table->string('husband_mother_occupation', 100)->nullable();
            $table->string('husband_mother_residence', 200)->nullable();

            // Wife Fields (auto-populated based on gender)
            $table->string('wife_name', 100)->nullable();
            $table->string('wife_age', 10)->nullable();
            $table->string('wife_residence', 200)->nullable();
            $table->string('wife_county', 50)->nullable();
            $table->string('wife_marital_status', 20)->nullable();
            $table->string('wife_occupation', 100)->nullable();
            $table->string('wife_father_name', 100)->nullable();
            $table->string('wife_father_occupation', 100)->nullable();
            $table->string('wife_father_residence', 200)->nullable();
            $table->string('wife_mother_name', 100)->nullable();
            $table->string('wife_mother_occupation', 100)->nullable();
            $table->string('wife_mother_residence', 200)->nullable();

            // ======================================
            // LEGACY MARRIAGE FIELDS (COMPATIBILITY)
            // ======================================
            $table->string('marriage_spouse', 100)->nullable();
            $table->string('marriage_register_number', 50)->nullable();
            $table->string('marriage_number', 50)->nullable();
            $table->string('married_by', 100)->nullable();
            $table->string('witness_1_name', 100)->nullable();
            $table->string('witness_2_name', 100)->nullable();
            $table->string('marriage_church', 100)->nullable();

            // ======================================
            // MARRIAGE CERTIFICATE TEMPLATE MAPPINGS
            // ======================================
            $table->string('sub_county', 50)->nullable();
            $table->string('entry_number', 50)->nullable();
            $table->string('certificate_number', 50)->nullable();
            $table->string('officiant_name', 100)->nullable();
            $table->string('witness1_name', 100)->nullable();
            $table->string('witness2_name', 100)->nullable();
            $table->string('religion', 50)->nullable();
            $table->string('license_number', 50)->nullable();

            // ======================================
            // CHURCH MARRIAGE PROCESS (BANAS & DISPENSATION)
            // ======================================

            // Banas (Marriage Announcement) Information
            $table->string('banas_number', 50)->nullable();
            $table->string('banas_church_1', 100)->nullable();
            $table->date('banas_date_1')->nullable();
            $table->string('banas_church_2', 100)->nullable();
            $table->date('banas_date_2')->nullable();

            // Dispensation Information
            $table->string('dispensation_from', 100)->nullable();
            $table->string('dispensation_given_by', 100)->nullable();
            $table->string('dispensation_impediment', 200)->nullable();
            $table->string('dispensation_authority', 100)->nullable();
            $table->date('dispensation_date')->nullable();

            // Marriage Officiation Details
            $table->string('presence_of', 100)->nullable();
            $table->string('delegated_by', 100)->nullable();
            $table->date('delegation_date')->nullable();

            // ======================================
            // EXTENDED WITNESS INFORMATION
            // ======================================
            $table->string('male_witness_full_name', 100)->nullable();
            $table->string('male_witness_father', 100)->nullable();
            $table->string('male_witness_clan', 50)->nullable();
            $table->string('female_witness_full_name', 100)->nullable();
            $table->string('female_witness_father', 100)->nullable();
            $table->string('female_witness_clan', 50)->nullable();

            // ======================================
            // ADDITIONAL INFORMATION
            // ======================================
            $table->text('other_documents')->nullable();
            $table->string('civil_marriage_certificate_number', 50)->nullable();
            $table->text('notes')->nullable();

            // ======================================
            // SYSTEM TIMESTAMPS
            // ======================================
            $table->timestamps();

            // ======================================
            // PERFORMANCE INDEXES
            // ======================================

            // Name-based searches
            $table->index(['first_name', 'last_name'], 'idx_member_full_name');
            $table->index(['last_name', 'first_name'], 'idx_member_name_reverse');

            // Church-based queries
            $table->index(['local_church', 'membership_status'], 'idx_church_status');
            $table->index(['local_church', 'church_group'], 'idx_church_group');
            $table->index(['small_christian_community', 'membership_status'], 'idx_community_status');

            // Status and demographic queries
            $table->index(['membership_status', 'gender'], 'idx_status_gender');
            $table->index(['matrimony_status', 'gender'], 'idx_matrimony_gender');
            $table->index(['marital_status', 'gender'], 'idx_marital_gender');
            $table->index(['date_of_birth', 'gender'], 'idx_age_gender');

            // Family and relationship queries
            $table->index(['family_id', 'membership_status'], 'idx_family_status');
            $table->index(['parent_id', 'family_id'], 'idx_parent_family');

            // Contact information searches
            $table->index(['phone'], 'idx_phone_search');
            $table->index(['email'], 'idx_email_search');

            // Sacrament-based queries
            $table->index(['baptism_date', 'local_church'], 'idx_baptism_church');
            $table->index(['confirmation_date', 'local_church'], 'idx_confirmation_church');
            $table->index(['marriage_date', 'local_church'], 'idx_marriage_church');
            $table->index(['eucharist_date', 'local_church'], 'idx_eucharist_church');

            // Geographic and demographic queries
            $table->index(['county', 'membership_status'], 'idx_location_status');
            $table->index(['tribe', 'local_church'], 'idx_tribe_church');
            $table->index(['occupation', 'education_level'], 'idx_occupation_education');

            // Marriage-specific queries
            $table->index(['spouse_name'], 'idx_spouse_search');
            $table->index(['marriage_date', 'marriage_location'], 'idx_marriage_details');

            // Certificate tracking
            $table->index(['marriage_certificate_number'], 'idx_marriage_cert_num');
            $table->index(['confirmation_register_number'], 'idx_confirmation_reg_num');

            // Compound indexes for complex queries
            $table->index(['membership_status', 'local_church', 'church_group'], 'idx_membership_compound');
            $table->index(['gender', 'matrimony_status', 'membership_status'], 'idx_demographics_compound');
            $table->index(['gender', 'marital_status', 'membership_status'], 'idx_marital_demographics_compound');
            $table->index(['created_at', 'membership_status'], 'idx_registration_timeline');
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
