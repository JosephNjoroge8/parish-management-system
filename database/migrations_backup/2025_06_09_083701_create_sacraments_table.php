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
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->enum('sacrament_type', [
                'baptism', 
                'eucharist', 
                'confirmation', 
                'reconciliation', 
                'anointing', 
                'marriage', 
                'holy_orders'
            ]);
            $table->date('sacrament_date');
            $table->string('location')->nullable();
            $table->string('celebrant')->nullable();
            $table->string('witness_1')->nullable();
            $table->string('witness_2')->nullable();
            $table->string('godparent_1')->nullable();
            $table->string('godparent_2')->nullable();
            $table->string('certificate_number')->nullable();
            $table->string('book_number')->nullable();
            $table->string('page_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index(['member_id', 'sacrament_type']);
            $table->index(['sacrament_type']);
            $table->index(['sacrament_date']);
            $table->index(['certificate_number']);
            
            // Ensure unique sacrament type per member (except reconciliation which can be repeated)
            $table->unique(['member_id', 'sacrament_type'], 'unique_member_sacrament');
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
