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
            $table->unsignedBigInteger('member_id')->nullable()->index();
            $table->string('contributor_name', 100)->index(); // In case member_id is not available
            $table->decimal('amount', 10, 2);
            $table->date('contribution_date')->index();
            $table->enum('payment_method', ['cash', 'mpesa', 'bank_transfer', 'cheque'])->default('cash');
            $table->string('reference_number', 50)->nullable();
            $table->enum('tithe_type', ['regular', 'thanksgiving', 'special', 'pledge'])->default('regular');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for performance with custom names to avoid MySQL 64-char limit
            $table->index(['contribution_date', 'tithe_type'], 'idx_contrib_date_type');
            $table->index(['contributor_name', 'contribution_date'], 'idx_contrib_name_date');
            $table->index(['amount', 'contribution_date'], 'idx_amount_date');
            $table->index(['payment_method', 'contribution_date'], 'idx_payment_date');
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
