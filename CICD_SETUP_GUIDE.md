# 🚀 Complete CI/CD Pipeline Setup Guide for cPanel Shared Hosting

## 📋 Table of Contents
1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Prerequisites](#prerequisites)
4. [Initial Setup](#initial-setup)
5. [Step-by-Step Configuration](#step-by-step-configuration)
6. [Testing the Pipeline](#testing-the-pipeline)
7. [Troubleshooting](#troubleshooting)
8. [Maintenance](#maintenance)
9. [Security Best Practices](#security-best-practices)
10. [Rollback Procedures](#rollback-procedures)

---

## Overview

This guide will help you set up a fully automated CI/CD pipeline for the Parish Management System using:
- **GitHub Actions** - For automated testing and building
- **GitHub Webhooks** - For deployment triggers
- **cPanel Shared Hosting** - For production hosting (no SSH required)
- **Automated Deployment** - Push to Main branch = automatic deployment

### What This Pipeline Does

1. ✅ **On Push to Main Branch:**
   - Runs automated tests (PHPUnit)
   - Checks code quality (Laravel Pint)
   - Builds production assets (Vite/React)
   - Triggers webhook to production server

2. ✅ **Webhook Receives Request:**
   - Verifies GitHub signature
   - Validates request authenticity
   - Triggers deployment script

3. ✅ **Deployment Script Executes:**
   - Creates backup of current code
   - Enables maintenance mode
   - Pulls latest code from GitHub
   - Installs Composer dependencies
   - Installs NPM dependencies & builds assets
   - Runs database migrations
   - Clears and rebuilds caches
   - Sets correct permissions
   - Disables maintenance mode

4. ✅ **On Failure:**
   - Automatically rolls back to previous backup
   - Sends error notifications
   - Logs detailed error information

---

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Developer Workflow                        │
│                                                               │
│  1. Developer pushes code to Main branch                     │
│                          ↓                                    │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────┴─────────────────────────────────┐
│                     GitHub Actions                           │
│                                                               │
│  2. Run Tests (PHPUnit)                                      │
│  3. Check Code Quality (Pint)                                │
│  4. Build Production Assets (Vite)                           │
│  5. Tests Pass ✅ → Trigger Webhook                          │
│                          ↓                                    │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────┴─────────────────────────────────┐
│                  GitHub Webhook (POST)                       │
│                                                               │
│  6. Send webhook to: https://domain.com/webhook.php         │
│     - Signed with secret                                     │
│     - Contains commit info                                   │
│                          ↓                                    │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────┴─────────────────────────────────┐
│              Production Server (cPanel)                      │
│                                                               │
│  webhook.php:                                                │
│  7. Verify signature ✅                                      │
│  8. Call deploy.php                                          │
│                          ↓                                    │
│  deploy.php:                                                 │
│  9.  Create backup                                           │
│  10. Enable maintenance mode                                 │
│  11. Pull latest code (git)                                  │
│  12. Install dependencies (composer)                         │
│  13. Build assets (npm)                                      │
│  14. Run migrations                                          │
│  15. Optimize Laravel                                        │
│  16. Disable maintenance mode                                │
│  17. Deployment Complete ✅                                  │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

---

## Prerequisites

### Required Access
- ✅ cPanel hosting account
- ✅ GitHub repository access (owner or admin)
- ✅ Domain configured and pointing to hosting
- ✅ Email access (for notifications)

### Server Requirements
- ✅ PHP 8.2 or higher
- ✅ MySQL/MariaDB database
- ✅ Git installed on server
- ✅ Composer support
- ✅ Node.js/NPM support (most cPanel hosts have this)
- ✅ Minimum 512MB memory_limit for PHP
- ✅ `proc_open` function enabled (for git commands)

### GitHub Repository
- ✅ Main branch protection (recommended)
- ✅ Admin access to repository settings
- ✅ Repository is private (recommended for production)

---

## Initial Setup

### Phase 1: Local Preparation (5 minutes)

#### Step 1: Configure Webhook Files

Run the setup helper script to configure your values:

```bash
cd /home/joseph/Desktop/parish-management-system
chmod +x setup-webhook.sh
bash setup-webhook.sh
```

The script will ask for:
- **Webhook Secret**: A strong random key (min 32 characters)
  - Generate one: `openssl rand -hex 32`
- **Deployment Path**: Your cPanel path (e.g., `/home2/username/parish_system`)
- **PHP Version**: Your PHP version (82, 83, 84)
- **Notification Email**: Your admin email
- **Domain**: Your production domain

**OR** manually update these files:

**File 1: `public/webhook.php`**
```php
// Line 24: Update secret
define('WEBHOOK_SECRET', 'YOUR_GENERATED_SECRET_HERE');

// Line 27: Update path
define('DEPLOYMENT_PATH', '/home2/YOUR_USERNAME/parish_system');

// Line 30: Update PHP version
define('PHP_VERSION', '82'); // Your PHP version
```

**File 2: `public/deploy.php`**
```php
// Line 20: Update path
define('DEPLOYMENT_PATH', '/home2/YOUR_USERNAME/parish_system');

// Line 21: Update PHP version
define('PHP_VERSION', '82');

// Line 29: Update secret (MUST match webhook.php)
define('DEPLOY_SECRET', 'YOUR_GENERATED_SECRET_HERE');
```

**File 3: `.cpanel.yml`**
```yaml
# Line 9: Update path
- export DEPLOYPATH=/home2/YOUR_USERNAME/parish_system

# Line 10: Update PHP version
- export PHPVER=82
```

#### Step 2: Commit Configuration

```bash
git add .
git commit -m "Configure CI/CD pipeline for production"
git push origin Main
```

---

### Phase 2: cPanel Setup (10 minutes)

#### Step 1: Create Database

1. Login to cPanel
2. Navigate to **MySQL Databases**
3. Create database: `username_parish`
4. Create user: `username_parish_user`
5. Set strong password
6. Add user to database with ALL PRIVILEGES
7. **Note down**: database name, username, password

#### Step 2: Configure PHP Version

1. Navigate to **Select PHP Version**
2. Select **PHP 8.2** or higher
3. Enable required extensions:
   ```
   ✅ PDO
   ✅ pdo_mysql
   ✅ mbstring
   ✅ openssl
   ✅ tokenizer
   ✅ xml
   ✅ ctype
   ✅ json
   ✅ bcmath
   ✅ fileinfo
   ✅ gd
   ✅ zip
   ```

#### Step 3: Set PHP Limits

In **Select PHP Version** → **Options**, set:
```
memory_limit = 512M
max_execution_time = 300
post_max_size = 50M
upload_max_filesize = 50M
```

#### Step 4: Upload Files

Upload these files to your `public_html` or subdomain directory:
1. `public/webhook.php`
2. `public/deploy.php`

**Via File Manager:**
1. Navigate to **File Manager**
2. Go to your deployment directory (e.g., `parish_system/public/`)
3. Upload `webhook.php` and `deploy.php`
4. Set permissions to **644**

#### Step 5: Clone Repository (First Time Only)

**Via cPanel Git Version Control:**

1. Navigate to **Git™ Version Control**
2. Click **Create**
3. Enter repository details:
   - **Clone URL**: `https://github.com/JosephNjoroge8/parish-management-system.git`
   - **Repository Path**: `/home2/YOUR_USERNAME/repositories/parish-system`
   - **Repository Name**: `parish-system`
4. Click **Create**

**If Git Version Control is not available, use Terminal/SSH alternative:**

```bash
cd /home2/YOUR_USERNAME
mkdir -p parish_system
cd parish_system
git clone -b Main https://github.com/JosephNjoroge8/parish-management-system.git .
```

#### Step 6: Create .env File

1. Copy `.env.production` to `.env`:
   ```bash
   cd /home2/YOUR_USERNAME/parish_system
   cp .env.production .env
   ```

2. Edit `.env` with your actual values:
   ```bash
   APP_NAME="Parish Management System"
   APP_ENV=production
   APP_KEY=   # Will be generated
   APP_DEBUG=false
   APP_URL=https://parish.quovadisyouthhub.org

   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=username_parish
   DB_USERNAME=username_parish_user
   DB_PASSWORD=your_database_password

   # Email settings (use cPanel email)
   MAIL_MAILER=smtp
   MAIL_HOST=mail.quovadisyouthhub.org
   MAIL_PORT=587
   MAIL_USERNAME=noreply@quovadisyouthhub.org
   MAIL_PASSWORD=your_email_password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=noreply@quovadisyouthhub.org
   MAIL_FROM_NAME="${APP_NAME}"
   ```

#### Step 7: Run Initial Setup Commands

Via **Terminal** (if available) or create a temporary PHP script:

```bash
cd /home2/YOUR_USERNAME/parish_system

# Install composer dependencies
/usr/local/bin/ea-php82 /opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader

# Generate application key
/usr/local/bin/ea-php82 artisan key:generate

# Create storage link
/usr/local/bin/ea-php82 artisan storage:link

# Run migrations
/usr/local/bin/ea-php82 artisan migrate --force

# Install npm and build assets
npm install
npm run build

# Cache configurations
/usr/local/bin/ea-php82 artisan config:cache
/usr/local/bin/ea-php82 artisan route:cache
/usr/local/bin/ea-php82 artisan view:cache
```

#### Step 8: Set Permissions

```bash
chmod -R 755 /home2/YOUR_USERNAME/parish_system
chmod -R 775 /home2/YOUR_USERNAME/parish_system/storage
chmod -R 775 /home2/YOUR_USERNAME/parish_system/bootstrap/cache
```

#### Step 9: Configure Domain

**Option A: Main Domain**
- Point your domain root to `parish_system/public`

**Option B: Subdomain**
1. Create subdomain in cPanel: `parish.quovadisyouthhub.org`
2. Set document root to: `/home2/YOUR_USERNAME/parish_system/public`

**Option C: Addon Domain**
1. Add addon domain in cPanel
2. Set document root to: `/home2/YOUR_USERNAME/parish_system/public`

---

### Phase 3: GitHub Webhook Configuration (5 minutes)

#### Step 1: Get Your Webhook Secret

The secret you configured in `webhook.php` (Step 1 of Phase 1)

#### Step 2: Create GitHub Webhook

1. Go to your repository: `https://github.com/JosephNjoroge8/parish-management-system`
2. Click **Settings** → **Webhooks** → **Add webhook**
3. Configure:

   **Payload URL:**
   ```
   https://parish.quovadisyouthhub.org/webhook.php
   ```

   **Content type:**
   ```
   application/json
   ```

   **Secret:**
   ```
   YOUR_WEBHOOK_SECRET_FROM_STEP_1
   ```

   **Which events would you like to trigger this webhook?**
   - Select: **Just the push event**

   **Active:**
   - ✅ Check this box

4. Click **Add webhook**

#### Step 3: Verify Webhook

1. GitHub will send a test ping
2. Check the webhook page for a green ✅ checkmark
3. Click on the webhook → **Recent Deliveries**
4. You should see a successful delivery (200 response)

---

## Step-by-Step Configuration Summary

Here's the complete checklist for setting up CI/CD:

### ✅ Checklist

#### Local Machine
- [ ] Configure `webhook.php` with your settings
- [ ] Configure `deploy.php` with your settings
- [ ] Configure `.cpanel.yml` with your paths
- [ ] Commit and push changes to GitHub

#### cPanel Server
- [ ] Create MySQL database and user
- [ ] Configure PHP version (8.2+)
- [ ] Enable required PHP extensions
- [ ] Set PHP limits (memory, execution time)
- [ ] Upload `webhook.php` to public directory
- [ ] Upload `deploy.php` to public directory
- [ ] Clone GitHub repository
- [ ] Create and configure `.env` file
- [ ] Run initial setup commands
- [ ] Set proper file permissions
- [ ] Configure domain/subdomain

#### GitHub
- [ ] Create webhook in repository settings
- [ ] Set webhook URL to `https://domain.com/webhook.php`
- [ ] Set webhook secret
- [ ] Select "Just the push event"
- [ ] Verify webhook receives ping successfully

---

## Testing the Pipeline

### Test 1: Webhook Connectivity

#### Method 1: Via GitHub
1. Go to Repository → Settings → Webhooks
2. Click on your webhook
3. Scroll to **Recent Deliveries**
4. Click **Redeliver** on the ping event
5. Check response is **200 OK**

#### Method 2: Manual Test
```bash
# On your local machine
curl -X POST https://parish.quovadisyouthhub.org/webhook.php \
  -H "Content-Type: application/json" \
  -H "X-GitHub-Event: ping" \
  -d '{"zen": "test"}'
```

### Test 2: Simple Code Change

1. Make a small change locally:
   ```bash
   echo "// CI/CD test" >> README.md
   git add README.md
   git commit -m "Test CI/CD pipeline"
   git push origin Main
   ```

2. Watch the process:
   - **GitHub Actions**: Check the Actions tab
   - **Webhook**: Check webhook Recent Deliveries
   - **Logs**: Check server logs

3. Monitor deployment logs:
   ```bash
   # Via cPanel File Manager or SSH
   tail -f /home2/YOUR_USERNAME/parish_system/storage/logs/webhook.log
   tail -f /home2/YOUR_USERNAME/parish_system/storage/logs/deployment.log
   ```

### Test 3: Full Feature Deployment

1. Create a new feature branch:
   ```bash
   git checkout -b feature/test-deployment
   ```

2. Make changes to your code

3. Push to feature branch (should NOT deploy):
   ```bash
   git push origin feature/test-deployment
   ```

4. Create Pull Request on GitHub

5. Merge PR to Main (SHOULD deploy):
   - GitHub Actions will run tests
   - On success, webhook triggers deployment
   - Changes appear on production

### Expected Timeline

| Step | Duration | Status Check |
|------|----------|--------------|
| GitHub Actions (Tests) | 2-3 min | Actions tab |
| GitHub Actions (Build) | 3-5 min | Actions tab |
| Webhook Trigger | < 5 sec | Webhook deliveries |
| Git Pull | 10-30 sec | Deployment log |
| Composer Install | 1-2 min | Deployment log |
| NPM Install | 2-3 min | Deployment log |
| Asset Build | 2-4 min | Deployment log |
| Migrations | 5-30 sec | Deployment log |
| Cache Optimization | 10-20 sec | Deployment log |
| **Total** | **8-15 min** | |

---

## Troubleshooting

### Issue 1: Webhook Returns 403 Forbidden

**Symptoms:**
- GitHub webhook shows red X
- Response code: 403
- Error: "Access denied"

**Solutions:**

1. **Check IP restrictions in `.htaccess`**
   ```apache
   # In public/.htaccess, ensure GitHub IPs are allowed
   <Files "webhook.php">
       Allow from 140.82.112.0/20
       Allow from 143.55.64.0/20
       Allow from 185.199.108.0/22
       Allow from 192.30.252.0/22
   </Files>
   ```

2. **Disable ModSecurity temporarily**
   - Add to `.htaccess`:
   ```apache
   <IfModule mod_security.c>
       SecFilterEngine Off
       SecFilterScanPOST Off
   </IfModule>
   ```

3. **Check file permissions**
   ```bash
   chmod 644 public/webhook.php
   ```

### Issue 2: Webhook Returns 500 Internal Server Error

**Symptoms:**
- Webhook delivers but returns 500
- PHP errors in logs

**Solutions:**

1. **Check PHP error logs**
   ```bash
   tail -f /home2/YOUR_USERNAME/parish_system/storage/logs/webhook-errors.log
   ```

2. **Verify PHP functions are enabled**
   - Required: `proc_open`, `exec`, `shell_exec`
   - Check in cPanel → Select PHP Version → PHP Extensions

3. **Check file paths are correct**
   ```php
   // In webhook.php and deploy.php
   define('DEPLOYMENT_PATH', '/home2/YOUR_USERNAME/parish_system');
   ```

4. **Increase PHP memory limit**
   - cPanel → Select PHP Version → Options
   - Set `memory_limit = 512M`

### Issue 3: Deployment Script Fails

**Symptoms:**
- Webhook succeeds but deployment fails
- Site shows old code

**Solutions:**

1. **Check deployment log**
   ```bash
   tail -100 /home2/YOUR_USERNAME/parish_system/storage/logs/deployment.log
   ```

2. **Verify Git repository**
   ```bash
   cd /home2/YOUR_USERNAME/parish_system
   git status
   git remote -v
   ```

3. **Test git manually**
   ```bash
   cd /home2/YOUR_USERNAME/parish_system
   /usr/bin/git fetch origin Main
   /usr/bin/git reset --hard origin/Main
   ```

4. **Check composer installation**
   ```bash
   which composer
   /opt/cpanel/composer/bin/composer --version
   ```

### Issue 4: Assets Not Building

**Symptoms:**
- Deployment succeeds but CSS/JS broken
- Missing `public/build/manifest.json`

**Solutions:**

1. **Check Node.js version**
   ```bash
   node --version  # Should be 18+
   npm --version
   ```

2. **Clear npm cache**
   ```bash
   cd /home2/YOUR_USERNAME/parish_system
   rm -rf node_modules package-lock.json
   npm cache clean --force
   npm install
   npm run build
   ```

3. **Check build output**
   ```bash
   ls -la public/build/
   cat public/build/manifest.json
   ```

4. **Verify Vite config**
   - Ensure `vite.config.js` has correct paths
   - Check `outDir: 'public/build'`

### Issue 5: Database Migrations Fail

**Symptoms:**
- Error: "Connection refused"
- Error: "Access denied"

**Solutions:**

1. **Verify database credentials in `.env`**
   ```bash
   cat .env | grep DB_
   ```

2. **Test database connection**
   ```bash
   /usr/local/bin/ea-php82 artisan tinker
   >>> DB::connection()->getPdo();
   ```

3. **Check database exists**
   - cPanel → MySQL Databases
   - Verify database and user exist

4. **Run migrations manually**
   ```bash
   /usr/local/bin/ea-php82 artisan migrate --force
   ```

### Issue 6: Permissions Errors

**Symptoms:**
- Error: "Permission denied"
- Error: "Unable to write to storage"

**Solutions:**

```bash
cd /home2/YOUR_USERNAME/parish_system

# Fix storage permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache
find storage -type f -exec chmod 664 {} \;
find bootstrap/cache -type f -exec chmod 664 {} \;

# Ensure correct ownership (replace username)
chown -R username:username storage
chown -R username:username bootstrap/cache
```

### Issue 7: GitHub Actions Fail

**Symptoms:**
- Tests fail in GitHub Actions
- Build fails in GitHub Actions

**Solutions:**

1. **Check test database configuration**
   - GitHub Actions uses MySQL service
   - Verify test database credentials in workflow

2. **Update GitHub workflow**
   ```yaml
   # .github/workflows/laravel.yml
   # Ensure MySQL service is running
   # Ensure correct PHP version
   ```

3. **Run tests locally first**
   ```bash
   php artisan test
   ```

4. **Check for missing environment variables**
   - Some tests might need additional env vars
   - Add to GitHub Secrets if needed

### Debug Mode

To enable verbose logging:

**In `webhook.php`:**
```php
// Line 55: Change to
ini_set('display_errors', '1');

// Line 57: Change to
ini_set('error_log', DEPLOYMENT_PATH . '/storage/logs/webhook-errors.log');
```

**In `deploy.php`:**
```php
// Add after configuration section
ini_set('display_errors', '1');
error_reporting(E_ALL);
```

---

## Maintenance

### Daily Tasks

1. **Monitor Deployment Logs**
   ```bash
   tail -f storage/logs/deployment.log
   tail -f storage/logs/webhook.log
   ```

2. **Check Disk Space**
   ```bash
   df -h /home2/YOUR_USERNAME
   ```

3. **Clean Old Logs** (automated)
   - Laravel auto-rotates daily logs
   - Keeps last 14 days by default

### Weekly Tasks

1. **Review Failed Deployments**
   ```bash
   grep "ERROR" storage/logs/deployment.log
   grep "FAILED" storage/logs/deployment.log
   ```

2. **Check Backup Directory**
   ```bash
   ls -lah backups/
   # Should have latest 5 backups
   ```

3. **Verify Cron Jobs** (if any)
   - cPanel → Cron Jobs
   - Ensure Laravel scheduler is running

### Monthly Tasks

1. **Update Dependencies**
   ```bash
   composer update
   npm update
   npm audit fix
   ```

2. **Clean Old Backups Manually**
   ```bash
   find backups/ -name "backup_*.tar.gz" -mtime +30 -delete
   ```

3. **Optimize Database**
   ```bash
   php artisan optimize
   php artisan queue:restart  # If using queues
   ```

4. **Review Security**
   - Update webhook secret
   - Check GitHub webhook IPs
   - Review access logs

### Automated Cleanup Script

Create `maintenance.php` in your deployment directory:

```php
<?php
// Run weekly via cPanel cron
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Clear old logs
Artisan::call('log:clear', ['--keep-last' => 14]);

// Optimize
Artisan::call('optimize:clear');
Artisan::call('optimize');

echo "Maintenance completed\n";
```

Add to cPanel Cron Jobs:
```
0 2 * * 0 /usr/local/bin/ea-php82 /home2/YOUR_USERNAME/parish_system/maintenance.php
```

---

## Security Best Practices

### 1. Webhook Secret

- Use strong random secret (32+ characters)
- Generate with: `openssl rand -hex 32`
- Never commit to Git
- Rotate every 90 days

### 2. File Permissions

```bash
# Application files: 755 for directories, 644 for files
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# Writable directories: 775
chmod -R 775 storage bootstrap/cache

# Executable scripts
chmod 755 artisan
```

### 3. Protect Sensitive Files

Add to `public/.htaccess`:
```apache
<FilesMatch "(\.env|\.git|composer\.(json|lock)|package(-lock)?\.json)">
    Order Deny,Allow
    Deny from all
</FilesMatch>
```

### 4. Environment Variables

- Never commit `.env` file
- Use different secrets for production
- Disable debug mode in production:
  ```
  APP_DEBUG=false
  APP_ENV=production
  ```

### 5. Database Security

- Use strong database passwords
- Restrict database user to localhost
- Grant only necessary privileges
- Regular backups

### 6. SSL/HTTPS

- Always use HTTPS in production
- Force HTTPS in `.htaccess`:
  ```apache
  RewriteCond %{HTTPS} off
  RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
  ```

### 7. Rate Limiting

Add to webhook.php to prevent abuse:
```php
// After signature verification
$rateLimitFile = sys_get_temp_dir() . '/webhook_rate_limit.txt';
$lastRequest = file_exists($rateLimitFile) ? (int)file_get_contents($rateLimitFile) : 0;

if (time() - $lastRequest < 30) {
    sendResponse(false, 'Rate limit exceeded. Wait 30 seconds between deployments.');
}

file_put_contents($rateLimitFile, time());
```

### 8. IP Whitelisting

In `public/.htaccess`:
```apache
# Only allow GitHub webhook IPs
<Files "webhook.php">
    Order Deny,Allow
    Deny from all
    Allow from 140.82.112.0/20
    Allow from 143.55.64.0/20
    Allow from 185.199.108.0/22
    Allow from 192.30.252.0/22
</Files>
```

### 9. Logging

- Monitor all webhook requests
- Log failed authentication attempts
- Review logs weekly for suspicious activity

### 10. Backup Strategy

- Automated backups before each deployment
- Keep last 5 backups
- Test restore procedure monthly
- Store critical backups off-server

---

## Rollback Procedures

### Automatic Rollback

The deployment script automatically rolls back on failure:
1. Detects deployment error
2. Restores from latest backup
3. Clears caches
4. Logs rollback event

### Manual Rollback

#### Option 1: Via Backup Restore

```bash
cd /home2/YOUR_USERNAME/parish_system

# List available backups
ls -lh backups/

# Restore specific backup
tar -xzf backups/backup_20241202120000.tar.gz

# Clear caches
/usr/local/bin/ea-php82 artisan optimize:clear
/usr/local/bin/ea-php82 artisan optimize
```

#### Option 2: Via Git Reset

```bash
cd /home2/YOUR_USERNAME/parish_system

# Find the working commit
git log --oneline -10

# Reset to specific commit
git reset --hard COMMIT_HASH

# Reinstall dependencies
/opt/cpanel/composer/bin/composer install --no-dev
npm ci
npm run build

# Optimize
/usr/local/bin/ea-php82 artisan optimize
```

#### Option 3: Via GitHub Revert

1. Find the problematic commit on GitHub
2. Click **Revert** button
3. Create PR with revert
4. Merge to Main
5. CI/CD will deploy reverted code

### Emergency Rollback (Site Down)

If site is completely broken:

```bash
# 1. Enable maintenance mode manually
touch /home2/YOUR_USERNAME/parish_system/storage/framework/down

# 2. Restore from backup
cd /home2/YOUR_USERNAME/parish_system
tar -xzf backups/backup_LATEST.tar.gz

# 3. Clear all caches
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/*
rm -rf storage/framework/views/*

# 4. Rebuild caches
/usr/local/bin/ea-php82 artisan config:cache
/usr/local/bin/ea-php82 artisan route:cache
/usr/local/bin/ea-php82 artisan view:cache

# 5. Disable maintenance mode
rm /home2/YOUR_USERNAME/parish_system/storage/framework/down
```

### Rollback Checklist

After rollback, verify:
- [ ] Site loads without errors
- [ ] Login works
- [ ] Database connectivity works
- [ ] Assets load correctly (CSS/JS)
- [ ] All critical features work
- [ ] Check error logs are clean

---

## Advanced Configuration

### Email Notifications

Enable in `webhook.php`:
```php
define('SEND_EMAIL_NOTIFICATIONS', true);
define('NOTIFY_EMAIL', 'admin@yourdomain.com');
```

Configure email in `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

### Slack Notifications

Add to `deploy.php` after deployment:
```php
function sendSlackNotification($message) {
    $webhook_url = 'YOUR_SLACK_WEBHOOK_URL';
    
    $data = json_encode(['text' => $message]);
    
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ]);
    
    curl_exec($ch);
    curl_close($ch);
}

// After successful deployment
sendSlackNotification('✅ Parish System deployed successfully!');
```

### Multiple Environments

Create separate webhook/deploy scripts:
- `webhook-staging.php` → Staging server
- `webhook-production.php` → Production server

Configure different GitHub webhooks for each branch:
- `develop` branch → Staging webhook
- `Main` branch → Production webhook

### Custom Deployment Steps

Add to `deploy.php` after migrations:
```php
// Custom: Clear specific cache
$command = "cd " . DEPLOYMENT_PATH . " && " . PHP_BIN . " artisan cache:forget custom_key";
execCommand($command, 30);

// Custom: Run seeder
$command = "cd " . DEPLOYMENT_PATH . " && " . PHP_BIN . " artisan db:seed --class=ProductionSeeder --force";
execCommand($command, 60);

// Custom: Generate sitemap
$command = "cd " . DEPLOYMENT_PATH . " && " . PHP_BIN . " artisan sitemap:generate";
execCommand($command, 60);
```

---

## Performance Optimization

### 1. OPcache Configuration

In cPanel → MultiPHP INI Editor:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  # Production only
opcache.revalidate_freq=0
```

### 2. Laravel Optimization

Add to deployment script:
```bash
# Additional optimizations
/usr/local/bin/ea-php82 artisan optimize
/usr/local/bin/ea-php82 artisan icons:cache  # If using Blade icons
/usr/local/bin/ea-php82 artisan filament:cache-components  # If using Filament
```

### 3. Asset Optimization

In `vite.config.js`:
```javascript
build: {
    minify: 'terser',
    terserOptions: {
        compress: {
            drop_console: true,  // Remove console.logs in production
        },
    },
    rollupOptions: {
        output: {
            manualChunks: {
                vendor: ['react', 'react-dom'],
                inertia: ['@inertiajs/react'],
            },
        },
    },
}
```

### 4. Database Optimization

Monthly task:
```bash
# Optimize tables
/usr/local/bin/ea-php82 artisan db:optimize

# Or via SQL
mysql -u username -p database_name -e "OPTIMIZE TABLE table_name;"
```

---

## Monitoring & Alerts

### 1. Uptime Monitoring

Use services like:
- UptimeRobot (Free tier: 50 monitors)
- Pingdom
- StatusCake

Configure to check:
- Main site: `https://parish.quovadisyouthhub.org`
- Every 5 minutes
- Alert via email/SMS on down

### 2. Error Monitoring

**Laravel Log Viewer:**
```bash
composer require opcodesio/log-viewer --dev
php artisan log-viewer:publish
```

Access at: `https://parish.quovadisyouthhub.org/log-viewer`

**External Services:**
- Sentry (Error tracking)
- Bugsnag
- Rollbar

### 3. Performance Monitoring

**Laravel Pulse:**
```bash
composer require laravel/pulse
php artisan pulse:install
php artisan migrate
```

**Or use external:**
- New Relic
- Scout APM
- Tideways

---

## FAQ

### Q: How long does deployment take?
**A:** Typical deployment: 8-15 minutes total
- GitHub Actions: 5-8 min
- Deployment script: 3-7 min

### Q: Can I deploy manually?
**A:** Yes! Use the cPanel Git Version Control "Pull or Deploy" feature, or run `deploy.php` directly.

### Q: What happens if deployment fails?
**A:** Automatic rollback to previous backup + notification.

### Q: Can I skip tests and deploy directly?
**A:** Not recommended. You can modify `.github/workflows/laravel.yml` to remove test job, but tests prevent broken code from deploying.

### Q: How do I pause deployments temporarily?
**A:** 
1. GitHub → Settings → Webhooks → Edit webhook
2. Uncheck "Active"
3. Save

### Q: Can multiple people push at the same time?
**A:** Deployments are queued. The webhook has a 30-second rate limit to prevent overlapping deployments.

### Q: How do I update the webhook secret?
**A:**
1. Generate new secret: `openssl rand -hex 32`
2. Update `webhook.php` and `deploy.php`
3. Upload new files to server
4. Update GitHub webhook settings

### Q: Does this work with other branches?
**A:** Yes! Configure additional webhooks for different branches (e.g., `develop` → staging server).

### Q: What if Git is not available on my cPanel?
**A:** Contact your hosting provider to enable Git. Most modern cPanel hosts have it. Alternative: Use FTP deployment (not recommended).

### Q: Can I see deployment history?
**A:** Check:
- `storage/logs/deployment.log` - Full deployment logs
- `storage/logs/webhook.log` - Webhook request logs
- GitHub webhook "Recent Deliveries"

---

## Support & Resources

### Documentation
- **Laravel Deployment**: https://laravel.com/docs/deployment
- **GitHub Webhooks**: https://docs.github.com/en/webhooks
- **Inertia.js**: https://inertiajs.com/server-side-setup

### Community
- **Laravel Discord**: https://discord.gg/laravel
- **GitHub Discussions**: Your repository discussions tab

### Logs Location
```
storage/logs/webhook.log          # Webhook requests
storage/logs/webhook-errors.log   # Webhook PHP errors
storage/logs/deployment.log       # Deployment process
storage/logs/laravel.log          # Application errors
```

### Quick Commands Reference

```bash
# Check deployment status
tail -f storage/logs/deployment.log

# Test webhook locally
curl -X POST https://your-domain.com/webhook.php \
  -H "Content-Type: application/json" \
  -H "X-GitHub-Event: push" \
  -d @test-payload.json

# Manual deployment
/usr/local/bin/ea-php82 /path/to/deploy.php

# Rollback to previous backup
cd /home2/username/parish_system
tar -xzf backups/backup_LATEST.tar.gz

# Clear all caches
php artisan optimize:clear
php artisan optimize

# Check Git status
cd /home2/username/parish_system
git status
git log --oneline -5

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# View recent errors
tail -50 storage/logs/laravel.log
```

---

## Conclusion

You now have a fully automated CI/CD pipeline that:
- ✅ Runs tests on every push
- ✅ Builds production assets
- ✅ Deploys automatically to production
- ✅ Handles rollbacks on failure
- ✅ Maintains backups
- ✅ Logs everything for debugging

**Remember:**
- Always test in staging first if possible
- Monitor logs after deployment
- Keep backups of critical data
- Update dependencies regularly
- Review security settings monthly

For issues or questions, check the [Troubleshooting](#troubleshooting) section or contact your system administrator.

---

**Version:** 1.0  
**Last Updated:** December 2, 2025  
**Author:** Parish Management System Team  
**License:** MIT
