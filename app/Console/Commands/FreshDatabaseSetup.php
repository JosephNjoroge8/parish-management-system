<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class FreshDatabaseSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parish:fresh-setup {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up fresh parish database with proper authentication, authorization, and sample data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏛️  Parish Management System - Fresh Database Setup');
        $this->info('====================================================');

        // Confirmation prompt unless --force is used
        if (! $this->option('force') && ! $this->confirm('This will completely reset your database and all data will be lost. Are you sure?')) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        try {
            $this->info('🔄 Starting fresh database setup...');

            // Step 1: Remove problematic schema dump if exists
            $this->removeSchemaFiles();

            // Step 2: Fresh migrate
            $this->info('📋 Running fresh migrations...');
            Artisan::call('migrate:fresh', ['--force' => true]);
            $this->info(Artisan::output());

            // Step 3: Seed the database (this will automatically run RolePermissionSeeder first)
            $this->info('🌱 Seeding database with roles, permissions, and sample data...');
            Artisan::call('db:seed', ['--force' => true]);
            $this->info(Artisan::output());

            // Step 4: Clear all caches to ensure fresh state
            $this->info('🧹 Clearing application caches...');
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            // Step 5: Optimize for better performance
            $this->info('⚡ Optimizing application...');
            Artisan::call('config:cache');
            Artisan::call('route:cache');

            $this->info('✅ Fresh database setup completed successfully!');
            $this->info('');
            $this->info('🔐 Authentication System Status:');
            $this->info('   ✓ Roles and permissions configured');
            $this->info('   ✓ Super admin account created');
            $this->info('   ✓ Role-based access control enabled');
            $this->info('');
            $this->info('👤 Admin Access Credentials:');
            $this->info('   Email: admin@parish.com');
            $this->info('   Password: admin123');
            $this->info('   Role: Super Administrator');
            $this->info('');
            $this->info('🚀 Additional Test Users:');
            $this->info('   Priest: priest@parish.com / priest123 (Admin)');
            $this->info('   Secretary: secretary@parish.com / secretary123 (Secretary)');
            $this->info('   Treasurer: treasurer@parish.com / treasurer123 (Treasurer)');
            $this->info('');
            $this->info('🌐 You can now start the application:');
            $this->info('   php artisan serve');
            $this->info('   npm run dev');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Setup failed: '.$e->getMessage());
            Log::error('Fresh database setup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Remove problematic schema files that might interfere with migrations
     */
    private function removeSchemaFiles(): void
    {
        $schemaFiles = [
            database_path('schema/sqlite-schema.sql'),
            database_path('schema/sqlite-schema.dump'),
            database_path('schema/mysql-schema.sql'),
            database_path('schema/pgsql-schema.sql'),
        ];

        foreach ($schemaFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
                $this->info('🗑️  Removed schema file: '.basename($file));
            }
        }

        // Also clean the schema directory if it's empty
        $schemaDir = database_path('schema');
        if (File::isDirectory($schemaDir) && count(File::files($schemaDir)) === 0) {
            File::deleteDirectory($schemaDir);
            $this->info('🗑️  Removed empty schema directory');
        }
    }
}
