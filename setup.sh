#!/bin/bash

# Parish Management System - Quick Setup Script
# Run this after deploying to cPanel

echo "🚀 Setting up Parish Management System..."

# Navigate to deployment directory
cd /home/joseph/public_html/

# Set permissions
echo "🔒 Setting file permissions..."
chmod -R 755 .
chmod -R 777 storage bootstrap/cache

# Copy environment file
echo "⚙️ Setting up environment..."
if [ ! -f .env ]; then
    if [ -f .env.production ]; then
        cp .env.production .env
        echo "✅ Environment file created from production template"
    else
        echo "❌ No .env.production found! Create manually."
        exit 1
    fi
fi

# Generate application key
echo "🔑 Generating application key..."
php artisan key:generate --force

# Run database migrations
echo "📊 Setting up database..."
php artisan migrate --force

# Seed the database with admin user
echo "👤 Creating admin user..."
php artisan db:seed --force

# Create storage link
echo "📁 Creating storage link..."
php artisan storage:link

# Cache configurations for performance
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Final permissions check
chmod 600 .env
chmod -R 777 storage bootstrap/cache

echo "🎉 Setup complete!"
echo ""
echo "Default Admin Credentials:"
echo "Email: admin@parish.com"
echo "Password: password"
echo ""
echo "⚠️ Remember to:"
echo "1. Change the admin password"
echo "2. Update .env with your domain"
echo "3. Test the authentication flow"
echo ""
echo "🌐 Your Parish Management System is ready!"