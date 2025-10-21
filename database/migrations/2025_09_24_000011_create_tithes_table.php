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
        if (Schema::hasTable('tithes')) {
            return;
        }

        Schema::create('tithes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->decimal('amount', 10, 2);
            $table->enum('tithe_type', [
                'tithe', 'offering', 'special_collection', 'thanksgiving',
                'project_fund', 'harambee', 'development_fund', 'other',
            ])->default('tithe');
            $table->enum('payment_method', [
                'cash', 'mpesa', 'bank_transfer', 'cheque', 'other',
            ])->default('cash');
            $table->date('date_given');
            $table->string('purpose')->nullable(); // E.g., "Church Building Fund", "Christmas Offering"
            $table->string('receipt_number')->unique()->nullable();
            $table->string('reference_number')->nullable(); // M-Pesa code, cheque number, etc.
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('collected_by')->nullable(); // User who recorded this
            $table->timestamps();

            // Foreign keys and indexes
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('collected_by')->references('id')->on('users')->onDelete('set null');

            $table->index('date_given');
            $table->index('tithe_type');
            $table->index('payment_method');
            $table->index('receipt_number');
            $table->index(['member_id', 'date_given']);
            $table->index(['date_given', 'tithe_type']);
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
