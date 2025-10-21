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
        if (! Schema::hasTable('marriage_records')) {
            Schema::create('marriage_records', function (Blueprint $table) {
                $table->id();
                $table->string('record_number')->unique()->nullable();

                // HUSBAND INFORMATION - Comprehensive as per Catholic Church requirements
                $table->string('husband_name');
                $table->string('husband_father_name')->nullable();
                $table->string('husband_mother_name')->nullable();
                $table->string('husband_tribe')->nullable();
                $table->string('husband_clan')->nullable();
                $table->string('husband_birth_place')->nullable();
                $table->string('husband_domicile')->nullable(); // Current residence
                $table->string('husband_baptized_at')->nullable(); // Church where baptized
                $table->date('husband_baptism_date')->nullable();
                $table->string('husband_widower_of')->nullable(); // If previously married
                $table->boolean('husband_parent_consent')->default(false);
                $table->unsignedBigInteger('husband_member_id')->nullable(); // Link to members table

                // WIFE INFORMATION - Comprehensive as per Catholic Church requirements
                $table->string('wife_name');
                $table->string('wife_father_name')->nullable();
                $table->string('wife_mother_name')->nullable();
                $table->string('wife_tribe')->nullable();
                $table->string('wife_clan')->nullable();
                $table->string('wife_birth_place')->nullable();
                $table->string('wife_domicile')->nullable(); // Current residence
                $table->string('wife_baptized_at')->nullable(); // Church where baptized
                $table->date('wife_baptism_date')->nullable();
                $table->string('wife_widow_of')->nullable(); // If previously married
                $table->boolean('wife_parent_consent')->default(false);
                $table->unsignedBigInteger('wife_member_id')->nullable(); // Link to members table

                // MARRIAGE CEREMONY INFORMATION
                $table->date('marriage_date');
                $table->string('marriage_location'); // Church where ceremony was held
                $table->string('celebrant'); // Priest who performed ceremony
                $table->string('witness_1')->nullable();
                $table->string('witness_2')->nullable();
                $table->string('witness_1_signature')->nullable(); // Could store signature image path
                $table->string('witness_2_signature')->nullable();

                // LEGAL AND CHURCH REQUIREMENTS
                $table->enum('marriage_type', ['church', 'customary', 'civil'])->default('church');
                $table->boolean('banns_published')->default(false);
                $table->date('banns_publication_date_1')->nullable();
                $table->date('banns_publication_date_2')->nullable();
                $table->date('banns_publication_date_3')->nullable();
                $table->boolean('marriage_license_obtained')->default(false);
                $table->string('marriage_license_number')->nullable();
                $table->date('marriage_license_date')->nullable();

                // REGISTER INFORMATION
                $table->string('marriage_book_number')->nullable();
                $table->integer('marriage_page_number')->nullable();
                $table->string('marriage_certificate_number')->nullable();

                // PRE-MARRIAGE REQUIREMENTS
                $table->boolean('pre_marriage_course_completed')->default(false);
                $table->date('pre_marriage_course_date')->nullable();
                $table->string('pre_marriage_course_facilitator')->nullable();

                // SYSTEM RELATIONSHIPS
                $table->unsignedBigInteger('marriage_sacrament_id')->nullable(); // Link to sacraments table

                // SYSTEM FIELDS
                $table->text('notes')->nullable();
                $table->text('special_circumstances')->nullable(); // Any special notes about the marriage
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamps();

                // Foreign keys and indexes
                $table->foreign('husband_member_id')->references('id')->on('members')->onDelete('set null');
                $table->foreign('wife_member_id')->references('id')->on('members')->onDelete('set null');
                $table->foreign('marriage_sacrament_id')->references('id')->on('sacraments')->onDelete('set null');
                $table->foreign('recorded_by')->references('id')->on('users')->onDelete('set null');

                $table->index('record_number');
                $table->index('marriage_date');
                $table->index('husband_name');
                $table->index('wife_name');
                $table->index('marriage_location');
                $table->index('celebrant');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marriage_records');
    }
};
