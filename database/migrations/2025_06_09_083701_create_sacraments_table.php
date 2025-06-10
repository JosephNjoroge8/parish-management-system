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
            $table->string('name'); // e.g., 'Baptism', 'Confirmation', 'First Communion'
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('minimum_age')->nullable(); // Minimum age requirement
            $table->text('requirements')->nullable(); // Requirements for the sacrament
            $table->timestamps();
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
