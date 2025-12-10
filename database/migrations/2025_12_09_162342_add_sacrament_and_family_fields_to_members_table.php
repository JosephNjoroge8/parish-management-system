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
            // Family Information
            $table->string('father_name')->nullable()->after('parent');
            $table->string('mother_name')->nullable()->after('father_name');
            $table->string('father_occupation')->nullable()->after('mother_name');
            $table->string('mother_occupation')->nullable()->after('father_occupation');
            $table->string('father_residence')->nullable()->after('mother_occupation');
            $table->string('mother_residence')->nullable()->after('father_residence');
            $table->string('birth_village')->nullable()->after('mother_residence');
            $table->string('county')->nullable()->after('birth_village');

            // Baptism Information
            $table->string('baptism_location')->nullable()->after('baptism_date');
            $table->string('baptized_by')->nullable()->after('baptism_location');
            $table->string('sponsor')->nullable()->after('baptized_by');

            // Confirmation Information
            $table->string('confirmation_location')->nullable()->after('confirmation_date');
            $table->string('confirmation_number')->nullable()->after('confirmation_location');
            $table->string('confirmation_register_number')->nullable()->after('confirmation_number');

            // Eucharist Information
            $table->date('eucharist_date')->nullable()->after('confirmation_register_number');
            $table->string('eucharist_location')->nullable()->after('eucharist_date');

            // Marriage Information
            $table->date('marriage_date')->nullable()->after('marriage_type');
            $table->string('marriage_location')->nullable()->after('marriage_date');
            $table->string('marriage_church')->nullable()->after('marriage_location');
            $table->string('marriage_county')->nullable()->after('marriage_church');
            $table->string('marriage_sub_county')->nullable()->after('marriage_county');
            $table->string('marriage_religion')->nullable()->after('marriage_sub_county');
            $table->string('marriage_officiant_name')->nullable()->after('marriage_religion');

            // Spouse Information
            $table->string('spouse_name')->nullable()->after('marriage_officiant_name');
            $table->integer('spouse_age')->nullable()->after('spouse_name');
            $table->string('spouse_residence')->nullable()->after('spouse_age');
            $table->string('spouse_county')->nullable()->after('spouse_residence');
            $table->string('spouse_marital_status')->nullable()->after('spouse_county');
            $table->string('spouse_occupation')->nullable()->after('spouse_marital_status');
            $table->string('spouse_father_name')->nullable()->after('spouse_occupation');
            $table->string('spouse_mother_name')->nullable()->after('spouse_father_name');

            // Disability Information
            $table->boolean('is_differently_abled')->default(false)->after('notes');
            $table->text('disability_description')->nullable()->after('is_differently_abled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'father_name',
                'mother_name',
                'father_occupation',
                'mother_occupation',
                'father_residence',
                'mother_residence',
                'birth_village',
                'county',
                'baptism_location',
                'baptized_by',
                'sponsor',
                'confirmation_location',
                'confirmation_number',
                'confirmation_register_number',
                'eucharist_date',
                'eucharist_location',
                'marriage_date',
                'marriage_location',
                'marriage_church',
                'marriage_county',
                'marriage_sub_county',
                'marriage_religion',
                'marriage_officiant_name',
                'spouse_name',
                'spouse_age',
                'spouse_residence',
                'spouse_county',
                'spouse_marital_status',
                'spouse_occupation',
                'spouse_father_name',
                'spouse_mother_name',
                'is_differently_abled',
                'disability_description',
            ]);
        });
    }
};
