<?php
// filepath: database/migrations/2025_06_14_000004_create_group_members_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('community_groups')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->enum('role', [
                'member',
                'leader',
                'assistant_leader',
                'secretary',
                'treasurer'
            ])->default('member');
            $table->date('joined_date');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes and constraints
            $table->unique(['group_id', 'member_id'], 'unique_group_member');
            $table->index(['group_id']);
            $table->index(['member_id']);
            $table->index(['status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('group_members');
    }
};