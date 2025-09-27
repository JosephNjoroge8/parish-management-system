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
            $table->string('location');
            $table->string('celebrant'); // Priest/Minister who performed the sacrament
            $table->string('witness_1')->nullable();
            $table->string('witness_2')->nullable();
            $table->string('godparent_1')->nullable();
            $table->string('godparent_2')->nullable();
            $table->string('certificate_number')->nullable();
            $table->string('book_number')->nullable();
            $table->string('page_number')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for performance
            $table->index(['member_id', 'sacrament_type']);
            $table->index('sacrament_type');
            $table->index('sacrament_date');
            $table->index(['sacrament_type', 'sacrament_date']);
            $table->index('certificate_number');
            $table->index('location');
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