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
        Schema::create('sacraments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->enum('sacrament_type', ['baptism', 'confirmation', 'marriage']);
            $table->date('sacrament_date');
            $table->string('location')->nullable(); // Church where sacrament was performed
            $table->string('celebrant')->nullable(); // Priest/Minister who performed sacrament
            $table->string('witness_1')->nullable();
            $table->string('witness_2')->nullable();
            $table->string('godparent_1')->nullable(); // For baptism/confirmation
            $table->string('godparent_2')->nullable();
            $table->string('certificate_number')->nullable();
            $table->string('book_number')->nullable(); // Register book number
            $table->integer('page_number')->nullable(); // Register page number
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable(); // User who recorded this
            
            // Polymorphic relationship to detailed records (baptism_records, marriage_records)
            $table->string('detailed_record_type')->nullable(); // Model class name
            $table->unsignedBigInteger('detailed_record_id')->nullable(); // ID in the specific table
            
            $table->timestamps();

            // Foreign keys and indexes
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['member_id', 'sacrament_type']);
            $table->index('sacrament_date');
            $table->index('sacrament_type');
            $table->index(['detailed_record_type', 'detailed_record_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sacraments');
    }
};