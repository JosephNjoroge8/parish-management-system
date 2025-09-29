#!/bin/bash

# ============================================================================
# Parish Management System - cPanel Deployment Script
# ============================================================================
# This script automatically configures your Parish Management System for production
# and handles compatibility between SQLite (development) and MySQL (production)
# Author: GitHub Copilot
# Version: 1.0
# ============================================================================

# Terminal colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Set script to exit on error
set -e

echo -e "${BLUE}===============================================${NC}"
echo -e "${BLUE}  Parish Management System - cPanel Deployment ${NC}"
echo -e "${BLUE}===============================================${NC}"
echo ""

# 1. Check if running in cPanel environment
if [ ! -f "/usr/local/cpanel/version" ]; then
    echo -e "${YELLOW}Warning: This doesn't appear to be a cPanel environment.${NC}"
    echo -e "Script will continue but may need adjustments for your hosting environment."
    echo ""
fi

# 2. Create log directory and file
mkdir -p logs
LOG_FILE="logs/deployment_$(date +%Y%m%d_%H%M%S).log"
exec > >(tee -a "$LOG_FILE") 2>&1

echo -e "${BLUE}Deployment log will be saved to:${NC} $LOG_FILE"
echo ""

# 3. Check PHP version
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo -e "PHP Version: ${GREEN}$PHP_VERSION${NC}"

if [[ $(echo "$PHP_VERSION" | cut -d. -f1) -lt 8 || ($(echo "$PHP_VERSION" | cut -d. -f1) -eq 8 && $(echo "$PHP_VERSION" | cut -d. -f2) -lt 1) ]]; then
    echo -e "${RED}Error: PHP 8.1 or higher required.${NC}"
    exit 1
fi

# 4. Check if Composer exists
if ! command -v composer &> /dev/null; then
    echo -e "${YELLOW}Composer not found. Attempting to install locally...${NC}"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --quiet
    rm composer-setup.php
    COMPOSER_CMD="php composer.phar"
    echo -e "${GREEN}Composer installed locally.${NC}"
else
    COMPOSER_CMD="composer"
    echo -e "Composer found: ${GREEN}$(composer --version)${NC}"
fi

# 5. Check if .env file exists
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}No .env file found. Creating from example...${NC}"
    cp .env.example .env
    echo -e "${GREEN}.env file created.${NC}"
fi

# 6. Create and configure cpanel.yml file for deployment
echo -e "${BLUE}Creating cPanel deployment configuration...${NC}"

cat > .cpanel.yml << 'EOF'
---
deployment:
  tasks:
    - export DEPLOYPATH=/home/$USER/public_html/
    - /bin/cp -R * $DEPLOYPATH
    - /bin/cp .env $DEPLOYPATH
    - /bin/cp .htaccess $DEPLOYPATH
    - cd $DEPLOYPATH && php artisan optimize:clear
    - cd $DEPLOYPATH && php artisan key:generate --force
    - cd $DEPLOYPATH && php artisan migrate --force
    - cd $DEPLOYPATH && php artisan config:cache
    - cd $DEPLOYPATH && php artisan route:cache
    - cd $DEPLOYPATH && php artisan view:cache
    - cd $DEPLOYPATH && php artisan storage:link
    - cd $DEPLOYPATH && php artisan db:configure-production
    - cd $DEPLOYPATH && php artisan db:optimize-mysql
    - cd $DEPLOYPATH && chmod -R 755 storage bootstrap/cache
    - cd $DEPLOYPATH && chmod 644 .env
EOF

echo -e "${GREEN}.cpanel.yml created successfully.${NC}"

# 7. Create compatibility class for SQLite to MySQL
echo -e "${BLUE}Creating database compatibility helper...${NC}"

mkdir -p app/Console/Commands

cat > app/Console/Commands/ConfigureProductionDatabase.php << 'EOF'
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConfigureProductionDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:configure-production';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure the database for production environment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Configuring database for production environment...');
        
        // Check current connection
        $connection = config('database.default');
        $this->info("Current database connection: {$connection}");
        
        // If .env specifies MySQL but we're using SQLite, update the configuration
        if ($connection === 'sqlite' && env('DB_CONNECTION') === 'mysql') {
            $this->info('Changing configuration to use MySQL...');
            
            // Update the database.php config directly (runtime only)
            config(['database.default' => 'mysql']);
            
            $this->info('Configuration updated to use MySQL');
        }
        
        // Verify connection to the database
        try {
            DB::connection()->getPdo();
            $this->info('Database connection successful: ' . DB::connection()->getDatabaseName());
        } catch (\Exception $e) {
            $this->error('Could not connect to the database: ' . $e->getMessage());
            return 1;
        }
        
        // Check if required tables exist
        $this->info('Checking database tables...');
        
        $requiredTables = ['users', 'members', 'families', 'tithes', 'activities', 'migrations'];
        $missingTables = [];
        
        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $missingTables[] = $table;
            }
        }
        
        if (count($missingTables) > 0) {
            $this->warn('Missing tables: ' . implode(', ', $missingTables));
            $this->info('Running migrations to create missing tables...');
            
            // Run migrations
            $this->call('migrate', [
                '--force' => true,
            ]);
        } else {
            $this->info('All required tables exist.');
        }
        
        // Update .env file to use MySQL if not already set
        $envContent = file_get_contents(base_path('.env'));
        
        if (strpos($envContent, 'DB_CONNECTION=sqlite') !== false) {
            $this->info('Updating .env file to use MySQL as default...');
            $envContent = str_replace('DB_CONNECTION=sqlite', 'DB_CONNECTION=mysql', $envContent);
            file_put_contents(base_path('.env'), $envContent);
        }
        
        $this->info('Database configuration complete!');
        return 0;
    }
}
EOF

# 8. Create MySQL optimization command
echo -e "${BLUE}Creating MySQL optimization command...${NC}"

cat > app/Console/Commands/OptimizeMysqlDatabase.php << 'EOF'
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OptimizeMysqlDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:optimize-mysql';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize MySQL database tables and indexes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = config('database.default');
        
        if ($connection !== 'mysql') {
            $this->info('This command is only for MySQL databases. Current connection: ' . $connection);
            return 0;
        }
        
        $this->info('Optimizing MySQL database...');
        
        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $tableColumn = 'Tables_in_' . config('database.connections.mysql.database');
        
        foreach ($tables as $table) {
            $tableName = $table->$tableColumn;
            $this->info("Optimizing table: {$tableName}");
            
            // Check and convert to InnoDB if needed
            $tableStatus = DB::select("SHOW TABLE STATUS WHERE Name = '{$tableName}'");
            if ($tableStatus[0]->Engine !== 'InnoDB') {
                $this->warn("Converting {$tableName} to InnoDB engine...");
                DB::statement("ALTER TABLE {$tableName} ENGINE = InnoDB");
            }
            
            // Check and update character set if needed
            if ($tableStatus[0]->Collation !== 'utf8mb4_unicode_ci') {
                $this->warn("Converting {$tableName} to utf8mb4 character set...");
                DB::statement("ALTER TABLE {$tableName} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
            
            // Optimize table
            DB::statement("OPTIMIZE TABLE {$tableName}");
            
            // Add common indexes if they don't exist
            $this->addCommonIndexes($tableName);
        }
        
        $this->info('Database optimization completed successfully!');
        return 0;
    }
    
    /**
     * Add common indexes to tables if they don't exist
     */
    protected function addCommonIndexes($tableName)
    {
        // Define common columns that should be indexed
        $indexableColumns = [
            'users' => ['email', 'name', 'created_at'],
            'members' => ['email', 'first_name', 'last_name', 'family_id', 'created_at'],
            'families' => ['family_name', 'head_of_family_id', 'created_at'],
            'tithes' => ['member_id', 'date', 'amount', 'created_at'],
            'activities' => ['title', 'start_date', 'created_at'],
            'activity_participants' => ['activity_id', 'member_id'],
            'baptism_records' => ['member_id', 'baptism_date'],
            'marriage_records' => ['groom_id', 'bride_id', 'marriage_date'],
            'sacraments' => ['member_id', 'sacrament_date', 'sacrament_type']
        ];
        
        // Skip if table isn't in our index list
        if (!isset($indexableColumns[$tableName])) {
            return;
        }
        
        // Get existing indexes
        $indexes = [];
        $indexResults = DB::select("SHOW INDEX FROM {$tableName}");
        foreach ($indexResults as $index) {
            $indexes[] = $index->Column_name;
        }
        
        // Add missing indexes
        foreach ($indexableColumns[$tableName] as $column) {
            // Check if column exists
            if (Schema::hasColumn($tableName, $column) && !in_array($column, $indexes)) {
                $this->line("  - Adding index for {$column}");
                try {
                    DB::statement("ALTER TABLE {$tableName} ADD INDEX idx_{$tableName}_{$column} ({$column})");
                } catch (\Exception $e) {
                    $this->warn("  - Could not add index for {$column}: " . $e->getMessage());
                }
            }
        }
        
        // Add composite indexes for common query patterns
        if ($tableName === 'members' && Schema::hasColumn($tableName, 'first_name') && Schema::hasColumn($tableName, 'last_name')) {
            try {
                DB::statement("ALTER TABLE {$tableName} ADD INDEX idx_members_name (first_name, last_name)");
            } catch (\Exception $e) {
                // Index might already exist
            }
        }
        
        if ($tableName === 'tithes' && Schema::hasColumn($tableName, 'date') && Schema::hasColumn($tableName, 'member_id')) {
            try {
                DB::statement("ALTER TABLE {$tableName} ADD INDEX idx_tithes_date_member (date, member_id)");
            } catch (\Exception $e) {
                // Index might already exist
            }
        }
    }
}
EOF

# 9. Create database compatibility fixer command
echo -e "${BLUE}Creating database compatibility fixer command...${NC}"

cat > app/Console/Commands/FixDatabaseCompatibility.php << 'EOF'
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixDatabaseCompatibility extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:fix-compatibility';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix database compatibility issues for production deployment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing database compatibility issues...');
        
        // Clear all caches to ensure fresh start
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        
        // Run migrations to ensure database is up to date
        $this->call('migrate', ['--force' => true]);
        
        // Configure database connection based on environment
        $this->call('db:configure-production');
        
        // If MySQL, optimize the database
        if (config('database.default') === 'mysql') {
            $this->call('db:optimize-mysql');
        }
        
        // Cache configuration for better performance
        $this->call('config:cache');
        
        $this->info('Database compatibility fixes completed successfully!');
        
        return 0;
    }
}
EOF

# 9. Create .htaccess file for Laravel in root
echo -e "${BLUE}Creating optimized .htaccess file...${NC}"

cat > .htaccess << 'EOF'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Optimize caching for static assets
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/gif "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
  ExpiresByType application/x-javascript "access plus 1 month"
  ExpiresByType text/javascript "access plus 1 month"
</IfModule>

# Compress HTML, CSS, JavaScript, Text, XML and fonts
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE application/javascript
  AddOutputFilterByType DEFLATE application/x-javascript
  AddOutputFilterByType DEFLATE text/css
  AddOutputFilterByType DEFLATE text/html
  AddOutputFilterByType DEFLATE text/javascript
  AddOutputFilterByType DEFLATE text/plain
  AddOutputFilterByType DEFLATE text/xml
</IfModule>

# Set security headers
<IfModule mod_headers.c>
  Header set X-Content-Type-Options "nosniff"
  Header set X-XSS-Protection "1; mode=block"
  Header set X-Frame-Options "SAMEORIGIN"
  Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
EOF

# 10. Update .gitignore file to remove test and debug files
echo -e "${BLUE}Updating .gitignore file...${NC}"

cat >> .gitignore << 'EOF'

# Additional development files to ignore
debug_*.php
test_*.php
test-*.php
*_test.php
*.test.php
/storage/debugbar/
/storage/logs/*.log

# Development documentation
/MYSQL_MIGRATION_GUIDE.md
/DEPLOYMENT_PRODUCTION_GUIDE.md
/PRODUCTION_DEPLOYMENT_GUIDE.md
/MEMBER_REGISTRATION_ANALYSIS.md
/MEMBER_REGISTRATION_RESOLUTION.md
/DATABASE_ENHANCEMENT_REPORT.md

# Keep only essential documentation
!/README.md
!/LICENSE
EOF

# 11. Copy public folder contents to root for cPanel
echo -e "${BLUE}Preparing public directory contents for cPanel...${NC}"

# Create index.php in root that points to public/index.php
cat > index.php << 'EOF'
<?php

/**
 * Parish Management System
 * 
 * cPanel-compatible index.php that works from document root
 */

// Define directory containing this file as the project root
define('LARAVEL_ROOT', __DIR__);

// Check if the public directory contains the original index.php
if (file_exists(LARAVEL_ROOT.'/public/index.php')) {
    // Require the original index.php from the public directory
    require LARAVEL_ROOT.'/public/index.php';
} else {
    die('Could not find the public/index.php file.');
}
EOF

# 12. Register new commands in app/Console/Kernel.php
echo -e "${BLUE}Registering new commands in Kernel.php...${NC}"

# Check if Kernel.php exists
if [ -f "app/Console/Kernel.php" ]; then
    # Add our new commands to the Kernel.php file if not already added
    if ! grep -q "FixDatabaseCompatibility" "app/Console/Kernel.php"; then
        # Find the protected $commands array
        KERNEL_CONTENT=$(cat app/Console/Kernel.php)
        
        if [[ $KERNEL_CONTENT =~ protected\ \$commands\ =\ \[ ]]; then
            # If the file has a $commands array, add our commands
            sed -i '/protected \$commands = \[/a \        \\App\\Console\\Commands\\ConfigureProductionDatabase::class,\n        \\App\\Console\\Commands\\OptimizeMysqlDatabase::class,\n        \\App\\Console\\Commands\\FixDatabaseCompatibility::class,' app/Console/Kernel.php
        else
            # If the file uses the newer $command registration in commands() method
            sed -i '/protected function commands/,/}/s/}/    $this->load(__DIR__."\/Commands");\n    }/' app/Console/Kernel.php
        fi
        
        echo -e "${GREEN}Commands registered in Kernel.php${NC}"
    else
        echo -e "${YELLOW}Commands already registered in Kernel.php${NC}"
    fi
else
    echo -e "${RED}Kernel.php not found! Commands will not be registered.${NC}"
fi

# 13. Final cleanup and checks
echo -e "${BLUE}Performing final checks and cleanup...${NC}"

# Remove temporary files
rm -f test-system.php debug_*.php test_*.php
rm -f MYSQL_MIGRATION_GUIDE.md DEPLOYMENT_PRODUCTION_GUIDE.md PRODUCTION_DEPLOYMENT_GUIDE.md
rm -f MEMBER_REGISTRATION_ANALYSIS.md MEMBER_REGISTRATION_RESOLUTION.md DATABASE_ENHANCEMENT_REPORT.md

echo -e "${GREEN}Temporary files cleaned up.${NC}"

# 14. Summary of what was done
echo -e "${BLUE}===============================================${NC}"
echo -e "${GREEN}Deployment Setup Complete!${NC}"
echo -e "${BLUE}===============================================${NC}"
echo ""
echo -e "${YELLOW}Summary of changes:${NC}"
echo -e "✓ Created cPanel deployment configuration (.cpanel.yml)"
echo -e "✓ Created database compatibility commands"
echo -e "✓ Created MySQL optimization commands"
echo -e "✓ Created database compatibility fixer command"
echo -e "✓ Created optimized .htaccess file"
echo -e "✓ Updated .gitignore to exclude development files"
echo -e "✓ Created cPanel-compatible index.php"
echo -e "✓ Registered new commands in Kernel.php"
echo -e "✓ Cleaned up temporary files"
echo ""
echo -e "${BLUE}Next Steps:${NC}"
echo -e "1. Configure your MySQL database credentials in .env"
echo -e "2. Push your code to your Git repository"
echo -e "3. Deploy using cPanel's Git Version Control feature"
echo -e "4. After deployment, check logs for any issues"
echo ""
echo -e "${BLUE}Deployment log saved to:${NC} $LOG_FILE"
echo ""

exit 0