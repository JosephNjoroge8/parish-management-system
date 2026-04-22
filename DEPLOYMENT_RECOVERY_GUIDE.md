# 📋 PARISH SYSTEM - STEP-BY-STEP DEPLOYMENT & RECOVERY GUIDE

**For**: HostPinacle cPanel Automated GitHub Deployment  
**Last Updated**: April 22, 2026  
**Status**: Production Recovery Mode

---

## 🎯 WHAT YOU NEED TO DO RIGHT NOW

### Scenario 1: You Have SSH/Terminal Access

**Time Required**: 15 minutes  
**Difficulty**: Easy

```bash
#!/bin/bash
set -e

echo "🚀 Parish System Recovery Script"
echo "=================================="
echo ""

# 1. Connect to your server via SSH first:
# ssh username@hostipinacle.com

# 2. Then run these commands:

cd /home2/shemidig/parish_system

echo "📊 Step 1: Checking current state..."
echo "  Directory: $(pwd)"
echo "  PHP: $(php -v | head -1)"
echo ""

echo "🔍 Step 2: Checking build files..."
if [ -d "public/build" ]; then
    FILE_COUNT=$(find public/build -type f | wc -l)
    echo "  ✅ Build directory found ($FILE_COUNT files)"
else
    echo "  ❌ Build directory MISSING - will rebuild"
fi
echo ""

echo "🛠️  Step 3: Rebuilding frontend assets..."
rm -rf public/build
npm install
npm run build

echo "  ✅ Assets built"
echo ""

echo "🔐 Step 4: Fixing permissions..."
chmod -R 755 public/build storage bootstrap/cache
echo "  ✅ Permissions fixed"
echo ""

echo "🧹 Step 5: Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "  ✅ Caches cleared"
echo ""

echo "✅ Step 6: Verification..."
if [ -f "public/build/manifest.json" ]; then
    echo "  ✅ Manifest exists"
    ASSET_COUNT=$(find public/build/assets -type f 2>/dev/null | wc -l)
    echo "  ✅ Found $ASSET_COUNT asset files"
    echo ""
    echo "🎉 RECOVERY COMPLETE!"
    echo ""
    echo "🌐 Your site should now be online:"
    echo "   https://parish.quovadisyouthhub.org"
else
    echo "  ❌ Build failed - check errors above"
    exit 1
fi
```

**Save this as a file and run it**:
```bash
# Save as: recovery.sh
vim recovery.sh

# Make executable
chmod +x recovery.sh

# Run it
./recovery.sh
```

---

### Scenario 2: No SSH Access (cPanel File Manager Only)

**Time Required**: 20 minutes  
**Difficulty**: Medium

#### Step 1: Rebuild Assets Locally

On your computer (Windows/Mac/Linux):

```bash
# 1. Navigate to project directory
cd ~/Desktop/parish-management-system

# 2. Clean old build
rm -rf public/build

# 3. Install dependencies
npm install

# 4. Build for production
npm run build

# 5. Verify it worked
ls -la public/build/

# Expected output: manifest.json and assets/ folder
```

#### Step 2: Upload to Production

**Method A: Via Git (Recommended)**
```bash
# 1. From local machine:
git add public/build
git commit -m "Rebuild production assets - fix blank page"
git push origin Main

# 2. Wait for GitHub Actions to deploy automatically (~5 minutes)

# 3. Check deployment status:
#    GitHub → Your repo → Actions tab → Click latest run
#    Wait for all steps to show ✅
```

**Method B: Via cPanel File Manager (If Git doesn't work)**
```
1. Log into cPanel
2. Open File Manager
3. Navigate to: /home2/shemidig/parish_system/public/
4. Right-click 'build' folder → Delete
5. Upload your local 'public/build' folder
6. Right-click upload → Extract (if zipped)
7. Wait for upload to complete
```

#### Step 3: Clear Production Caches

If you have cPanel Terminal access:
```bash
cd /home2/shemidig/parish_system
php artisan config:clear
php artisan cache:clear
```

If no Terminal, wait 24 hours for automatic cache expiration.

#### Step 4: Test

```
Visit: https://parish.quovadisyouthhub.org
Should show the site with full styling and colors
```

---

## 🔧 ADVANCED TROUBLESHOOTING

### Problem: Build Fails with "npm: command not found"

**On cPanel Server**:
```bash
# Check if npm is available
which npm
npm --version

# If not found, try with node directly
node --version
node -e "console.log(require('npm'))"

# Or use the ea-npm command (EasyApache)
/usr/local/bin/npm --version
/usr/local/bin/npm run build
```

**If npm is really missing**:
- Contact HostPinacle support to enable Node.js
- Or use GitHub Actions to build locally, commit assets

---

### Problem: Permission Denied When Uploading

**Possible Causes**:
- public/ directory permissions too restrictive
- Owner is different user

**Fix**:
```bash
# On server (SSH):
cd /home2/shemidig/parish_system

# Fix ownership
chown -R yourusername:yourusername public/

# Fix permissions
chmod 755 public/
chmod -R 755 public/build/
```

---

### Problem: "Failed to load module script" Still Shows

**Verify assets are actually there**:
```bash
# On server:
curl -I https://parish.quovadisyouthhub.org/build/manifest.json

# Should return:
# HTTP/2 200
# Content-Type: application/json

# NOT:
# HTTP/2 404
# Content-Type: text/html
```

**If returns 404, assets are missing**:
- Rebuild using instructions above
- Verify upload completed
- Check file permissions

**If returns 200 but page still blank**:
- Different issue (not assets)
- Check Laravel logs: `tail -50 storage/logs/laravel.log`
- Look for database/auth errors

---

### Problem: "Database connection refused"

**Check credentials**:
```bash
# On server:
grep "DB_" .env

# Should show valid values:
# DB_HOST=localhost
# DB_PORT=3306
# DB_DATABASE=shemidig_parish_db
# DB_USERNAME=shemidig_parishuser
# DB_PASSWORD=your_password
```

**Test connection**:
```bash
php -r "
\$dsn = 'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE');
try {
    \$pdo = new PDO(\$dsn, getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
    echo '✅ Database connected!';
} catch (Exception \$e) {
    echo '❌ Connection failed: ' . \$e->getMessage();
}
"
```

**If connection fails**:
- Verify credentials in cPanel → MySQL Databases
- Ask HostPinacle support to verify MySQL is running
- Check if database/user actually exists

---

### Problem: Deployment Keeps Failing

**Check GitHub Actions**:
1. Go to your GitHub repository
2. Click "Actions" tab
3. Click latest run
4. Scroll down to see which step failed
5. Click that step to see the error message

**Common failures**:
- **Composer install fails**: Missing PHP extensions (contact hosting support)
- **npm build fails**: Missing Node.js or disk space
- **Migrations fail**: Database issues
- **FTP/SSH fails**: Wrong credentials in GitHub Secrets

**Fix**:
```bash
# Verify GitHub Secrets are set:
# GitHub → Settings → Secrets and Variables → Actions

Required secrets:
- CPANEL_FTP_SERVER = ftp.hostipinacle.com
- CPANEL_FTP_USERNAME = your_cpanel_user
- CPANEL_FTP_PASSWORD = your_cpanel_password
- CPANEL_SERVER_DIR = /home2/shemidig/parish_system
- CPANEL_SSH_HOST = your_server_ip_or_hostname
- CPANEL_SSH_USERNAME = your_ssh_user
- CPANEL_SSH_PASSWORD = your_ssh_password
```

**If secrets are wrong**:
1. Update them in GitHub
2. Make a small change to code
3. Commit and push to trigger new deployment

---

## 📊 VERIFICATION TESTS

### Test 1: Build Files Exist

```bash
# On server:
cd /home2/shemidig/parish_system

# Check directory
ls -la public/build/

# Should show:
# drwxr-xr-x  2 user user 4096 Apr 22 10:30 assets
# -rw-r--r--  1 user user 2048 Apr 22 10:30 manifest.json
```

---

### Test 2: Manifest is Valid JSON

```bash
# On server:
cd /home2/shemidig/parish_system

# Validate JSON
php -r "
\$json = json_decode(file_get_contents('public/build/manifest.json'), true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo '✅ Valid JSON' . PHP_EOL;
    echo 'Entries: ' . count(\$json) . PHP_EOL;
    foreach (array_slice(\$json, 0, 3) as \$key => \$val) {
        echo '  ' . \$key . ' → ' . \$val['file'] . PHP_EOL;
    }
} else {
    echo '❌ Invalid JSON: ' . json_last_error_msg();
}
"
```

---

### Test 3: Browser Can Access Assets

**In your browser**:
```
1. Visit: https://parish.quovadisyouthhub.org/build/manifest.json
   Should show JSON, NOT an error page

2. Visit: https://parish.quovadisyouthhub.org/diagnostic.php
   Should show system diagnostics with checkmarks
```

---

### Test 4: No JavaScript Errors

**In your browser (F12)**:
```
1. Press F12
2. Click "Console" tab
3. Should NOT show:
   "Failed to load module script: Expected a JavaScript module script..."

4. If shows error, check Network tab:
   - Look for red entries (failed downloads)
   - Check their Response (should be JS content, not HTML)
```

---

### Test 5: Database is Working

```bash
# On server:
php artisan tinker

# In Tinker shell:
> DB::table('members')->count();
# Should return a number (or 0 if empty)

> exit;
```

---

## 🔄 COMPLETE RECOVERY WORKFLOW

**Use this if everything above fails**:

### Phase 1: Backup (5 min)
```bash
# On server:
cd /home2/shemidig
tar -czf parish_backup_$(date +%Y%m%d_%H%M%S).tar.gz parish_system/
ls -lh parish_backup*.tar.gz
```

### Phase 2: Full Clean Install (10 min)
```bash
# On server:
cd /home2/shemidig/parish_system

# Remove build artifacts
rm -rf public/build vendor node_modules

# Copy production config
cp .env.production .env

# Install Composer (production only)
/opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader

# Generate/reset app key
php artisan key:generate --force

# Clear all caches
php artisan cache:clear --all
php artisan config:cache
php artisan route:cache
```

### Phase 3: Database (5 min)
```bash
# On server:
cd /home2/shemidig/parish_system

# Run migrations
php artisan migrate --force

# Seed if needed
# php artisan db:seed --force
```

### Phase 4: Frontend (5 min)
```bash
# On server:
cd /home2/shemidig/parish_system

# Install Node dependencies
npm ci

# Build for production
npm run build

# Verify
ls -la public/build/manifest.json
```

### Phase 5: Optimize (3 min)
```bash
# On server:
cd /home2/shemidig/parish_system

# Fix all permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 777 storage bootstrap/cache

# Link storage
php artisan storage:link 2>/dev/null || true

# Restart queue
php artisan queue:restart 2>/dev/null || true

# Bring online
php artisan up
```

### Phase 6: Test (2 min)
```bash
# In browser:
https://parish.quovadisyouthhub.org
# Should load normally

https://parish.quovadisyouthhub.org/diagnostic.php
# Should show all green
```

---

## 🚀 GOING FORWARD: PROPER DEPLOYMENT PROCESS

To prevent this issue, always follow this before pushing code:

### Local Development Checklist

```bash
#!/bin/bash

echo "Pre-deployment checklist..."
echo ""

echo "1️⃣  Build frontend assets..."
npm run build || { echo "Build failed!"; exit 1; }
echo "✅ Build complete"
echo ""

echo "2️⃣  Verify build files..."
if [ -f "public/build/manifest.json" ]; then
    echo "✅ Manifest exists"
else
    echo "❌ Manifest missing!"; 
    exit 1; 
fi
echo ""

echo "3️⃣  Run tests..."
php artisan test || { echo "Tests failed!"; exit 1; }
echo "✅ Tests pass"
echo ""

echo "4️⃣  Format code..."
vendor/bin/pint --dirty || true
echo "✅ Code formatted"
echo ""

echo "5️⃣  Ready to deploy..."
echo "git add -A"
echo "git commit -m 'Update: [your message]'"
echo "git push origin Main"
echo ""
echo "✅ All checks complete! Ready to push."
```

**Save as** `pre-deploy.sh` and run before every deployment:
```bash
chmod +x pre-deploy.sh
./pre-deploy.sh
```

---

### Post-Deployment Verification

After GitHub Actions completes:

```bash
echo "Post-deployment verification..."
echo ""

# 1. Wait for deployment to complete
echo "⏳ Waiting for deployment... (check GitHub Actions)"
echo "   Go to: GitHub → Actions → Latest Run"
echo ""

# 2. When complete, test site
echo "Testing site..."
SITE_URL="https://parish.quovadisyouthhub.org"

# Check if site loads
HTTP_CODE=$(curl -o /dev/null -s -w "%{http_code}" -L "$SITE_URL")
if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ Site is online (HTTP $HTTP_CODE)"
else
    echo "⚠️  Site returned HTTP $HTTP_CODE"
fi
echo ""

# 3. Test diagnostic page
echo "Checking diagnostic page..."
curl -s "$SITE_URL/diagnostic.php" | grep -q "✅" && echo "✅ Diagnostic page works" || echo "⚠️  Check diagnostic page"
echo ""

echo "✅ Deployment verification complete!"
```

---

## 🆘 QUICK REFERENCE COMMANDS

### Essential Commands

```bash
# Clear all caches
php artisan cache:clear --all
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Database operations
php artisan migrate --force          # Run migrations
php artisan migrate:rollback         # Rollback
php artisan db:seed                  # Seed database

# Asset management
npm run build                        # Build frontend
npm run dev                          # Dev mode

# Permissions
chmod -R 755 storage bootstrap/cache
chmod -R 777 storage/logs

# Diagnostics
php artisan tinker                   # Interactive PHP shell
tail -50 storage/logs/laravel.log    # View errors
php artisan serve                    # Local testing
```

---

## 📞 SUPPORT CONTACTS

### If You're Still Stuck

1. **Check Server Logs**:
   ```bash
   tail -100 storage/logs/laravel.log
   ```

2. **Check Browser Console** (F12):
   - Look for error messages
   - Screenshot and save

3. **Run Diagnostic**:
   ```
   https://parish.quovadisyouthhub.org/diagnostic.php
   ```

4. **Contact HostPinacle Support With**:
   - Error messages from above
   - Your deployment path: `/home2/shemidig/parish_system`
   - Ask them to verify:
     - PHP version (should be 8.4+)
     - Node.js is installed
     - MySQL is running
     - File permissions allow writing to storage/

5. **Contact GitHub Support** (if deployment fails):
   - Error from GitHub Actions
   - Screenshot of the failed step

---

**Last Updated**: April 22, 2026  
**Status**: Production Recovery Guide  
**Prepared For**: Parish Management System @ HostPinacle cPanel
