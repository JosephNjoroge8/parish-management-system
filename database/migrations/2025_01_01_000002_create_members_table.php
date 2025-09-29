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
            $table->string('first_name', 100)->index(); // Indexed for search performance
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100)->index(); // Indexed for search performance
            $table->date('date_of_birth')->nullable()->index(); // Indexed for age queries
            $table->enum('gender', ['Male', 'Female'])->nullable()->index(); // Indexed for statistics
            $table->string('id_number', 20)->unique()->nullable(); // Unique constraint for data integrity
            
            // ======================================
            // CONTACT INFORMATION
            // ======================================
            $table->string('phone', 20)->nullable()->index(); // Indexed for contact searches
            $table->string('email', 100)->nullable()->index(); // Indexed for contact searches
            $table->text('residence')->nullable();
            
            // ======================================
            // CHURCH INFORMATION (OPTIMIZED)
            // ======================================
            $table->string('local_church', 100)->nullable()->index(); // Indexed for church-based queries
            $table->string('small_christian_community', 100)->nullable()->index(); // Indexed for community queries
            $table->enum('church_group', [
                'PMC', 'Youth', 'Young Parents', 'C.W.A', 'CMA', 'Choir', 'Catholic Action', 'Pioneer'
            ])->nullable()->index(); // Indexed for group statistics
            $table->json('additional_church_groups')->nullable(); // For multiple group memberships
            
            // ======================================
            // MEMBERSHIP INFORMATION (OPTIMIZED)
            // ======================================
            $table->enum('membership_status', ['active', 'inactive', 'transferred', 'deceased'])
                  ->default('active')->index(); // Indexed for status filtering
            $table->date('membership_date')->nullable()->index(); // Indexed for membership analytics
            $table->enum('matrimony_status', ['single', 'married', 'widowed', 'separated', 'divorced'])
                  ->default('single')->index(); // Indexed for marital status reports
            $table->enum('marriage_type', ['customary', 'church', 'civil', 'both'])->nullable();
            
            // ======================================
            // ACCESSIBILITY AND INCLUSION
            // ======================================
            $table->boolean('is_differently_abled')->default(false)->index(); // Indexed for accessibility reports
            $table->text('disability_description')->nullable();
            
            // ======================================
            // EDUCATION AND OCCUPATION (OPTIMIZED)
            // ======================================
            $table->string('occupation', 100)->nullable()->index(); // Indexed for occupation statistics
            $table->enum('education_level', [
                'none', 'primary', 'kcpe', 'secondary', 'kcse', 'certificate', 
                'diploma', 'degree', 'masters', 'phd', 'other'
            ])->nullable()->index(); // Indexed for education statistics
            
            // ======================================
            // FAMILY RELATIONSHIPS (PERFORMANCE OPTIMIZED)
            // ======================================
            $table->unsignedBigInteger('family_id')->nullable()->index(); // Indexed for family queries
            $table->unsignedBigInteger('parent_id')->nullable()->index(); // Indexed for hierarchy queries
            $table->unsignedBigInteger('godparent_id')->nullable()->index(); // Indexed for sacrament queries
            $table->unsignedBigInteger('minister_id')->nullable()->index(); // Indexed for ministry queries
            
            // ======================================
            // CULTURAL AND TRIBAL INFORMATION
            // ======================================
            $table->string('tribe', 50)->nullable()->index(); // Indexed for demographic reports
            $table->string('clan', 50)->nullable();
            
            // ======================================
            // FAMILY INFORMATION (STRING FIELDS FOR DATA ENTRY)
            // ======================================
            $table->string('parent', 100)->nullable(); // Father's name (primary data entry field)
            $table->string('mother_name', 100)->nullable(); // Mother's name (primary data entry field)
            $table->string('godparent', 100)->nullable(); // Godparent name (primary data entry field)
            $table->string('minister', 100)->nullable(); // Minister name (primary data entry field)
            
            // ======================================
            // GEOGRAPHICAL INFORMATION (OPTIMIZED)
            // ======================================
            $table->string('birth_village', 100)->nullable();
            $table->string('county', 50)->nullable()->index(); // Indexed for location reports
            $table->string('district', 50)->nullable();
            $table->string('province', 50)->nullable();
            
            // ======================================
            // SACRAMENT INFORMATION (AUTO-SYNCED FIELDS)
            // ======================================
            
            // Baptism Information
            $table->date('baptism_date')->nullable()->index(); // Indexed for sacrament reports
            $table->string('baptism_location', 100)->nullable();
            $table->string('baptized_by', 100)->nullable(); // Auto-synced from 'minister'
            $table->string('sponsor', 100)->nullable(); // Auto-synced from 'godparent'
            $table->string('father_name', 100)->nullable(); // Auto-synced from 'parent'
            
            // Confirmation Information
            $table->date('confirmation_date')->nullable()->index(); // Indexed for sacrament reports
            $table->string('confirmation_location', 100)->nullable();
            $table->string('confirmation_register_number', 50)->nullable()->unique(); // Unique for certificate tracking
            $table->string('confirmation_number', 50)->nullable()->unique(); // Unique for certificate tracking
            
            // First Communion Information
            $table->date('eucharist_date')->nullable()->index(); // Indexed for sacrament reports
            $table->string('eucharist_location', 100)->nullable();
            
            // Extended Godparent Information
            $table->string('godfather_name', 100)->nullable();
            $table->string('godmother_name', 100)->nullable();
            
            // ======================================
            // MARRIAGE INFORMATION (COMPREHENSIVE)
            // ======================================
            
            // Core Marriage Details
            $table->date('marriage_date')->nullable()->index(); // Indexed for marriage reports
            $table->string('marriage_location', 100)->nullable();
            $table->string('marriage_county', 50)->nullable();
            $table->string('marriage_sub_county', 50)->nullable();
            $table->string('marriage_entry_number', 50)->nullable()->unique(); // Unique for certificate tracking
            $table->string('marriage_certificate_number', 50)->nullable()->unique(); // Unique for certificate tracking
            $table->string('marriage_religion', 50)->nullable();
            $table->string('marriage_license_number', 50)->nullable();
            $table->string('marriage_officiant_name', 100)->nullable();
            $table->string('marriage_witness1_name', 100)->nullable();
            $table->string('marriage_witness2_name', 100)->nullable();
            
            // ======================================
            // SPOUSE INFORMATION (DETAILED)
            // ======================================
            $table->string('spouse_name', 100)->nullable()->index(); // Indexed for spouse searches
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
            // MARRIAGE CERTIFICATE TEMPLATE FIELDS (AUTO-POPULATED)
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
            $table->string('marriage_spouse', 100)->nullable(); // Legacy compatibility
            $table->string('marriage_register_number', 50)->nullable(); // Legacy compatibility
            $table->string('marriage_number', 50)->nullable(); // Legacy compatibility
            $table->string('married_by', 100)->nullable(); // Legacy compatibility
            $table->string('witness_1_name', 100)->nullable(); // Legacy compatibility
            $table->string('witness_2_name', 100)->nullable(); // Legacy compatibility
            $table->string('marriage_church', 100)->nullable(); // Legacy compatibility
            
            // ======================================
            // MARRIAGE CERTIFICATE TEMPLATE MAPPINGS (AUTO-SYNCED)
            // ======================================
            $table->string('sub_county', 50)->nullable(); // Auto-synced from marriage_sub_county
            $table->string('entry_number', 50)->nullable(); // Auto-synced from marriage_entry_number
            $table->string('certificate_number', 50)->nullable(); // Auto-synced from marriage_certificate_number
            $table->string('officiant_name', 100)->nullable(); // Auto-synced from marriage_officiant_name
            $table->string('witness1_name', 100)->nullable(); // Auto-synced from marriage_witness1_name
            $table->string('witness2_name', 100)->nullable(); // Auto-synced from marriage_witness2_name
            $table->string('religion', 50)->nullable(); // Auto-synced from marriage_religion
            $table->string('license_number', 50)->nullable(); // Auto-synced from marriage_license_number
            
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
            // PERFORMANCE INDEXES (OPTIMIZED FOR QUERIES)
            // ======================================
            
            // Name-based searches (most common queries)
            $table->index(['first_name', 'last_name'], 'idx_member_full_name');
            $table->index(['last_name', 'first_name'], 'idx_member_name_reverse');
            
            // Church-based queries (very common)
            $table->index(['local_church', 'membership_status'], 'idx_church_status');
            $table->index(['local_church', 'church_group'], 'idx_church_group');
            $table->index(['small_christian_community', 'membership_status'], 'idx_community_status');
            
            // Status and demographic queries
            $table->index(['membership_status', 'gender'], 'idx_status_gender');
            $table->index(['matrimony_status', 'gender'], 'idx_matrimony_gender');
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
            
            // Certificate tracking (unique constraints for data integrity)
            $table->index(['marriage_certificate_number'], 'idx_marriage_cert_num');
            $table->index(['confirmation_register_number'], 'idx_confirmation_reg_num');
            
            // Compound indexes for complex queries
            $table->index(['membership_status', 'local_church', 'church_group'], 'idx_membership_compound');
            $table->index(['gender', 'matrimony_status', 'membership_status'], 'idx_demographics_compound');
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