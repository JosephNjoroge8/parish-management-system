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
        // Add foreign key constraints
        Schema::table('members', function (Blueprint $table) {
            $table->foreign('family_id')->references('id')->on('families')->onDelete('set null');
            $table->foreign('parent_id')->references('id')->on('members')->onDelete('set null');
            $table->foreign('godparent_id')->references('id')->on('members')->onDelete('set null');
            $table->foreign('minister_id')->references('id')->on('members')->onDelete('set null');
        });

        Schema::table('sacraments', function (Blueprint $table) {
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });

        Schema::table('baptism_records', function (Blueprint $table) {
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });

        Schema::table('marriage_records', function (Blueprint $table) {
            $table->foreign('husband_member_id')->references('id')->on('members')->onDelete('set null');
            $table->foreign('wife_member_id')->references('id')->on('members')->onDelete('set null');
        });

        Schema::table('tithes', function (Blueprint $table) {
            $table->foreign('member_id')->references('id')->on('members')->onDelete('set null');
        });

        Schema::table('activity_participants', function (Blueprint $table) {
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('set null');
        });

        Schema::table('family_relationships', function (Blueprint $table) {
            $table->foreign('family_id')->references('id')->on('families')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });

        Schema::table('group_members', function (Blueprint $table) {
            $table->foreign('group_id')->references('id')->on('community_groups')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });

        // Add performance indexes to improve query performance
        Schema::table('members', function (Blueprint $table) {
            // Additional composite indexes for better performance
            $table->index(['membership_status', 'created_at'], 'idx_status_created');
            $table->index(['local_church', 'matrimony_status'], 'idx_church_matrimony');
            $table->index(['phone', 'email'], 'idx_contact_info');
        });

        Schema::table('tithes', function (Blueprint $table) {
            $table->index(['contribution_date', 'amount'], 'idx_date_amount');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->index(['activity_date', 'activity_status'], 'idx_date_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['family_id']);
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['godparent_id']);
            $table->dropForeign(['minister_id']);
        });

        Schema::table('sacraments', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });

        Schema::table('baptism_records', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });

        Schema::table('marriage_records', function (Blueprint $table) {
            $table->dropForeign(['husband_member_id']);
            $table->dropForeign(['wife_member_id']);
        });

        Schema::table('tithes', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });

        Schema::table('activity_participants', function (Blueprint $table) {
            $table->dropForeign(['activity_id']);
            $table->dropForeign(['member_id']);
        });

        Schema::table('family_relationships', function (Blueprint $table) {
            $table->dropForeign(['family_id']);
            $table->dropForeign(['member_id']);
        });

        Schema::table('group_members', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['member_id']);
        });

        // Drop additional indexes
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex('idx_status_created');
            $table->dropIndex('idx_church_matrimony');
            $table->dropIndex('idx_contact_info');
        });

        Schema::table('tithes', function (Blueprint $table) {
            $table->dropIndex('idx_date_amount');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('idx_date_status');
        });
    }
};
