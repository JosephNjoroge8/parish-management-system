<?php
// filepath: database/migrations/2025_06_14_000002_create_tithes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tithes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('tithe_type', [
                'tithe',
                'offering',
                'special_collection',
                'donation',
                'thanksgiving',
                'project_contribution'
            ])->default('tithe');
            $table->enum('payment_method', [
                'cash',
                'check',
                'mobile_money',
                'bank_transfer',
                'card'
            ])->default('cash');
            $table->date('date_given');
            $table->string('purpose')->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index(['member_id', 'date_given']);
            $table->index(['tithe_type']);
            $table->index(['date_given']);
            $table->index(['receipt_number']);
            $table->index(['reference_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tithes');
    }
};