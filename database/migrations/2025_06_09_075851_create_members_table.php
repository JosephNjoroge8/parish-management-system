<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female']);
            $table->string('phone')->nullable();
            $table->string('email')->nullable(); // Remove unique constraint - members can share family emails
            $table->string('id_number')->unique()->nullable();
            $table->text('address')->nullable(); // Make nullable - can inherit from family
            $table->string('occupation')->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->default('single');
            $table->date('membership_date');
            $table->enum('membership_status', ['active', 'inactive', 'transferred', 'deceased'])->default('active');
            
            // Better foreign key handling
            $table->foreignId('family_id')
                  ->nullable()
                  ->constrained('families')
                  ->onDelete('set null'); // Don't delete members if family is deleted
            
            $table->string('relationship_to_head')->nullable();
            $table->text('special_needs')->nullable();
            $table->text('notes')->nullable();
            
            // Add parish-specific fields
            $table->enum('member_type', ['adult', 'youth', 'child'])->default('adult');
            $table->date('baptism_date')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->date('first_communion_date')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['first_name', 'last_name']);
            $table->index(['membership_status']);
            $table->index(['family_id']);
            $table->index(['member_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('members');
    }
};
