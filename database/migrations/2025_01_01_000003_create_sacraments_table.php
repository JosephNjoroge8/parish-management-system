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
            $table->unsignedBigInteger('member_id')->index();
            $table->enum('sacrament_type', ['baptism', 'confirmation', 'eucharist', 'marriage', 'ordination', 'anointing']);
            $table->date('sacrament_date')->index();
            $table->string('location', 100)->nullable();
            $table->string('officiant', 100)->nullable();
            $table->string('register_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['member_id', 'sacrament_type']);
            $table->index(['sacrament_date', 'sacrament_type']);
            $table->index(['location', 'sacrament_type']);
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
