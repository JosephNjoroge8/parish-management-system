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
        Schema::create('tithes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->decimal('amount', 10, 2);
            $table->enum('tithe_type', ['tithe', 'offering', 'special_collection', 'donation', 'thanksgiving', 'project_contribution']);
            $table->enum('payment_method', ['cash', 'check', 'mobile_money', 'bank_transfer', 'card']);
            $table->date('date_given');
            $table->string('purpose')->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for performance
            $table->index(['member_id', 'date_given']);
            $table->index(['tithe_type', 'date_given']);
            $table->index('date_given');
            $table->index('payment_method');
            $table->index('receipt_number');
            $table->index(['date_given', 'amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tithes');
    }
};