#!/bin/bash

# Parish Management System Production Deployment Script
# This script prepares the system for production deployment on cPanel/Hostinger

echo "🏛️ Parish Management System - Production Deployment Script"
echo "=========================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "Laravel application not found. Please run this script from the project root directory."
    exit 1
fi

print_status "Starting production deployment preparation..."

# Step 1: Install dependencies
echo ""
echo "📦 Installing dependencies..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader
    print_status "PHP dependencies installed"
else
    print_warning "Composer not found. Please install dependencies manually: composer install --no-dev --optimize-autoloader"
fi

if command -v npm &> /dev/null; then
    npm install
    print_status "Node.js dependencies installed"
else
    print_warning "NPM not found. Please install dependencies manually: npm install"
fi

# Step 2: Build assets
echo ""
echo "🔧 Building production assets..."
if command -v npm &> /dev/null; then
    npm run build
    print_status "Assets built for production"
else
    print_warning "Cannot build assets. Please run: npm run build"
fi

# Step 3: Set up environment
echo ""
echo "⚙️ Setting up environment..."
if [ ! -f ".env" ]; then
    if [ -f ".env.production" ]; then
        cp .env.production .env
        print_status "Production environment file copied to .env"
    else
        cp .env.example .env
        print_warning "Created .env from example. Please configure database and other settings."
    fi
else
    print_warning ".env file already exists. Please verify production settings."
fi

# Step 4: Generate application key
echo ""
echo "🔐 Generating application key..."
php artisan key:generate --force
print_status "Application key generated"

# Step 5: Cache configuration
echo ""
echo "⚡ Optimizing configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_status "Configuration cached"

# Step 6: Run migrations
echo ""
echo "🗄️ Setting up database..."
print_warning "About to run database migrations..."
read -p "Continue? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    print_status "Database migrations completed"
    
    # Ask about seeding
    read -p "Run production seeder for roles and admin user? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        php artisan db:seed --class=ProductionSeeder
        print_status "Production seeder completed"
        print_warning "Default admin: admin@parish.local / admin123"
        print_error "CHANGE THE DEFAULT PASSWORD IMMEDIATELY!"
    fi
else
    print_warning "Skipped database setup. Run manually: php artisan migrate --force"
fi

# Step 7: Set permissions
echo ""
echo "🔒 Setting file permissions..."
chmod -R 755 storage bootstrap/cache
if [ -d "public" ]; then
    chmod -R 755 public
fi
print_status "File permissions set"

# Step 8: Create symbolic link for storage
echo ""
echo "🔗 Creating storage symlink..."
php artisan storage:link
print_status "Storage symlink created"

# Step 9: Create production files
echo ""
echo "📁 Creating production-specific files..."

# Create .htaccess for public folder
cat > public/.htaccess << 'EOF'
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

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
</IfModule>

# Disable directory browsing
Options -Indexes

# Protect sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
EOF

print_status ".htaccess file created"

# Create robots.txt
cat > public/robots.txt << 'EOF'
User-agent: *
Disallow: /admin
Disallow: /storage
Disallow: /vendor
Allow: /

Sitemap: https://yourdomain.com/sitemap.xml
EOF

print_status "robots.txt created"

# Step 10: Final checks
echo ""
echo "🔍 Final verification..."

# Check critical directories
if [ ! -d "storage/logs" ]; then
    mkdir -p storage/logs
    print_status "Created storage/logs directory"
fi

if [ ! -d "storage/app/public" ]; then
    mkdir -p storage/app/public
    print_status "Created storage/app/public directory"
fi

# Verify .env file
if [ -f ".env" ]; then
    if grep -q "APP_ENV=production" .env; then
        print_status "Environment set to production"
    else
        print_warning "APP_ENV not set to production in .env file"
    fi
    
    if grep -q "APP_DEBUG=false" .env; then
        print_status "Debug mode disabled"
    else
        print_warning "APP_DEBUG should be set to false in production"
    fi
else
    print_error ".env file not found!"
fi

echo ""
echo "🎉 Deployment preparation completed!"
echo "================================================"
echo ""
print_status "Next steps for hosting deployment:"
echo "1. Upload all files to your hosting account"
echo "2. Point your domain to the 'public' folder"
echo "3. Ensure PHP 8.1+ is enabled"
echo "4. Configure your database connection in .env"
echo "5. Run: php artisan migrate --force (on host)"
echo "6. Run: php artisan db:seed --class=ProductionSeeder (on host)"
echo "7. Test the application thoroughly"
echo ""
print_warning "SECURITY REMINDERS:"
echo "- Change default admin password: admin@parish.local / admin123"
echo "- Update APP_URL in .env to your domain"
echo "- Ensure APP_DEBUG=false in production"
echo "- Review and update database credentials"
echo "- Set up SSL/HTTPS for your domain"
echo ""
print_status "Deployment script completed successfully!"