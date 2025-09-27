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
            
            // Husband information
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
            $table->boolean('husband_parent_consent')->default(false);
            
            // Wife information
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
            $table->boolean('wife_parent_consent')->default(false);
            
            // Banas information
            $table->string('banas_number');
            $table->string('banas_church_1');
            $table->date('banas_date_1');
            $table->string('banas_church_2')->nullable();
            $table->date('banas_date_2')->nullable();
            
            // Dispensation information
            $table->string('dispensation_from')->nullable();
            $table->string('dispensation_given_by')->nullable();
            $table->string('dispensation_impediment')->nullable();
            $table->date('dispensation_date')->nullable();
            
            // Marriage information
            $table->date('marriage_date');
            $table->string('marriage_church');
            $table->string('district');
            $table->string('province');
            $table->string('presence_of');
            $table->string('delegated_by')->nullable();
            $table->date('delegation_date')->nullable();
            
            // Witness information
            $table->string('male_witness_name');
            $table->string('male_witness_father');
            $table->string('male_witness_clan');
            $table->string('female_witness_name');
            $table->string('female_witness_father');
            $table->string('female_witness_clan');
            
            // Additional information
            $table->string('civil_marriage_certificate_number')->nullable();
            $table->string('other_documents')->nullable();
            $table->foreignId('parish_priest_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Links to members and sacrament record
            $table->foreignId('husband_id')->nullable()->constrained('members')->onDelete('set null');
            $table->foreignId('wife_id')->nullable()->constrained('members')->onDelete('set null');
            $table->foreignId('sacrament_id')->nullable()->constrained('sacraments')->onDelete('set null');
            
            $table->timestamps();
            
            // Add indexes
            $table->index('record_number');
            $table->index(['husband_name', 'wife_name']);
            $table->index('marriage_date');
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
