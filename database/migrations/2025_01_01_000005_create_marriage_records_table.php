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
            $table->unsignedBigInteger('husband_member_id')->nullable()->index();
            $table->unsignedBigInteger('wife_member_id')->nullable()->index();
            $table->date('marriage_date')->index();
            $table->string('marriage_location', 100)->nullable();
            $table->string('county', 50)->nullable();
            $table->string('sub_county', 50)->nullable();
            $table->string('entry_number', 50)->nullable()->unique();
            $table->string('certificate_number', 50)->nullable()->unique();
            $table->string('religion', 50)->nullable();
            $table->string('license_number', 50)->nullable();
            $table->string('officiant_name', 100)->nullable();
            $table->string('witness1_name', 100)->nullable();
            $table->string('witness2_name', 100)->nullable();

            // Husband Details
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

            // Wife Details
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

            // Additional Marriage Information
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for performance with custom names to avoid MySQL 64-char limit
            $table->index(['marriage_date', 'marriage_location'], 'idx_marriage_date_loc');
            $table->index(['husband_name', 'wife_name'], 'idx_husband_wife');
            $table->index(['officiant_name'], 'idx_officiant');
            $table->index(['certificate_number'], 'idx_cert_num');
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
