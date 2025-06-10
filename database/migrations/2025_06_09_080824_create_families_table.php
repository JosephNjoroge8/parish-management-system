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
            $table->string('address');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('deanery');
            $table->string('parish');
            $table->unsignedBigInteger('head_of_family_id')->nullable();
            $table->timestamps();
            
            $table->index(['deanery', 'parish']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('families');
    }
};