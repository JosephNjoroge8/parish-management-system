#!/bin/bash

# Parish Management System - Deployment Preparation Script
# Run this before deploying to cPanel

echo "🏛️  Parish Management System - Deployment Preparation"
echo "=================================================="

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Not in Laravel project root directory"
    echo "Please run this script from your project root"
    exit 1
fi

echo "✅ Laravel project detected"

# Build production assets
echo "🔨 Building production assets..."
if [ -f "package.json" ]; then
    npm run build
    if [ $? -eq 0 ]; then
        echo "✅ Assets built successfully"
    else
        echo "⚠️  Asset build failed, but continuing..."
    fi
else
    echo "⚠️  No package.json found, skipping asset build"
fi

# Optimize Composer for production
echo "📦 Optimizing Composer for production..."
composer install --no-dev --optimize-autoloader --no-interaction
echo "✅ Composer optimization complete"

# Clear all caches
echo "🧹 Clearing development caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo "✅ Caches cleared"

# Verify authentication system
echo "🔐 Verifying authentication system..."
php artisan db:seed --class=RolePermissionSeeder --force
echo "✅ Authentication system verified"

# Generate deployment files
echo "📋 Generating deployment checklist..."

# Create deployment checklist
cat > DEPLOYMENT_CHECKLIST.md << 'EOF'
# Deployment Checklist

## Pre-Deployment ✅
- [ ] All code committed and pushed to GitHub
- [ ] Production assets built (npm run build)
- [ ] Composer optimized for production
- [ ] Environment variables configured
- [ ] Database credentials obtained from cPanel

## cPanel Setup 🏗️
- [ ] Git repository cloned in cPanel
- [ ] MySQL database created
- [ ] Domain/subdomain configured
- [ ] SSL certificate installed

## File Configuration 📝
- [ ] .env file configured with production settings
- [ ] deploy.php uploaded and configured
- [ ] File permissions set (755 for storage, bootstrap/cache)
- [ ] APP_KEY generated

## Database Setup 🗄️
- [ ] Migrations run (php artisan migrate --force)
- [ ] Seeders run (php artisan db:seed --force)
- [ ] Super admin account created (admin@parish.com)

## Security 🔒
- [ ] APP_DEBUG=false
- [ ] Strong passwords set
- [ ] Webhook secret configured
- [ ] File permissions secured

## Testing 🧪
- [ ] Website loads correctly
- [ ] Login system works
- [ ] Role-based access functional
- [ ] Auto-deployment working

## Admin Credentials 👤
- Super Admin: admin@parish.com / admin123
- Priest: priest@parish.com / priest123
- Secretary: secretary@parish.com / secretary123
- Treasurer: treasurer@parish.com / treasurer123
EOF

# Create .htaccess for cPanel root redirection
cat > .htaccess.cpanel << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Redirect to Laravel public directory
    RewriteRule ^(.*)$ parish-management-system/public/$1 [L]
</IfModule>
EOF

# Create cPanel configuration
cat > .cpanel.yml << 'EOF'
---
deployment:
  tasks:
    - export DEPLOYPATH=/home/$USER/public_html/parish-management-system
    - /bin/cp -R * $DEPLOYPATH/
    - /bin/chmod +x $DEPLOYPATH/deploy.sh
EOF

echo "✅ Deployment files generated"

# Final checks
echo "🔍 Running final checks..."

# Check if .env.example exists and has all required variables
if [ -f ".env.example" ]; then
    echo "✅ Environment example file exists"
else
    echo "⚠️  Creating .env.example from .env.cpanel"
    cp .env.cpanel .env.example
fi

# Verify critical files exist
files_to_check=("deploy.php" "deploy.sh" ".env.cpanel" "DEPLOYMENT_GUIDE.md")
for file in "${files_to_check[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file exists"
    else
        echo "❌ $file missing"
    fi
done

echo ""
echo "🎉 Deployment preparation complete!"
echo ""
echo "📋 Next Steps:"
echo "1. Review DEPLOYMENT_GUIDE.md for detailed instructions"
echo "2. Push all changes to GitHub:"
echo "   git add ."
echo "   git commit -m 'Ready for cPanel deployment'"
echo "   git push origin main"
echo "3. Follow the cPanel setup instructions in DEPLOYMENT_GUIDE.md"
echo ""
echo "🔑 Remember to change the webhook secret in deploy.php!"
echo "🔒 Update database credentials in .env after cPanel setup"
echo ""