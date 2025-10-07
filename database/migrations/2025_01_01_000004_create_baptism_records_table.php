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
            $table->unsignedBigInteger('member_id')->index();
            $table->date('baptism_date')->index();
            $table->string('baptism_location', 100)->nullable();
            $table->string('baptized_by', 100)->nullable(); // Minister/Priest
            $table->string('father_name', 100)->nullable();
            $table->string('mother_name', 100)->nullable();
            $table->string('sponsor', 100)->nullable(); // Godparent
            $table->string('register_number', 50)->nullable()->unique();
            $table->string('certificate_number', 50)->nullable()->unique();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for performance with custom names to avoid MySQL 64-char limit
            $table->index(['baptism_date', 'baptism_location'], 'idx_baptism_date_loc');
            $table->index(['baptized_by'], 'idx_baptized_by');
            $table->index(['register_number'], 'idx_register_num');
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
