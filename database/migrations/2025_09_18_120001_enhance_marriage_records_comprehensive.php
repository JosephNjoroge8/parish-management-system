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
        // Drop existing marriage_records table to rebuild with comprehensive structure
        Schema::dropIfExists('marriage_records');
        
        Schema::create('marriage_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_number')->unique()->index();
            
            // HUSBAND INFORMATION - All fields as specified
            $table->string('husband_name');
            $table->string('husband_father_name'); // son of (father's name)
            $table->string('husband_mother_name'); // mother's name
            $table->string('husband_tribe');
            $table->string('husband_clan');
            $table->string('husband_birth_place'); // born in
            $table->string('husband_domicile'); // domicile
            $table->string('husband_baptized_at'); // baptized at
            $table->date('husband_baptism_date'); // date
            $table->string('husband_widower_of')->nullable(); // widower of
            $table->enum('husband_parent_consent', ['Yes', 'No'])->default('No'); // parent consent obtained: Yes/NO
            
            // WIFE INFORMATION - All fields as specified
            $table->string('wife_name');
            $table->string('wife_father_name'); // daughter of (father's name)
            $table->string('wife_mother_name'); // mother's name
            $table->string('wife_tribe');
            $table->string('wife_clan');
            $table->string('wife_birth_place'); // born in
            $table->string('wife_domicile'); // domicile
            $table->string('wife_baptized_at'); // baptized at
            $table->date('wife_baptism_date'); // date
            $table->string('wife_widow_of')->nullable(); // widow of
            $table->enum('wife_parent_consent', ['Yes', 'No'])->default('No'); // parent consent obtained: Yes/NO
            
            // BANAS INFORMATION - As specified
            $table->string('banas_number'); // Banas No
            $table->string('banas_church_1'); // in the church of
            $table->date('banas_date_1'); // dates
            $table->string('banas_church_2')->nullable(); // and in the church of
            $table->date('banas_date_2')->nullable(); // dates
            $table->string('dispensation_from')->nullable(); // or with dispensation from
            $table->string('dispensation_given_by')->nullable(); // given by
            
            // DISPENSATION INFORMATION - As specified
            $table->text('dispensation_impediment')->nullable(); // Dispensation from the impediment(s) of
            $table->string('dispensation_authority')->nullable(); // given by
            $table->date('dispensation_date')->nullable(); // date
            
            // MARRIAGE CONTRACT INFORMATION - As specified
            $table->date('marriage_date'); // Contracted marriage according to the right of Catholic church today (date)
            $table->string('marriage_month'); // month
            $table->string('marriage_year'); // year
            $table->string('marriage_church'); // in the church of
            $table->string('district'); // District of
            $table->string('province'); // province of
            $table->string('presence_of'); // in the presence of
            $table->string('delegated_by')->nullable(); // delegated by
            $table->date('delegation_date')->nullable(); // on (date)
            
            // SIGNATURES - As specified
            $table->text('husband_signature')->nullable(); // Signature: Husband
            $table->text('wife_signature')->nullable(); // Signature: wife
            
            // WITNESS INFORMATION - As specified
            $table->string('male_witness_full_name'); // WITNESS (full names) name
            $table->string('male_witness_father'); // son of
            $table->string('male_witness_clan'); // clan
            $table->string('female_witness_full_name'); // name
            $table->string('female_witness_father'); // daughter of  
            $table->string('female_witness_clan'); // clan
            $table->text('male_witness_signature')->nullable(); // Signature: Male
            $table->text('female_witness_signature')->nullable(); // Signature: female
            
            // ADDITIONAL DOCUMENTS AND SIGNATURES - As specified
            $table->text('other_documents')->nullable(); // any other document
            $table->text('parish_priest_signature')->nullable(); // parish priest signature
            $table->string('civil_marriage_certificate_number')->nullable(); // Civil marriage certificate number
            $table->text('parish_stamp')->nullable(); // parish stamp
            
            // SYSTEM RELATIONSHIPS
            $table->foreignId('husband_id')->nullable()->constrained('members')->onDelete('set null');
            $table->foreignId('wife_id')->nullable()->constrained('members')->onDelete('set null');
            $table->foreignId('parish_priest_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('sacrament_id')->nullable()->constrained('sacraments')->onDelete('set null');
            
            $table->timestamps();
            
            // Add comprehensive indexes for performance
            $table->index('record_number');
            $table->index(['husband_name', 'wife_name']);
            $table->index('marriage_date');
            $table->index('marriage_church');
            $table->index('district');
            $table->index('province');
            $table->index('husband_id');
            $table->index('wife_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marriage_records');
    }
};
