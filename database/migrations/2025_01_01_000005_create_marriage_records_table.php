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
        Schema::create('marriage_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_number')->unique();
            
            // HUSBAND INFORMATION (Comprehensive as per marriage record specification)
            $table->string('husband_name');
            $table->string('husband_father_name');
            $table->string('husband_mother_name');
            $table->string('husband_tribe');
            $table->string('husband_clan');
            $table->string('husband_birth_place');
            $table->string('husband_domicile');
            $table->string('husband_baptized_at');
            $table->date('husband_baptism_date');
            $table->string('husband_widower_of')->nullable();
            $table->enum('husband_parent_consent', ['Yes', 'No'])->default('No');
            
            // WIFE INFORMATION (Comprehensive as per marriage record specification)
            $table->string('wife_name');
            $table->string('wife_father_name');
            $table->string('wife_mother_name');
            $table->string('wife_tribe');
            $table->string('wife_clan');
            $table->string('wife_birth_place');
            $table->string('wife_domicile');
            $table->string('wife_baptized_at');
            $table->date('wife_baptism_date');
            $table->string('wife_widow_of')->nullable();
            $table->enum('wife_parent_consent', ['Yes', 'No'])->default('No');
            
            // BANNS INFORMATION
            $table->string('banns_number')->nullable();
            $table->string('banns_church_1')->nullable();
            $table->date('banns_date_1')->nullable();
            $table->string('banns_church_2')->nullable();
            $table->date('banns_date_2')->nullable();
            $table->string('dispensation_from')->nullable();
            $table->string('dispensation_given_by')->nullable();
            
            // DISPENSATION INFORMATION
            $table->string('dispensation_impediment')->nullable();
            $table->string('dispensation_authority')->nullable();
            $table->date('dispensation_date')->nullable();
            
            // MARRIAGE CONTRACT INFORMATION
            $table->date('marriage_date');
            $table->string('marriage_month');
            $table->string('marriage_year');
            $table->string('marriage_church');
            $table->string('district');
            $table->string('province');
            $table->string('presence_of'); // Who officiated
            $table->string('delegated_by')->nullable();
            $table->date('delegation_date')->nullable();
            
            // SIGNATURES
            $table->string('husband_signature')->nullable();
            $table->string('wife_signature')->nullable();
            
            // WITNESS INFORMATION (Comprehensive)
            $table->string('male_witness_full_name');
            $table->string('male_witness_father');
            $table->string('male_witness_clan');
            $table->string('female_witness_full_name');
            $table->string('female_witness_father');
            $table->string('female_witness_clan');
            $table->string('male_witness_signature')->nullable();
            $table->string('female_witness_signature')->nullable();
            
            // ADDITIONAL DOCUMENTS AND SIGNATURES
            $table->text('other_documents')->nullable();
            $table->string('parish_priest_signature')->nullable();
            $table->string('civil_marriage_certificate_number')->nullable();
            $table->string('parish_stamp')->nullable();
            
            // SYSTEM RELATIONSHIPS
            $table->unsignedBigInteger('parish_priest_id')->nullable();
            $table->unsignedBigInteger('husband_id')->nullable();
            $table->unsignedBigInteger('wife_id')->nullable();
            $table->unsignedBigInteger('sacrament_id')->nullable();
            
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('husband_id')->references('id')->on('members')->onDelete('set null');
            $table->foreign('wife_id')->references('id')->on('members')->onDelete('set null');
            $table->foreign('parish_priest_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('sacrament_id')->references('id')->on('sacraments')->onDelete('cascade');

            // Indexes for performance
            $table->index('record_number');
            $table->index(['husband_name', 'wife_name']);
            $table->index('marriage_date');
            $table->index('marriage_church');
            $table->index(['husband_id', 'wife_id']);
            $table->index('sacrament_id');
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