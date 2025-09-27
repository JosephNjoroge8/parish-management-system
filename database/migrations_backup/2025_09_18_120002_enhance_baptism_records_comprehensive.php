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
        // Drop existing baptism_records table to rebuild with comprehensive structure
        Schema::dropIfExists('baptism_records');
        
        Schema::create('baptism_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_number')->unique()->index();
            
            // BAPTISM CARD PERSONAL INFORMATION - As specified
            $table->string('father_name'); // fathers name
            $table->string('mother_name'); // mothers name
            $table->string('tribe'); // Tribe
            $table->string('birth_village'); // born on (village)
            $table->string('county'); // county
            $table->date('birth_date'); // date
            $table->text('residence'); // residence
            
            // BAPTISM INFORMATION - As specified
            $table->string('baptism_location'); // BAPTISM: At
            $table->date('baptism_date'); // Date
            $table->string('baptized_by'); // baptized by
            $table->string('sponsor'); // sponsor
            
            // EUCHARIST INFORMATION - As specified
            $table->string('eucharist_location')->nullable(); // EUCHARIST: At
            $table->date('eucharist_date')->nullable(); // Date
            
            // CONFIRMATION INFORMATION - As specified
            $table->string('confirmation_location')->nullable(); // CONFIRMATION: At
            $table->date('confirmation_date')->nullable(); // Date
            $table->string('confirmation_register_number')->nullable(); // Reg.NO
            $table->string('confirmation_number')->nullable(); // Conf.No
            
            // MARRIAGE INFORMATION - As specified
            $table->string('marriage_spouse')->nullable(); // MARRIAGE: Together with
            $table->string('marriage_location')->nullable(); // At
            $table->date('marriage_date')->nullable(); // Date
            $table->string('marriage_register_number')->nullable(); // Reg.NO
            $table->string('marriage_number')->nullable(); // Marr.NO
            
            // SYSTEM RELATIONSHIPS - To avoid data redundancy
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('baptism_sacrament_id')->nullable()->constrained('sacraments')->onDelete('set null');
            $table->foreignId('eucharist_sacrament_id')->nullable()->constrained('sacraments')->onDelete('set null');
            $table->foreignId('confirmation_sacrament_id')->nullable()->constrained('sacraments')->onDelete('set null');
            $table->foreignId('marriage_sacrament_id')->nullable()->constrained('sacraments')->onDelete('set null');
            
            $table->timestamps();
            
            // Add comprehensive indexes for performance
            $table->index('record_number');
            $table->index(['father_name', 'mother_name']);
            $table->index('baptism_date');
            $table->index('baptism_location');
            $table->index('birth_village');
            $table->index('county');
            $table->index('tribe');
            $table->index('member_id');
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
