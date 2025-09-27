<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration creates missing tables, adds missing columns,
     * and implements comprehensive performance indexes.
     */
    public function up(): void
    {
        // 1. Create tithes table
        if (!Schema::hasTable('tithes')) {
            Schema::create('tithes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('member_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('family_id')->nullable()->constrained('families')->onDelete('set null');
                $table->decimal('amount', 10, 2);
                $table->date('date');
                $table->string('payment_method', 50)->default('cash');
                $table->string('reference_number', 100)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                
                // Performance indexes
                $table->index('member_id');
                $table->index('family_id');
                $table->index('date');
                $table->index(['member_id', 'date']);
                $table->index('amount');
                $table->index('payment_method');
                $table->index(['date', 'amount']);
                $table->index('recorded_by');
            });
        }
        
        // 2. Create donations table
        if (!Schema::hasTable('donations')) {
            Schema::create('donations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('donor_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('donor_name')->nullable();
                $table->string('donor_email')->nullable();
                $table->string('donor_phone', 20)->nullable();
                $table->decimal('amount', 10, 2);
                $table->date('donation_date');
                $table->string('donation_type', 50)->default('general');
                $table->string('purpose')->nullable();
                $table->string('payment_method', 50)->default('cash');
                $table->string('reference_number', 100)->nullable();
                $table->boolean('is_anonymous')->default(false);
                $table->string('receipt_number', 100)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                
                // Performance indexes
                $table->index('donor_id');
                $table->index('donation_date');
                $table->index('donation_type');
                $table->index('amount');
                $table->index(['donation_date', 'donation_type']);
                $table->index(['donation_date', 'amount']);
                $table->index('recorded_by');
            });
        }
        
        // 3. Create events table
        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->date('event_date');
                $table->time('event_time')->nullable();
                $table->date('end_date')->nullable();
                $table->time('end_time')->nullable();
                $table->string('location')->nullable();
                $table->string('event_type', 50)->default('general');
                $table->string('organizer')->nullable();
                $table->integer('max_participants')->nullable();
                $table->boolean('registration_required')->default(false);
                $table->dateTime('registration_deadline')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('status', 50)->default('planned');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                
                // Performance indexes
                $table->index('event_date');
                $table->index('is_active');
                $table->index('status');
                $table->index('event_type');
                $table->index(['is_active', 'event_date']);
                $table->index(['is_active', 'created_at']);
                $table->index('created_by');
            });
        }
        
        // 4. Create ministries table
        if (!Schema::hasTable('ministries')) {
            Schema::create('ministries', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('ministry_type', 50)->default('service');
                $table->foreignId('leader_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('meeting_schedule')->nullable();
                $table->string('meeting_location')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('status', 50)->default('active');
                $table->text('requirements')->nullable();
                $table->string('contact_email')->nullable();
                $table->string('contact_phone', 20)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                
                // Performance indexes
                $table->index('is_active');
                $table->index('leader_id');
                $table->index('ministry_type');
                $table->index('status');
                $table->index(['is_active', 'ministry_type']);
                $table->index(['is_active', 'created_at']);
                $table->index('created_by');
            });
        }
        
        // 5. Create contributions table
        if (!Schema::hasTable('contributions')) {
            Schema::create('contributions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('member_id')->constrained('users')->onDelete('cascade');
                $table->string('contribution_type', 100);
                $table->decimal('amount', 10, 2);
                $table->date('date');
                $table->string('payment_method', 50)->default('cash');
                $table->string('reference_number', 100)->nullable();
                $table->string('purpose')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                
                // Performance indexes
                $table->index('member_id');
                $table->index('date');
                $table->index('contribution_type');
                $table->index(['member_id', 'date']);
                $table->index('amount');
                $table->index(['date', 'amount']);
                $table->index('recorded_by');
            });
        }
        
        // 6. Add missing columns to existing tables
        $this->addMissingColumns();
        
        // 7. Add performance indexes to existing tables
        $this->addPerformanceIndexes();
    }
    
    /**
     * Add missing columns to existing tables
     */
    private function addMissingColumns(): void
    {
        // Add is_active to community_groups
        if (Schema::hasTable('community_groups') && !Schema::hasColumn('community_groups', 'is_active')) {
            Schema::table('community_groups', function (Blueprint $table) {
                $table->boolean('is_active')->default(true);
            });
        }
        
        // Add is_active to activities
        if (Schema::hasTable('activities') && !Schema::hasColumn('activities', 'is_active')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->boolean('is_active')->default(true);
            });
        }
        
        // Add is_active and name to families
        if (Schema::hasTable('families')) {
            if (!Schema::hasColumn('families', 'is_active')) {
                Schema::table('families', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true);
                });
            }
            
            if (!Schema::hasColumn('families', 'name')) {
                Schema::table('families', function (Blueprint $table) {
                    $table->string('name')->nullable();
                });
                
                // Copy family_name to name
                DB::statement('UPDATE families SET name = family_name WHERE name IS NULL');
            }
        }
        
        // Add name and date_received to sacraments
        if (Schema::hasTable('sacraments')) {
            if (!Schema::hasColumn('sacraments', 'name')) {
                Schema::table('sacraments', function (Blueprint $table) {
                    $table->string('name')->nullable();
                });
                
                // Copy sacrament_type to name
                DB::statement('UPDATE sacraments SET name = sacrament_type WHERE name IS NULL');
            }
            
            if (!Schema::hasColumn('sacraments', 'date_received')) {
                Schema::table('sacraments', function (Blueprint $table) {
                    $table->date('date_received')->nullable();
                });
                
                // Copy sacrament_date to date_received
                DB::statement('UPDATE sacraments SET date_received = sacrament_date WHERE date_received IS NULL');
            }
        }
    }
    
    /**
     * Add performance indexes to existing tables
     */
    private function addPerformanceIndexes(): void
    {
        // Users table indexes
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('is_active');
                $table->index('created_by');
                $table->index('last_login_at');
                $table->index(['is_active', 'email']);
                $table->index(['is_active', 'created_at']);
            });
        }
        
        // Community groups indexes
        if (Schema::hasTable('community_groups')) {
            Schema::table('community_groups', function (Blueprint $table) {
                $table->index('is_active');
                $table->index(['is_active', 'group_type']);
            });
        }
        
        // Activities indexes
        if (Schema::hasTable('activities')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->index('end_date');
                $table->index('is_active');
                $table->index(['is_active', 'activity_type']);
                $table->index(['start_date', 'end_date']);
                $table->index(['status', 'start_date']);
            });
        }
        
        // Families indexes
        if (Schema::hasTable('families')) {
            Schema::table('families', function (Blueprint $table) {
                $table->index('is_active');
                $table->index('name');
                $table->index(['is_active', 'name']);
                $table->index(['is_active', 'created_at']);
            });
        }
        
        // Sacraments indexes
        if (Schema::hasTable('sacraments')) {
            Schema::table('sacraments', function (Blueprint $table) {
                $table->index('date_received');
                $table->index('name');
                $table->index(['member_id', 'date_received']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop created tables
        Schema::dropIfExists('contributions');
        Schema::dropIfExists('ministries');
        Schema::dropIfExists('events');
        Schema::dropIfExists('donations');
        Schema::dropIfExists('tithes');
        
        // Remove added columns (note: indexes will be dropped automatically)
        if (Schema::hasTable('community_groups') && Schema::hasColumn('community_groups', 'is_active')) {
            Schema::table('community_groups', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
        
        if (Schema::hasTable('activities') && Schema::hasColumn('activities', 'is_active')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
        
        if (Schema::hasTable('families')) {
            if (Schema::hasColumn('families', 'is_active')) {
                Schema::table('families', function (Blueprint $table) {
                    $table->dropColumn('is_active');
                });
            }
            if (Schema::hasColumn('families', 'name')) {
                Schema::table('families', function (Blueprint $table) {
                    $table->dropColumn('name');
                });
            }
        }
        
        if (Schema::hasTable('sacraments')) {
            if (Schema::hasColumn('sacraments', 'name')) {
                Schema::table('sacraments', function (Blueprint $table) {
                    $table->dropColumn('name');
                });
            }
            if (Schema::hasColumn('sacraments', 'date_received')) {
                Schema::table('sacraments', function (Blueprint $table) {
                    $table->dropColumn('date_received');
                });
            }
        }
    }
};
