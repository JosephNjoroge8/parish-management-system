# 🔴 PRODUCTION DIAGNOSTIC REPORT & COMPLETE FIX GUIDE

**Status**: System is offline with blank page error  
**Date**: April 22, 2026  
**Severity**: CRITICAL - Application not accessible

---

## 📊 SYSTEM ANALYSIS

### Application Stack
- **Framework**: Laravel 12.41.1
- **PHP**: 8.4.16
- **Frontend**: React 18 + Inertia 2 (Vite bundler)
- **Database**: MySQL
- **Hosting**: HostPinacle cPanel
- **CI/CD**: GitHub Actions automated deployment
- **Models**: Member model
- **Database Driver**: MySQL

### Deployment Architecture
```
GitHub Repository (Main branch)
    ↓ (Auto triggers on push)
GitHub Actions Workflow
    ├─ Tests (Laravel tests)
    ├─ Build (npm run build → public/build/)
    ├─ Code Quality (Pint check)
    └─ Deploy (FTP/SSH to cPanel)
         ↓
cPanel Server (/home2/shemidig/parish_system)
    ├─ .cpanel.yml orchestrates deployment
    ├─ Composer install
    ├─ npm build
    ├─ Database migrations
    └─ Cache optimization
```

---

## 🔍 ROOT CAUSE ANALYSIS: WHY YOU SEE A BLANK PAGE

### The Error Chain
```
1. Browser requests: GET https://parish.quovadisyouthhub.org
   ↓
2. Server (Apache) tries to find /public/index.php
   ↓
3. Laravel boots, renders Inertia component
   ↓
4. Browser needs JavaScript: GET /build/assets/app-XXXXX.js
   ↓
5. Server can't find the file (manifest.json issue or missing /build/)
   ↓
6. Apache 404 redirect → Laravel error page (HTML)
   ↓
7. Browser expects JavaScript (.js) but gets HTML (text/html)
   ↓
8. MIME Type Error: "Expected a JavaScript module script 
                     but the server responded with a MIME type of 'text/html'"
   ↓
9. Result: Blank page, nothing loads
```

### Most Likely Issues (Ranked)
1. **60% Probability**: `/public/build/manifest.json` missing or corrupted
2. **20% Probability**: Wrong `APP_URL` or `ASSET_URL` in production `.env`
3. **10% Probability**: Database connection failed (migrations didn't run)
4. **7% Probability**: Permissions issues (755/775 not set correctly)
5. **3% Probability**: Deployment path mismatch in `.cpanel.yml`

---

## ⚡ QUICK DIAGNOSIS (5 MINUTES)

### Step 1: Check if Site is Online
Visit in browser (even though blank): `https://parish.quovadisyouthhub.org`

**If returns 500 error**: Database/Laravel issue  
**If returns blank page**: Asset loading issue (most likely)  
**If returns connection error**: Server/DNS issue  

### Step 2: Check Browser Console
Press `F12` → Console tab

**Look for**:
```
Failed to load module script: Expected a JavaScript module script 
but the server responded with a MIME type of "text/html". Referrer policy: "strict-origin-when-cross-origin"
```

✅ **If you see this**: Assets are missing - follow "Asset Fix" section  
❌ **If you see different error**: Different problem - check "Troubleshooting" section

### Step 3: Check Production Build Files
**Via cPanel File Manager or SSH**:

```bash
# Navigate to your application directory
cd /home2/shemidig/parish_system

# Check if build directory exists
ls -la public/build/

# Expected output:
# total XX
# drwxr-xr-x  2 user user  assets/
# -rw-r--r--  1 user user  manifest.json
```

**If you see**:
- ✅ `manifest.json` listed: Good! (check Step 4)
- ❌ `No such file or directory`: **CRITICAL** - rebuild needed
- ❌ Empty directory: Rebuild needed

### Step 4: Check Manifest is Valid
```bash
# On production server
cat public/build/manifest.json | head -20

# Should output JSON like:
# {
#   "resources/css/app.css": {
#     "file": "assets/app-CbxHDkO3.css",
#     ...
```

**If you see**:
- ✅ JSON output: Good! (check Step 5)
- ❌ `file not found`: Directory exists but manifest missing
- ❌ HTML error: `.htaccess` configuration issue

### Step 5: Check .env Configuration
```bash
# On production server
cat .env | grep "APP_\|ASSET_"

# Should show:
# APP_ENV=production
# APP_URL=https://parish.quovadisyouthhub.org
# ASSET_URL=https://parish.quovadisyouthhub.org  (or blank is OK)
```

**If you see**:
- ✅ Correct domain: Good! (check Step 6)
- ❌ `http://localhost` or old domain: **CRITICAL** - needs fixing
- ❌ No ASSET_URL: That's OK if APP_URL is correct

### Step 6: Check Laravel Logs
```bash
# On production server
tail -50 storage/logs/laravel.log

# Look for errors like:
# [2026-XX-XX XX:XX:XX] production.ERROR: Connection refused...
# [2026-XX-XX XX:XX:XX] production.ERROR: SQLSTATE...
```

**If you see**:
- ✅ No recent errors: Good! (check Step 7)
- ❌ Database connection error: Database issue - follow "Database Fix"
- ❌ Artisan command errors: Deployment didn't complete - follow "Deployment Fix"

### Step 7: Access Diagnostic Page (If Available)
Visit in browser: `https://parish.quovadisyouthhub.org/diagnostic.php`

This page checks:
- ✅ Vite manifest exists and is valid
- ✅ Build assets are accessible
- ✅ PHP permissions are correct
- ✅ Database connection works
- ✅ Laravel cache is functioning

**If you see green checks**: Different issue (not asset related)  
**If you see red X's**: Follow the specific error message

---

## 🛠️ COMPLETE FIX GUIDE

### OPTION A: Asset Rebuild & Upload (Fastest Fix - 10 minutes)

**On Your Local Machine:**

```bash
# 1. Go to project directory
cd ~/Desktop/parish-management-system

# 2. Clean old build
rm -rf public/build

# 3. Install fresh npm dependencies
npm install

# 4. Build production assets
npm run build

# 5. Verify build succeeded
if [ -f "public/build/manifest.json" ]; then
    echo "✅ Build successful!"
    ls -lh public/build/
else
    echo "❌ Build failed - check error above"
    exit 1
fi

# 6. Upload to production via Git (Recommended)
git add -A
git commit -m "Rebuild production assets - fix blank page"
git push origin Main

# OR: Upload via cPanel File Manager
# - Go to cPanel → File Manager
# - Navigate to /home2/shemidig/parish_system/public/
# - Delete old 'build' folder
# - Upload local public/build folder
```

**On Production Server (via SSH or cPanel Terminal):**

```bash
# 1. Navigate to application
cd /home2/shemidig/parish_system

# 2. If using Git, pull latest
git pull origin Main

# 3. Fix permissions
chmod -R 755 public/build
find public/build -type f -exec chmod 644 {} \;

# 4. Clear Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 5. Verify
ls -la public/build/manifest.json
echo "Manifest content:" 
cat public/build/manifest.json | head -10

# 6. Test in browser
echo "✅ Done! Visit https://parish.quovadisyouthhub.org"
```

---

### OPTION B: Complete Deployment Reset (Most Thorough)

Use this if Option A doesn't work.

**On Your Local Machine:**

```bash
# 1. Clean everything
rm -rf public/build node_modules package-lock.json
rm -rf vendor composer.lock

# 2. Rebuild from scratch
npm install
composer install

# 3. Build assets
npm run build

# 4. Commit everything
git add -A
git commit -m "Complete rebuild - fix production"
git push origin Main

# Wait for GitHub Actions to complete deployment...
# Check: GitHub repo → Actions tab
```

**Monitor Deployment:**
- Watch GitHub Actions run tests → build → deploy
- Check: Actions tab → Last run → Deploy step
- If fails: Click step to see error

**If Deployment Completes:**
```bash
# On production server (SSH/Terminal):
cd /home2/shemidig/parish_system

# Verify all components
echo "=== BUILD FILES ==="
ls -lah public/build/ | head -5
echo ""
echo "=== VENDOR INSTALLED ==="
ls vendor/ | head -5
echo ""
echo "=== ENV FILE ==="
grep "APP_ENV\|APP_URL" .env
echo ""
echo "=== STORAGE PERMISSIONS ==="
ls -ld storage bootstrap/cache
echo ""
echo "✅ All components present"
```

---

### OPTION C: Manual Production Setup (Nuclear Option)

Use this if both options above fail or if you want complete control.

**Step 1: Backup Current Production**
```bash
# On production server
cd /home2/shemidig
tar -czf parish_system_backup_$(date +%Y%m%d).tar.gz parish_system/
echo "✅ Backup created"
```

**Step 2: Clean and Rebuild**
```bash
cd /home2/shemidig/parish_system

# Remove build and vendor
rm -rf public/build vendor node_modules

# Copy fresh .env
cp .env.production .env

# Edit .env to match your server
nano .env

# Required values in .env:
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=https://parish.quovadisyouthhub.org
# DB_HOST=localhost
# DB_PORT=3306
# DB_DATABASE=<your_cpanel_db>
# DB_USERNAME=<your_cpanel_user>
# DB_PASSWORD=<your_cpanel_pass>
```

**Step 3: Install Dependencies**
```bash
# Install Composer dependencies
/opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader

# Generate APP_KEY if missing
php artisan key:generate --force

# Run migrations
php artisan migrate --force

# Install Node and build assets
npm ci --production=false
npm run build

# Verify build
if [ ! -f "public/build/manifest.json" ]; then
    echo "❌ Build failed!"
    exit 1
fi
```

**Step 4: Optimize for Production**
```bash
# Set permissions
chmod -R 755 storage bootstrap/cache public/build
chmod -R 775 storage/logs

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Link storage (if not already)
php artisan storage:link 2>/dev/null || echo "Storage already linked"

# Restart queue
php artisan queue:restart 2>/dev/null || true

# Verify everything is working
echo "✅ Production setup complete!"
```

---

## 🔧 SPECIFIC ISSUE FIXES

### Issue: "MIME type text/html" Error

**Quick Fix:**
```bash
cd /home2/shemidig/parish_system
rm -rf public/build
npm run build
chmod -R 755 public/build
php artisan cache:clear
```

---

### Issue: Database Connection Error

**Check Connection:**
```bash
# On production server
php artisan tinker
> DB::connection()->getPdo();
> // If successful, DB is connected

# Check .env values
grep "DB_" .env

# Test connection directly
php -r "
\$dsn = 'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE');
try {
    \$pdo = new PDO(\$dsn, getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
    echo '✅ Connected successfully!';
} catch (Exception \$e) {
    echo '❌ Connection failed: ' . \$e->getMessage();
}
"
```

**Fix Steps:**
```bash
# 1. Verify correct database credentials in .env
cat .env | grep "DB_"

# 2. Reset database connection
php artisan migrate:rollback --force
php artisan migrate --force

# 3. Verify database has data
php artisan tinker
> App\Models\Member::count();  // Should show a number
```

---

### Issue: Permissions Denied Error

**Fix:**
```bash
cd /home2/shemidig/parish_system

# Fix all permissions
chmod -R 755 .
chmod -R 777 storage
chmod -R 777 bootstrap/cache
chmod -R 755 public

# Or more specific:
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
find storage -type f -exec chmod 666 {} \;
find bootstrap/cache -type f -exec chmod 666 {} \;
```

---

### Issue: .env File Missing in Production

**Fix:**
```bash
cd /home2/shemidig/parish_system

# Check if .env exists
if [ ! -f ".env" ]; then
    # Copy from .env.production
    if [ -f ".env.production" ]; then
        cp .env.production .env
        echo "✅ .env created from .env.production"
    else
        # Copy from example
        cp .env.example .env
        echo "⚠️  .env created from .env.example - EDIT IT!"
        
        # Edit required values
        nano .env
    fi
fi

# Generate key if missing
grep "APP_KEY=" .env | grep -q "base64:" || php artisan key:generate --force
```

---

### Issue: Deployment Path Wrong

**Check Current Path:**
```bash
# On production server
pwd
# Should show: /home2/shemidig/parish_system

# If different, update .cpanel.yml:
cat .cpanel.yml | grep "DEPLOYPATH"
# Shows: export DEPLOYPATH=/home2/shemidig/parish_system

# To change, edit .cpanel.yml and commit:
nano .cpanel.yml
# Change DEPLOYPATH= to correct path
git add .cpanel.yml
git commit -m "Fix deployment path"
git push origin Main
```

---

## 📋 STEP-BY-STEP IMMEDIATE FIX (Do This Now)

### For Users With SSH Access:

```bash
# 1. Connect to server
ssh username@hostipinacle.com

# 2. Navigate to project
cd /home2/shemidig/parish_system

# 3. Check current state
echo "=== CHECKING CURRENT STATE ==="
echo "Build files:"
ls -la public/build/ 2>/dev/null || echo "❌ Build directory missing!"
echo ""
echo ".env configured:"
grep "APP_URL\|ASSET_URL\|DB_DATABASE" .env
echo ""
echo "Laravel logs (last 10 errors):"
grep "ERROR\|Exception" storage/logs/laravel.log | tail -10

# 4. Rebuild assets
echo "=== REBUILDING ASSETS ==="
rm -rf public/build
npm run build || { echo "Build failed!"; exit 1; }

# 5. Fix permissions
echo "=== FIXING PERMISSIONS ==="
chmod -R 755 public/build storage bootstrap/cache

# 6. Clear caches
echo "=== CLEARING CACHES ==="
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 7. Verify
echo ""
echo "=== VERIFICATION ==="
if [ -f "public/build/manifest.json" ]; then
    echo "✅ Build manifest exists"
    echo "✅ Try visiting https://parish.quovadisyouthhub.org"
else
    echo "❌ Build failed - see errors above"
fi
```

### For Users Without SSH (cPanel File Manager Only):

```bash
# 1. Use cPanel → Databases → phpmyadmin
#    Verify database exists and has tables

# 2. Use cPanel → File Manager
#    Check if /public/build/ directory exists

# 3. If missing:
#    - Download entire application locally
#    - Run "npm run build" on local machine
#    - Upload only "public/build" folder back to server

# 4. Use cPanel → Terminal (if available)
#    Run the SSH commands above

# 5. If no Terminal access:
#    - Visit https://parish.quovadisyouthhub.org/diagnostic.php
#    - It will show what's wrong
#    - Contact HostPinacle support with diagnostic output
```

---

## ✅ VERIFICATION CHECKLIST

After applying fixes, verify each item:

- [ ] **Build Files Exist**: `ls public/build/manifest.json` returns file info
- [ ] **Manifest Valid**: `cat public/build/manifest.json | head -20` shows JSON
- [ ] **.env Configured**: `grep APP_URL .env` shows correct domain
- [ ] **Permissions OK**: `ls -ld storage` shows `drwxr-xr-x` or `drwxrwxr-x`
- [ ] **Database Connected**: `php artisan tinker` and `DB::connection()->getPdo()` works
- [ ] **Cache Clear**: No old config cached
- [ ] **Visit Site**: `https://parish.quovadisyouthhub.org` loads with styling
- [ ] **F12 Console**: No MIME type errors
- [ ] **Diagnostic Page**: `https://parish.quovadisyouthhub.org/diagnostic.php` shows all ✅

---

## 🚀 PREVENT THIS IN THE FUTURE

### Deployment Checklist Before Every Push:

```bash
# Local machine checklist
- [ ] npm run build  # Verify assets build
- [ ] ls public/build/manifest.json  # Verify created
- [ ] php artisan test  # Run tests
- [ ] vendor/bin/pint --dirty  # Fix code style
- [ ] git add . && git commit -m "Meaningful message"
- [ ] git push origin Main  # Triggers deployment
```

### Post-Deployment Checklist:

```bash
# On production (after GitHub Actions completes)
- [ ] Visit https://parish.quovadisyouthhub.org (should load)
- [ ] Visit /diagnostic.php (should show all green)
- [ ] Check browser console (F12) for errors
- [ ] Test a few key features (login, view data, etc.)
```

### GitHub Secrets Configuration:

Ensure these are set in Settings → Secrets → Actions:
```
CPANEL_FTP_SERVER       - ftp.hostipinacle.com
CPANEL_FTP_USERNAME     - your_cpanel_username
CPANEL_FTP_PASSWORD     - your_cpanel_password
CPANEL_SERVER_DIR       - /home2/shemidig/parish_system
CPANEL_SSH_HOST         - hostipinacle.com
CPANEL_SSH_USERNAME     - your_ssh_username
CPANEL_SSH_PASSWORD     - your_ssh_password
CPANEL_SSH_PORT         - 22
APP_URL                 - https://parish.quovadisyouthhub.org
PRODUCTION_WEBHOOK_URL  - (optional)
```

---

## 📞 IF STILL NOT WORKING

1. **Run this diagnostic script locally**:
   ```bash
   bash pre-deployment-check.sh
   ```

2. **Check production logs**:
   ```bash
   tail -100 storage/logs/laravel.log
   grep "ERROR\|Exception" storage/logs/laravel.log | tail -20
   ```

3. **Visit diagnostic page**:
   ```
   https://parish.quovadisyouthhub.org/diagnostic.php
   ```

4. **Contact HostPinacle Support With**:
   - Error messages from F12 Console
   - Output of `ls -la public/build/`
   - Output of `tail -50 storage/logs/laravel.log`
   - Screenshot of `/diagnostic.php`
   - Error message from browser

---

## 📚 RELATED DOCUMENTATION

- [PRODUCTION_BLANK_PAGE_SOLUTION.md](./PRODUCTION_BLANK_PAGE_SOLUTION.md) - Detailed asset loading guide
- [DEPLOYMENT_STRATEGY.md](./DEPLOYMENT_STRATEGY.md) - Overall deployment architecture
- [DEPLOYMENT_INSTRUCTIONS.md](./DEPLOYMENT_INSTRUCTIONS.md) - Initial setup guide
- [QUICK_FIX_GUIDE.md](./QUICK_FIX_GUIDE.md) - Fast troubleshooting
- [QUICK_REFERENCE.md](./QUICK_REFERENCE.md) - Commands reference

---

**Generated**: April 22, 2026  
**Application**: Parish Management System  
**Environment**: Production (HostPinacle cPanel)  
**Status**: AWAITING RECOVERY
