# Parish Management System - cPanel Deployment Guide

## Step 1: Prepare Your Repository

1. **Commit and Push All Changes to GitHub:**
```bash
git add .
git commit -m "Ready for cPanel deployment with authentication system"
git push origin main
```

## Step 2: Create Repository Clone in cPanel

1. **Login to cPanel** and navigate to **Git™ Version Control**
2. **Click "Create" to add a new repository**
3. **Repository Settings:**
   - Clone URL: `https://github.com/JosephNjoroge8/parish-management-system.git`
   - Repository Path: `/public_html` (for main domain) or `/public_html/subdomain`
   - Repository Name: `parish-management-system`
4. **Click "Create"**

**Important:** cPanel creates a folder with your repository name. Files will be in `/public_html/parish-management-system/`

## Step 3: Configure Deployment Script

### Option A: PHP Deployment Script (Recommended)

1. **Upload `deploy.php`** to your `public_html` directory
2. **Edit the deployment script:**
```php
// Change this line in deploy.php:
$repo_path = __DIR__ . '/parish-management-system'; // Adjust path if needed
$secret = 'your_secure_random_string_here'; // Change this!
```

3. **Test manual deployment:**
   Visit: `https://yourdomain.com/deploy.php?deploy=your_secure_random_string_here`

### Option B: Bash Script Deployment

1. **Upload `deploy.sh`** to your repository directory
2. **Make it executable:**
```bash
chmod +x deploy.sh
```
3. **Edit paths in the script:**
```bash
REPO_PATH="/home/yourusername/public_html/parish-management-system"
LOG_FILE="/home/yourusername/public_html/deployment.log"
```

## Step 4: Environment Configuration

**Important:** Since you already have an active `.env` file in cPanel, the deployment scripts will preserve it.

1. **Review your existing `.env` file** and ensure it has:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - Correct database credentials
   - Your domain URL in `APP_URL`
   - Mail configuration

2. **Optional: Use `.env.cpanel` as reference** for any missing variables:
   - The `.env.cpanel` file contains recommended production settings
   - Compare with your existing `.env` to identify missing configurations

3. **Verify application key exists:**
```bash
php artisan key:generate --show  # Check if key exists
# Only run this if no key exists:
# php artisan key:generate
```

## Step 5: Database Setup

1. **Create MySQL database** in cPanel
2. **Run initial setup:**
```bash
php artisan migrate --force
php artisan db:seed --force
```

## Step 6: Set Up GitHub Webhook (Automatic Deployment)

1. **Go to your GitHub repository** → Settings → Webhooks
2. **Add webhook:**
   - Payload URL: `https://yourdomain.com/deploy.php`
   - Content type: `application/json`
   - Secret: `your_secure_random_string_here` (same as in deploy.php)
   - Events: Just push events
3. **Click "Add webhook"**

## Step 7: Configure File Permissions

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod 644 .env
```

## Step 8: Point Domain to Laravel Public Directory

### Option A: Create .htaccess in public_html
```apache
RewriteEngine On
RewriteRule ^(.*)$ parish-management-system/public/$1 [L]
```

### Option B: Move files (Advanced)
Move all files from `parish-management-system/` to root and update paths

## Step 9: Production Optimization

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

## Step 10: Security Checklist

- [ ] **Existing `.env` file reviewed** and updated with production settings
- [ ] `APP_DEBUG=false` in your existing .env
- [ ] Strong `APP_KEY` exists in your .env
- [ ] Database credentials in .env are correct for cPanel MySQL
- [ ] `APP_URL` matches your domain in .env
- [ ] Webhook secret is strong and unique in deploy.php
- [ ] SSL certificate installed
- [ ] File permissions: storage (755), .env (644)
- [ ] Error logging configured

## Testing Your Deployment

1. **Visit your domain** - should show Laravel application
2. **Test authentication:**
   - Login: admin@parish.com / admin123
   - Verify role-based access works
3. **Test webhook:**
   - Make a change to your GitHub repository
   - Push the change
   - Check deployment.log for automatic deployment

## Troubleshooting

### Common Issues:

1. **500 Error:**
   - Check file permissions
   - Verify .env configuration
   - Check error logs in cPanel

2. **Database Connection:**
   - Verify MySQL credentials in .env
   - Ensure database exists
   - Check host (usually localhost)

3. **Assets Not Loading:**
   - Run `npm run build` locally and commit
   - Or set up Node.js in cPanel if available

4. **Authentication Issues:**
   - Ensure RolePermissionSeeder ran successfully
   - Check users table has proper data
   - Verify APP_KEY is set

### Log Files to Check:
- `deployment.log` - Deployment process
- `storage/logs/laravel.log` - Application errors
- cPanel Error Logs - Server errors

## Maintenance Commands

```bash
# Update from GitHub
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Clear caches
php artisan cache:clear
php artisan config:cache

# Check system status
php artisan parish:fresh-setup --force  # Use with caution!
```

Your Parish Management System is now ready for production deployment with full authentication and authorization! 🚀