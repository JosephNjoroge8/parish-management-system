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
        Schema::create('baptism_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_number')->unique();
            $table->unsignedBigInteger('member_id');
            
            // PERSONAL INFORMATION (As per baptism card specification)
            $table->string('father_name');
            $table->string('mother_name');
            $table->string('tribe');
            $table->string('birth_village'); // born on (village)
            $table->string('county');
            $table->date('birth_date');
            $table->text('residence');
            
            // BAPTISM INFORMATION
            $table->string('baptism_location'); // BAPTISM: At
            $table->date('baptism_date'); // Date
            $table->string('baptized_by'); // baptized by
            $table->string('sponsor'); // sponsor
            
            // EUCHARIST INFORMATION
            $table->string('eucharist_location')->nullable(); // EUCHARIST: At
            $table->date('eucharist_date')->nullable(); // Date
            
            // CONFIRMATION INFORMATION
            $table->string('confirmation_location')->nullable(); // CONFIRMATION: At
            $table->date('confirmation_date')->nullable(); // Date
            $table->string('confirmation_register_number')->nullable(); // Reg.NO
            $table->string('confirmation_number')->nullable(); // Conf.No
            
            // MARRIAGE INFORMATION
            $table->string('marriage_spouse')->nullable(); // MARRIAGE: Together with
            $table->string('marriage_location')->nullable(); // At
            $table->date('marriage_date')->nullable(); // Date
            $table->string('marriage_register_number')->nullable(); // Reg.NO
            $table->string('marriage_number')->nullable(); // Marr.NO
            
            // SYSTEM RELATIONSHIPS (to avoid redundancy)
            $table->unsignedBigInteger('baptism_sacrament_id')->nullable();
            $table->unsignedBigInteger('eucharist_sacrament_id')->nullable();
            $table->unsignedBigInteger('confirmation_sacrament_id')->nullable();
            $table->unsignedBigInteger('marriage_sacrament_id')->nullable();
            
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('baptism_sacrament_id')->references('id')->on('sacraments')->onDelete('set null');
            $table->foreign('eucharist_sacrament_id')->references('id')->on('sacraments')->onDelete('set null');
            $table->foreign('confirmation_sacrament_id')->references('id')->on('sacraments')->onDelete('set null');
            $table->foreign('marriage_sacrament_id')->references('id')->on('sacraments')->onDelete('set null');

            // Indexes
            $table->index('member_id');
            $table->index('record_number');
            $table->index(['father_name', 'mother_name']);
            $table->index('tribe');
            $table->index('baptism_location');
            $table->index('baptism_date');
            $table->index(['birth_date', 'baptism_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('baptism_records');
    }
};