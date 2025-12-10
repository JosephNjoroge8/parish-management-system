# 🚀 Production Deployment - Complete Guide

## 🔴 CRITICAL: Understanding the Blank Page Error

### The Problem
You're seeing a blank page with this browser console error:
```
Failed to load module script: Expected a JavaScript module script 
but the server responded with a MIME type of "text/html"
```

### What This Means
1. **Your browser requests**: `/build/assets/app-XXXXXXXX.js`
2. **Server can't find it**: File doesn't exist or has wrong hash
3. **Apache routes to Laravel**: Via `.htaccess` fallback
4. **Laravel returns HTML**: 404 error page in HTML format
5. **Browser expects JavaScript**: Gets HTML instead → MIME type error
6. **Result**: Blank page because no JavaScript loads

---

## ✅ Complete Solution Checklist

### Step 1: Verify Local Build Works
```bash
# 1. Clean existing build
rm -rf public/build

# 2. Install fresh dependencies
npm install

# 3. Build for production
npm run build

# 4. Verify assets were created
bash verify-vite-assets.sh

# 5. Check that files exist
ls -la public/build/
ls -la public/build/assets/
cat public/build/manifest.json | head -20
```

**Expected Output:**
- `public/build/manifest.json` exists and is valid JSON
- `public/build/assets/` contains many `.js` and `.css` files
- All files have proper permissions (755 for directories, 644 for files)

---

### Step 2: Fix Environment Configuration

#### Local Development (.env)
```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# NO ASSET_URL needed for local
```

#### Production (.env on server)
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://parish.quovadisyouthhub.org

# CRITICAL: Set this for production
ASSET_URL=https://parish.quovadisyouthhub.org
```

---

### Step 3: Production Deployment Process

#### Option A: Manual Deployment (Recommended First Time)

```bash
# ON YOUR LOCAL MACHINE:
# 1. Build assets locally
npm run build

# 2. Create deployment archive
tar -czf production-deploy.tar.gz \
    app/ \
    bootstrap/ \
    config/ \
    database/ \
    public/ \
    resources/ \
    routes/ \
    storage/ \
    artisan \
    composer.json \
    composer.lock \
    .env.example

# 3. Upload to server via cPanel File Manager or FTP
# - Upload production-deploy.tar.gz
# - Extract in your home directory
# - Move files to public_html or appropriate directory
```

#### On Production Server (via SSH or cPanel Terminal):

```bash
# 1. Navigate to your application directory
cd /home2/shemidig/parish_system

# 2. Extract uploaded files (if using tar.gz)
tar -xzf production-deploy.tar.gz

# 3. Set up environment
cp .env.example .env
# EDIT .env with production values (database, APP_URL, etc.)

# 4. Install Composer dependencies (production only)
/opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader

# 5. Generate application key (if not set)
php artisan key:generate

# 6. Run migrations
php artisan migrate --force

# 7. Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Fix permissions
chmod -R 755 storage bootstrap/cache public/build
chmod -R 775 storage/logs storage/framework

# 9. Verify assets exist
ls -la public/build/
ls -la public/build/assets/

# 10. Test the diagnostic page
# Visit: https://parish.quovadisyouthhub.org/diagnostic.php
```

---

### Step 4: Verify Production Assets

**Visit Diagnostic Page:**
```
https://parish.quovadisyouthhub.org/diagnostic.php
```

This page will show you:
- ✅ Build directory exists
- ✅ Manifest file is valid
- ✅ All assets are present
- ✅ File permissions are correct
- ✅ PHP version is compatible

---

### Step 5: Common Issues & Solutions

#### Issue 1: "Build directory not found"
**Solution:**
```bash
# Assets weren't uploaded or extracted
# Re-upload public/build directory from local machine
# OR rebuild on server (if Node.js available):
npm install
npm run build
```

#### Issue 2: "Manifest file missing"
**Solution:**
```bash
# Rebuild assets
npm run build

# Verify manifest exists
cat public/build/manifest.json | jq .
```

#### Issue 3: "Files referenced but not found"
**Solution:**
```bash
# Manifest and actual files are out of sync
# Delete and rebuild:
rm -rf public/build
npm run build

# Verify all files exist:
bash verify-vite-assets.sh
```

#### Issue 4: "Permission denied"
**Solution:**
```bash
# Fix permissions on production server:
cd /home2/shemidig/parish_system
chmod -R 755 public/build
chmod 644 public/build/manifest.json
chmod 644 public/build/assets/*

# Verify ownership (should be your cPanel user):
chown -R shemidig:shemidig public/build
```

#### Issue 5: ".htaccess not working"
**Solution:**
```bash
# Verify .htaccess exists
ls -la public/.htaccess

# Check Apache can read it
chmod 644 public/.htaccess

# Verify mod_rewrite is enabled (contact host if not)
```

---

## 🔍 Debugging Steps

### 1. Browser Developer Tools
```
F12 → Console Tab
Look for errors starting with "Failed to load module script"
```

### 2. Network Tab
```
F12 → Network Tab → Reload Page
Find the failed .js file
Click on it → Response Tab
If you see HTML (Laravel error page), assets are missing
```

### 3. Check File Directly
```
Visit in browser:
https://parish.quovadisyouthhub.org/build/manifest.json

Should show JSON, not HTML error
```

### 4. Laravel Logs
```bash
# On server:
tail -f storage/logs/laravel.log

# Look for 404 errors or file not found
```

---

## 📋 Pre-Deployment Checklist

- [ ] Build assets locally: `npm run build`
- [ ] Verify build: `bash verify-vite-assets.sh`
- [ ] Check manifest.json exists and is valid
- [ ] Ensure .htaccess is up to date
- [ ] Set correct .env values for production
- [ ] Test locally first: `php artisan serve`
- [ ] Create backup of production database
- [ ] Upload all files to production
- [ ] Set file permissions correctly
- [ ] Run `composer install --no-dev --optimize-autoloader`
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Cache configs: `php artisan config:cache`
- [ ] Test diagnostic page: `/diagnostic.php`
- [ ] Check browser console for errors
- [ ] Test main application functionality

---

## 🎯 Expected File Structure on Production

```
/home2/shemidig/parish_system/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
│   ├── .htaccess          ← MUST exist
│   ├── index.php          ← MUST exist
│   ├── diagnostic.php     ← For debugging
│   └── build/             ← CRITICAL!
│       ├── manifest.json  ← MUST exist
│       └── assets/        ← MUST have .js/.css files
│           ├── app-XXXXXX.js
│           ├── app-XXXXXX.css
│           └── ...
├── resources/
├── routes/
├── storage/
│   ├── logs/              ← Must be writable
│   └── framework/         ← Must be writable
├── vendor/
├── .env                   ← Production config
├── artisan
└── composer.json
```

---

## 🆘 Still Having Issues?

### Quick Debug Commands

```bash
# Check if Node.js is available on server
node --version
npm --version

# Check PHP version
php --version

# Verify Laravel can read manifest
php -r "echo json_encode(json_decode(file_get_contents('public/build/manifest.json')), JSON_PRETTY_PRINT);"

# Check Apache modules
php -m | grep mod_rewrite

# Test asset serving
curl -I https://parish.quovadisyouthhub.org/build/manifest.json
```

### Contact Points
- Check diagnostic page: `/diagnostic.php`
- Review Laravel logs: `storage/logs/laravel.log`
- Check browser console: F12 → Console
- Review network requests: F12 → Network

---

## 🎉 Success Indicators

When everything works:
- ✅ Homepage loads with styling
- ✅ No console errors
- ✅ All network requests return 200
- ✅ `/diagnostic.php` shows all green checks
- ✅ Can navigate between pages
- ✅ JavaScript interactions work

---

## 📚 Additional Resources

- **Vite Documentation**: https://vitejs.dev/guide/
- **Laravel Vite Integration**: https://laravel.com/docs/12.x/vite
- **Deployment Guide**: See `DEPLOYMENT_INSTRUCTIONS.md`
- **Server Setup**: Contact cPanel support for Apache configuration
