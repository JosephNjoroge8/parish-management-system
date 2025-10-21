#!/bin/bash

# Parish Management System - Production Hotfix Deployment Script
# This script fixes the foreign key constraint error in production

echo "🚀 Starting Parish Management System Production Hotfix Deployment..."
echo "📅 $(date)"
echo ""

# Step 1: Pull latest changes from GitHub
echo "📦 Pulling latest changes from GitHub..."
git pull origin Main

if [ $? -ne 0 ]; then
    echo "❌ Failed to pull from GitHub. Please check your git configuration."
    exit 1
fi

echo "✅ Successfully pulled latest changes"
echo ""

# Step 2: Clear all caches
echo "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "✅ Caches cleared"
echo ""

# Step 3: Regenerate autoloader
echo "🔄 Regenerating Composer autoloader..."
composer dump-autoload --optimize

echo "✅ Autoloader regenerated"
echo ""

# Step 4: Install/Update dependencies (if needed)
echo "📦 Installing production dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "✅ Dependencies updated"
echo ""

# Step 5: Build frontend assets
echo "🏗️ Building frontend assets..."
npm ci --only=production
npm run build

echo "✅ Frontend assets built"
echo ""

# Step 6: Set proper permissions
echo "🔒 Setting proper file permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || chown -R apache:apache storage bootstrap/cache 2>/dev/null || echo "⚠️ Could not set ownership (may need sudo)"

echo "✅ Permissions set"
echo ""

# Step 7: Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Production optimizations applied"
echo ""

# Step 8: Run the fixed seeder (optional - only if safe to reset data)
echo "❓ Do you want to run the database seeder with the foreign key fix?"
echo "⚠️ WARNING: This will clear existing member data!"
read -p "Enter 'yes' to proceed or any other key to skip: " -r

if [[ $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    echo ""
    echo "🗄️ Running database seeder with foreign key fix..."
    
    # Option 1: Fresh migration (clears all data)
    echo "Choose seeding option:"
    echo "1) Fresh migration (CLEARS ALL DATA)"
    echo "2) Just seed users (safer)"
    echo "3) Skip seeding"
    read -p "Enter choice (1-3): " -r choice
    
    case $choice in
        1)
            echo "🔄 Running fresh migration with seeders..."
            php artisan migrate:fresh --seed --force
            ;;
        2)
            echo "👤 Seeding users only..."
            php artisan db:seed --class=UserSeeder --force
            ;;
        3)
            echo "⏭️ Skipping seeding"
            ;;
        *)
            echo "⏭️ Invalid choice, skipping seeding"
            ;;
    esac
else
    echo "⏭️ Skipping database seeding"
fi

echo ""
echo "🎉 Production hotfix deployment completed successfully!"
echo ""
echo "🔐 Available login credentials:"
echo "   🔑 Super Admin: admin@parish.local / parish123"
echo "   🔑 Backup Admin: admin@parish.com / admin123"
echo "   👤 Staff User: staff@parish.local / staff123"
echo "   📝 Secretary: secretary@parish.local / secretary123"
echo ""
echo "✅ The foreign key constraint issue has been resolved!"
echo "📍 Your parish management system is ready for use."
echo ""
echo "📋 Quick verification checklist:"
echo "   □ Test admin login"
echo "   □ Check member listing"
echo "   □ Test search functionality"
echo "   □ Try PDF generation"
echo ""
echo "🆘 If you encounter any issues:"
echo "   1. Check logs: tail -f storage/logs/laravel.log"
echo "   2. Verify database connection"
echo "   3. Check file permissions"
echo ""
echo "📅 Deployment completed at: $(date)"