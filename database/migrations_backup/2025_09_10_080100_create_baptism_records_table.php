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
            
            // Personal information
            $table->string('father_name');
            $table->string('mother_name');
            $table->string('tribe');
            $table->string('birth_village');
            $table->string('county');
            $table->date('birth_date');
            $table->string('residence');
            
            // Baptism information
            $table->string('baptism_location');
            $table->date('baptism_date');
            $table->string('baptized_by');
            $table->string('sponsor');
            
            // Eucharist information
            $table->string('eucharist_location')->nullable();
            $table->date('eucharist_date')->nullable();
            
            // Confirmation information
            $table->string('confirmation_location')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->string('confirmation_number')->nullable();
            $table->string('confirmation_register_number')->nullable();
            
            // Marriage information
            $table->string('marriage_spouse')->nullable();
            $table->string('marriage_location')->nullable();
            $table->date('marriage_date')->nullable();
            $table->string('marriage_register_number')->nullable();
            $table->string('marriage_number')->nullable();
            
            // Links to member and sacrament record
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('baptism_sacrament_id')->nullable()->constrained('sacraments')->onDelete('set null');
            $table->foreignId('eucharist_sacrament_id')->nullable()->constrained('sacraments')->onDelete('set null');
            $table->foreignId('confirmation_sacrament_id')->nullable()->constrained('sacraments')->onDelete('set null');
            $table->foreignId('marriage_sacrament_id')->nullable()->constrained('sacraments')->onDelete('set null');
            
            $table->timestamps();
            
            // Add indexes
            $table->index('record_number');
            $table->index(['father_name', 'mother_name']);
            $table->index('baptism_date');
            $table->index('member_id');
        });

        // Update sacraments table to add detailed_record_id and detailed_record_type for polymorphic relationship
        Schema::table('sacraments', function (Blueprint $table) {
            $table->dropIndex('unique_member_sacrament'); // Remove previous unique constraint
            $table->string('detailed_record_type')->nullable();
            $table->unsignedBigInteger('detailed_record_id')->nullable();
            $table->index(['detailed_record_type', 'detailed_record_id']);
            
            // Allow multiple records of the same sacrament type but with unique certificate numbers
            $table->unique(['member_id', 'sacrament_type', 'certificate_number'], 'unique_member_sacrament_cert');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sacraments', function (Blueprint $table) {
            $table->dropIndex(['detailed_record_type', 'detailed_record_id']);
            $table->dropColumn(['detailed_record_type', 'detailed_record_id']);
            $table->dropIndex('unique_member_sacrament_cert');
            $table->unique(['member_id', 'sacrament_type'], 'unique_member_sacrament');
        });

        Schema::dropIfExists('baptism_records');
    }
};
