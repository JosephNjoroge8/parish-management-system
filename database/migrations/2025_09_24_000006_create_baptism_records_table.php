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
            $table->string('record_number')->unique()->nullable();
            $table->unsignedBigInteger('member_id');
            
            // BAPTISM CARD PERSONAL INFORMATION - As per Catholic Church requirements
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('tribe')->nullable();
            $table->string('birth_village')->nullable(); // Born at (village)
            $table->string('county')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('residence')->nullable();
            
            // BAPTISM INFORMATION
            $table->string('baptism_location')->nullable(); // Church where baptized
            $table->date('baptism_date')->nullable();
            $table->string('baptized_by')->nullable(); // Priest/Minister
            $table->string('sponsor')->nullable(); // Godparent/Sponsor
            
            // FIRST HOLY COMMUNION (EUCHARIST) INFORMATION
            $table->string('eucharist_location')->nullable();
            $table->date('eucharist_date')->nullable();
            
            // CONFIRMATION INFORMATION
            $table->string('confirmation_location')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->string('confirmation_register_number')->nullable();
            $table->string('confirmation_number')->nullable();
            
            // MARRIAGE INFORMATION (if applicable)
            $table->string('marriage_spouse')->nullable(); // Name of spouse
            $table->string('marriage_location')->nullable();
            $table->date('marriage_date')->nullable();
            $table->string('marriage_register_number')->nullable();
            $table->string('marriage_number')->nullable();
            
            // SYSTEM RELATIONSHIPS
            $table->unsignedBigInteger('baptism_sacrament_id')->nullable(); // Link to sacraments table
            $table->unsignedBigInteger('confirmation_sacrament_id')->nullable();
            $table->unsignedBigInteger('marriage_sacrament_id')->nullable();
            
            // REGISTER INFORMATION
            $table->string('baptism_book_number')->nullable();
            $table->integer('baptism_page_number')->nullable();
            $table->string('baptism_certificate_number')->nullable();
            
            // SYSTEM FIELDS
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();

            // Foreign keys and indexes
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('baptism_sacrament_id')->references('id')->on('sacraments')->onDelete('set null');
            $table->foreign('confirmation_sacrament_id')->references('id')->on('sacraments')->onDelete('set null');
            $table->foreign('marriage_sacrament_id')->references('id')->on('sacraments')->onDelete('set null');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index('record_number');
            $table->index('baptism_date');
            $table->index(['father_name', 'mother_name']);
            $table->index('tribe');
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