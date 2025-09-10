#!/bin/bash
# Production Database Connection Fix
# Upload this file to your production server and run it

echo "🔧 Fixing Parish System Database Connection..."

# Navigate to project directory
cd /home2/shemidig/parish_system

# Backup current .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Fix database credentials in .env file
sed -i 's/DB_HOST=.*/DB_HOST=localhost/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=shemidig_NjoroParish/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=Nj0r0g3@345/' .env
sed -i 's/DB_DATABASE=.*/DB_DATABASE=shemidig_parish_system/' .env

# Clear all Laravel caches
echo "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Test database connection
echo "🔍 Testing database connection..."
php artisan tinker --execute="
try {
    \$pdo = DB::connection()->getPdo();
    echo 'Database connection: SUCCESS\n';
    echo 'Connected to: ' . DB::connection()->getDatabaseName() . '\n';
} catch (\Exception \$e) {
    echo 'Database connection: FAILED\n';
    echo 'Error: ' . \$e->getMessage() . '\n';
}"

# Create sessions table if it doesn't exist
echo "📋 Ensuring sessions table exists..."
php artisan session:table
php artisan migrate --force

# Optimize for production
echo "⚡ Optimizing for production..."
php artisan optimize

echo "✅ Database connection fix completed!"
echo "🌐 Try accessing: https://parish.quovadisyouthhub.org"
