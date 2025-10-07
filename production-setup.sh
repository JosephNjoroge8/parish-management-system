#!/bin/bash

# Production Setup Script for Parish Management System
# Run this script AFTER uploading files to cPanel

echo "🚀 Starting Production Setup for Parish Management System..."

# Set proper permissions
echo "📁 Setting file permissions..."
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache
chmod 644 .env

# Clear all caches
echo "🧹 Clearing application caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Generate application key if not set
echo "🔑 Generating application key..."
php artisan key:generate --force

# Run database migrations
echo "💾 Running database migrations..."
php artisan migrate --force

# Create super admin user
echo "👑 Creating super admin user..."
php artisan make:super-admin --email=admin@parish.com --password=Admin123! --name="System Administrator"

# Seed essential data if needed
echo "🌱 Seeding essential data..."
php artisan db:seed --class=UserSeeder --force

# Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create symbolic link for storage (if not exists)
echo "🔗 Creating storage symbolic link..."
php artisan storage:link

echo "✅ Production setup completed successfully!"
echo ""
echo "📋 Next steps:"
echo "1. Verify database connection in .env file"
echo "2. Update APP_URL in .env to your domain"
echo "3. Test the application at your domain"
echo "4. Login with: admin@parish.com / Admin123!"
echo ""
echo "🔒 Security Reminders:"
echo "- Change default admin password after first login"
echo "- Review .env file for proper production settings"
echo "- Ensure database credentials are correct"
echo "- Verify file permissions are set correctly"