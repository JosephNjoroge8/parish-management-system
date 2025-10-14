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
        Schema::table('members', function (Blueprint $table) {
            // Check if the migration was partially run and add only missing fields
            if (!Schema::hasColumn('members', 'mother_name')) {
                // Parent/Family Information
                $table->string('mother_name')->nullable()->after('parent');
                $table->string('father_name')->nullable()->after('mother_name');
                $table->string('father_occupation')->nullable()->after('father_name');
                $table->string('father_residence')->nullable()->after('father_occupation');
                $table->string('mother_occupation')->nullable()->after('father_residence');
                $table->string('mother_residence')->nullable()->after('mother_occupation');
                
                // Sacrament Information - Baptism
                $table->string('baptized_by')->nullable()->after('baptism_date');
                $table->string('baptism_location')->nullable()->after('baptized_by');
                $table->string('sponsor')->nullable()->after('baptism_location');
                
                // Sacrament Information - Confirmation
                $table->string('confirmation_location')->nullable()->after('confirmation_date');
                $table->string('confirmation_register_number')->nullable()->after('confirmation_location');
                $table->string('confirmation_number')->nullable()->after('confirmation_register_number');
                
                // Sacrament Information - Eucharist
                $table->date('eucharist_date')->nullable()->after('confirmation_number');
                $table->string('eucharist_location')->nullable()->after('eucharist_date');
                
                // Marriage Certificate fields (essential for certificate generation)
                $table->date('marriage_date')->nullable()->after('marriage_type');
                $table->string('marriage_location')->nullable()->after('marriage_date');
                $table->string('marriage_county')->nullable()->after('marriage_location');
                $table->string('marriage_sub_county')->nullable()->after('marriage_county');
                $table->string('marriage_entry_number')->nullable()->after('marriage_sub_county');
                $table->string('marriage_certificate_number')->nullable()->after('marriage_entry_number');
                $table->string('marriage_religion')->nullable()->after('marriage_certificate_number');
                $table->string('marriage_license_number')->nullable()->after('marriage_religion');
                $table->string('marriage_officiant_name')->nullable()->after('marriage_license_number');
                $table->string('marriage_witness1_name')->nullable()->after('marriage_officiant_name');
                $table->string('marriage_witness2_name')->nullable()->after('marriage_witness1_name');
                $table->string('member_marriage_residence')->nullable()->after('marriage_witness2_name');
                
                // Spouse Information (for marriage certificate)
                $table->string('spouse_name')->nullable()->after('member_marriage_residence');
                $table->string('spouse_age')->nullable()->after('spouse_name');
                $table->string('spouse_residence')->nullable()->after('spouse_age');
                $table->string('spouse_county')->nullable()->after('spouse_residence');
                $table->string('spouse_marital_status')->nullable()->after('spouse_county');
                $table->string('spouse_occupation')->nullable()->after('spouse_marital_status');
                $table->string('spouse_father_name')->nullable()->after('spouse_occupation');
                $table->string('spouse_father_occupation')->nullable()->after('spouse_father_name');
                $table->string('spouse_father_residence')->nullable()->after('spouse_father_occupation');
                $table->string('spouse_mother_name')->nullable()->after('spouse_father_residence');
                $table->string('spouse_mother_occupation')->nullable()->after('spouse_mother_name');
                $table->string('spouse_mother_residence')->nullable()->after('spouse_mother_occupation');
                
                // Bride information (for marriage certificates)
                $table->string('bride_name')->nullable()->after('spouse_mother_residence');
                $table->string('bride_age')->nullable()->after('bride_name');
                $table->string('bride_residence')->nullable()->after('bride_age');
                $table->string('bride_county')->nullable()->after('bride_residence');
                $table->string('bride_marital_status')->nullable()->after('bride_county');
                $table->string('bride_occupation')->nullable()->after('bride_marital_status');
                $table->string('bride_father_name')->nullable()->after('bride_occupation');
                $table->string('bride_father_occupation')->nullable()->after('bride_father_name');
                $table->string('bride_father_residence')->nullable()->after('bride_father_occupation');
                $table->string('bride_mother_name')->nullable()->after('bride_father_residence');
                $table->string('bride_mother_occupation')->nullable()->after('bride_mother_name');
                $table->string('bride_mother_residence')->nullable()->after('bride_mother_occupation');
                
                // Bridegroom information (for marriage certificates)
                $table->string('bridegroom_name')->nullable()->after('bride_mother_residence');
                $table->string('bridegroom_age')->nullable()->after('bridegroom_name');
                $table->string('bridegroom_residence')->nullable()->after('bridegroom_age');
                $table->string('bridegroom_county')->nullable()->after('bridegroom_residence');
                $table->string('bridegroom_marital_status')->nullable()->after('bridegroom_county');
                $table->string('bridegroom_occupation')->nullable()->after('bridegroom_marital_status');
                $table->string('bridegroom_father_name')->nullable()->after('bridegroom_occupation');
                $table->string('bridegroom_father_occupation')->nullable()->after('bridegroom_father_name');
                $table->string('bridegroom_father_residence')->nullable()->after('bridegroom_father_occupation');
                $table->string('bridegroom_mother_name')->nullable()->after('bridegroom_father_residence');
                $table->string('bridegroom_mother_occupation')->nullable()->after('bridegroom_mother_name');
                $table->string('bridegroom_mother_residence')->nullable()->after('bridegroom_mother_occupation');
                
                // Additional location fields
                $table->string('birth_village')->nullable()->after('bridegroom_mother_residence');
                $table->string('county')->nullable()->after('birth_village');
            }
            
            // Add fields that weren't added in the partial run (after emergency_contact that already exists)
            if (!Schema::hasColumn('members', 'parent_id')) {
                // Other relationship/relational fields
                $table->unsignedBigInteger('parent_id')->nullable()->after('family_id');
                $table->unsignedBigInteger('godparent_id')->nullable()->after('parent_id');
                $table->unsignedBigInteger('minister_id')->nullable()->after('godparent_id');
                
                // For marriage certificate template compatibility
                $table->string('husband_name')->nullable()->after('county');
                $table->string('husband_age')->nullable()->after('husband_name');
                $table->string('husband_residence')->nullable()->after('husband_age');
                $table->string('husband_county')->nullable()->after('husband_residence');
                $table->string('husband_marital_status')->nullable()->after('husband_county');
                $table->string('husband_occupation')->nullable()->after('husband_marital_status');
                $table->string('husband_father_name')->nullable()->after('husband_occupation');
                $table->string('husband_father_occupation')->nullable()->after('husband_father_name');
                $table->string('husband_father_residence')->nullable()->after('husband_father_occupation');
                $table->string('husband_mother_name')->nullable()->after('husband_father_residence');
                $table->string('husband_mother_occupation')->nullable()->after('husband_mother_name');
                $table->string('husband_mother_residence')->nullable()->after('husband_mother_occupation');
                
                $table->string('wife_name')->nullable()->after('husband_mother_residence');
                $table->string('wife_age')->nullable()->after('wife_name');
                $table->string('wife_residence')->nullable()->after('wife_age');
                $table->string('wife_county')->nullable()->after('wife_residence');
                $table->string('wife_marital_status')->nullable()->after('wife_county');
                $table->string('wife_occupation')->nullable()->after('wife_marital_status');
                $table->string('wife_father_name')->nullable()->after('wife_occupation');
                $table->string('wife_father_occupation')->nullable()->after('wife_father_name');
                $table->string('wife_father_residence')->nullable()->after('wife_father_occupation');
                $table->string('wife_mother_name')->nullable()->after('wife_father_residence');
                $table->string('wife_mother_occupation')->nullable()->after('wife_mother_name');
                $table->string('wife_mother_residence')->nullable()->after('wife_mother_occupation');
                
                // Additional marriage certificate template fields
                $table->string('sub_county')->nullable()->after('wife_mother_residence');
                $table->string('entry_number')->nullable()->after('sub_county');
                $table->string('certificate_number')->nullable()->after('entry_number');
                $table->string('officiant_name')->nullable()->after('certificate_number');
                $table->string('witness1_name')->nullable()->after('officiant_name');
                $table->string('witness2_name')->nullable()->after('witness1_name');
                $table->string('religion')->nullable()->after('witness2_name');
                $table->string('license_number')->nullable()->after('religion');
                
                // Marriage spouse field for baptism card compatibility
                $table->string('marriage_spouse')->nullable()->after('license_number');
                $table->string('marriage_register_number')->nullable()->after('marriage_spouse');
                $table->string('marriage_number')->nullable()->after('marriage_register_number');
                
                // Additional disability fields if not exist
                if (!Schema::hasColumn('members', 'is_differently_abled')) {
                    $table->boolean('is_differently_abled')->default(false)->after('county');
                    $table->text('disability_description')->nullable()->after('is_differently_abled');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Remove all the added fields in reverse order
            $table->dropColumn([
                'marriage_number',
                'marriage_register_number', 
                'marriage_spouse',
                'license_number',
                'religion',
                'witness2_name',
                'witness1_name',
                'officiant_name',
                'certificate_number',
                'entry_number',
                'sub_county',
                'wife_mother_residence',
                'wife_mother_occupation',
                'wife_mother_name',
                'wife_father_residence',
                'wife_father_occupation',
                'wife_father_name',
                'wife_occupation',
                'wife_marital_status',
                'wife_county',
                'wife_residence',
                'wife_age',
                'wife_name',
                'husband_mother_residence',
                'husband_mother_occupation',
                'husband_mother_name',
                'husband_father_residence',
                'husband_father_occupation',
                'husband_father_name',
                'husband_occupation',
                'husband_marital_status',
                'husband_county',
                'husband_residence',
                'husband_age',
                'husband_name',
                'emergency_phone',
                'emergency_contact',
                'county',
                'birth_village',
                'bridegroom_mother_residence',
                'bridegroom_mother_occupation',
                'bridegroom_mother_name',
                'bridegroom_father_residence',
                'bridegroom_father_occupation',
                'bridegroom_father_name',
                'bridegroom_occupation',
                'bridegroom_marital_status',
                'bridegroom_county',
                'bridegroom_residence',
                'bridegroom_age',
                'bridegroom_name',
                'bride_mother_residence',
                'bride_mother_occupation',
                'bride_mother_name',
                'bride_father_residence',
                'bride_father_occupation',
                'bride_father_name',
                'bride_occupation',
                'bride_marital_status',
                'bride_county',
                'bride_residence',
                'bride_age',
                'bride_name',
                'spouse_mother_residence',
                'spouse_mother_occupation',
                'spouse_mother_name',
                'spouse_father_residence',
                'spouse_father_occupation',
                'spouse_father_name',
                'spouse_occupation',
                'spouse_marital_status',
                'spouse_county',
                'spouse_residence',
                'spouse_age',
                'spouse_name',
                'member_marriage_residence',
                'marriage_witness2_name',
                'marriage_witness1_name',
                'marriage_officiant_name',
                'marriage_license_number',
                'marriage_religion',
                'marriage_certificate_number',
                'marriage_entry_number',
                'marriage_sub_county',
                'marriage_county',
                'marriage_location',
                'marriage_date',
                'eucharist_location',
                'eucharist_date',
                'confirmation_number',
                'confirmation_register_number',
                'confirmation_location',
                'sponsor',
                'baptism_location',
                'baptized_by',
                'mother_residence',
                'mother_occupation',
                'father_residence',
                'father_occupation',
                'father_name',
                'mother_name',
                'minister_id',
                'godparent_id',
                'parent_id',
            ]);
        });
    }
};
