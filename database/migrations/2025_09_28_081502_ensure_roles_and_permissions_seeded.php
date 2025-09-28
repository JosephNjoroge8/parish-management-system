<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations - Ensures roles and permissions are always seeded
     */
    public function up(): void
    {
        try {
            // Check if we have the necessary tables for Spatie permissions
            if (Schema::hasTable('roles') && Schema::hasTable('permissions') && Schema::hasTable('model_has_roles')) {
                
                // Check if we already have roles seeded
                $roleCount = \Illuminate\Support\Facades\DB::table('roles')->count();
                $permissionCount = \Illuminate\Support\Facades\DB::table('permissions')->count();
                
                // Only seed if we don't have roles and permissions already
                if ($roleCount === 0 && $permissionCount === 0) {
                    Log::info('Migration: Running RolePermissionSeeder as part of migration');
                    
                    // Run the RolePermissionSeeder
                    Artisan::call('db:seed', [
                        '--class' => 'Database\\Seeders\\RolePermissionSeeder',
                        '--force' => true
                    ]);
                    
                    Log::info('Migration: RolePermissionSeeder completed successfully');
                } else {
                    Log::info('Migration: Roles and permissions already exist, skipping seeder', [
                        'roles' => $roleCount,
                        'permissions' => $permissionCount
                    ]);
                }
            } else {
                Log::warning('Migration: Spatie permission tables not found, skipping role seeding');
            }
            
        } catch (\Exception $e) {
            Log::error('Migration: Failed to seed roles and permissions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Don't fail the migration - just log the error
            // This ensures the migration system doesn't break if seeding fails
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't remove roles and permissions on rollback
        // as they might be in use by the system
        Log::info('Migration rollback: Keeping roles and permissions intact for system stability');
    }
};
