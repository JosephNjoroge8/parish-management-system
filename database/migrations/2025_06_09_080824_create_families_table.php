<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('family_name');
            $table->string('head_of_family'); // String field instead of foreign key
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('deanery')->nullable();
            $table->string('parish')->nullable();
            $table->string('family_code')->unique()->nullable();
            $table->string('parish_section')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            $table->index(['deanery', 'parish']);
            $table->index('family_name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('families');
    }
};