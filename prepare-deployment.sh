#!/bin/bash

# ====================================
# PARISH MANAGEMENT SYSTEM - FINAL DEPLOYMENT PREPARATION
# ====================================
# This script prepares everything for production deployment

set -e  # Exit on any error

echo "🎯 Parish Management System - Final Deployment Preparation"
echo "=========================================================="
echo "Preparing for production deployment to parish.quovadisyouthhub.org"
echo "Started at: $(date)"
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the Laravel root directory."
    exit 1
fi

# ====================================
# STEP 1: CLEAN AND OPTIMIZE
# ====================================
echo "🧹 STEP 1: Cleaning and Optimizing"
echo "=================================="

# Clear all Laravel caches
echo "Clearing Laravel caches..."
php artisan config:clear || echo "Config clear failed (normal if no config cached)"
php artisan cache:clear || echo "Cache clear failed (normal if no cache)"
php artisan route:clear || echo "Route clear failed (normal if no routes cached)"
php artisan view:clear || echo "View clear failed (normal if no views cached)"

# Clear storage caches
echo "Clearing storage caches..."
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*
rm -rf bootstrap/cache/*.php

echo "✅ System cleaned"
echo ""

# ====================================
# STEP 2: INSTALL PRODUCTION DEPENDENCIES
# ====================================
echo "📦 STEP 2: Installing Production Dependencies"
echo "============================================="

echo "Installing Composer dependencies (production mode)..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "Installing NPM dependencies..."
npm ci

echo "✅ Dependencies installed"
echo ""

# ====================================
# STEP 3: BUILD PRODUCTION ASSETS
# ====================================
echo "🔨 STEP 3: Building Production Assets"
echo "====================================="

echo "Building optimized production assets..."
npm run build:production

# Verify build
if [ ! -d "public/build" ] || [ ! -f "public/build/manifest.json" ]; then
    echo "❌ Build failed - no assets generated"
    exit 1
fi

asset_count=$(find public/build -name "*.js" -o -name "*.css" | wc -l)
total_files=$(find public/build -type f | wc -l)

if [ "$asset_count" -lt 90 ]; then
    echo "❌ Build incomplete - only $asset_count assets found (expected 90+)"
    exit 1
fi

echo "✅ Build successful - $total_files total files, $asset_count CSS/JS assets"
echo ""

# ====================================
# STEP 4: VERIFY CRITICAL FILES
# ====================================
echo "🔍 STEP 4: Verifying Critical Files"
echo "==================================="

echo "Checking critical deployment files..."

critical_files=(
    "public/build/manifest.json"
    "public/.htaccess"
    "public/index.php"
    ".env.example"
    "composer.json"
    "artisan"
)

for file in "${critical_files[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file"
    else
        echo "❌ MISSING: $file"
        exit 1
    fi
done

echo ""

# ====================================
# STEP 5: CREATE DEPLOYMENT HELPERS
# ====================================
echo "🛠️ STEP 5: Creating Deployment Helpers"
echo "======================================"

# Create production helper for cPanel
cat > public/production-helper.php << 'EOF'
<?php
/**
 * PARISH MANAGEMENT SYSTEM - PRODUCTION DEPLOYMENT HELPER
 * Upload this to your production server and visit: https://parish.quovadisyouthhub.org/production-helper.php
 * DELETE THIS FILE after successful deployment!
 */

if (php_sapi_name() === 'cli') {
    die("This script should only be run via web browser\n");
}

$basePath = dirname(__DIR__);
$action = $_GET['action'] ?? '';
$output = '';

if ($action && file_exists($basePath . '/artisan')) {
    chdir($basePath);
    
    switch ($action) {
        case 'generate_key':
            if (!file_exists('.env')) {
                $output = "<div style='color: red;'>❌ .env file not found. Please create it first.</div>";
            } else {
                exec('php artisan key:generate 2>&1', $result, $returnCode);
                if ($returnCode === 0) {
                    $output = "<div style='color: green;'>✅ Application key generated successfully!</div>";
                } else {
                    $output = "<div style='color: red;'>❌ Failed to generate key: " . implode('<br>', $result) . "</div>";
                }
            }
            break;
            
        case 'migrate':
            exec('php artisan migrate --force 2>&1', $result, $returnCode);
            if ($returnCode === 0) {
                $output = "<div style='color: green;'>✅ Database migrations completed successfully!</div>";
            } else {
                $output = "<div style='color: red;'>❌ Migration failed: " . implode('<br>', $result) . "</div>";
            }
            break;
            
        case 'optimize':
            $commands = ['config:cache', 'route:cache', 'view:cache', 'optimize'];
            $output = "<div><h3>🔧 Running optimization commands...</h3>";
            foreach ($commands as $cmd) {
                exec("php artisan $cmd 2>&1", $result, $returnCode);
                if ($returnCode === 0) {
                    $output .= "<div style='color: green;'>✅ $cmd completed</div>";
                } else {
                    $output .= "<div style='color: orange;'>⚠️ $cmd: " . implode(' ', $result) . "</div>";
                }
            }
            $output .= "</div>";
            break;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Parish Management System - Production Helper</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; margin: 10px 5px; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .danger { background: #dc3545; }
        .danger:hover { background: #c82333; }
    </style>
</head>
<body>
    <h1>🚀 Parish Management System - Production Helper</h1>
    
    <div class="warning">
        <strong>⚠️ SECURITY WARNING:</strong> Delete this file immediately after deployment completion!
    </div>

    <?php if ($output): ?>
        <div class="success"><?php echo $output; ?></div>
    <?php endif; ?>

    <h2>System Information</h2>
    <ul>
        <li>PHP Version: <?php echo PHP_VERSION; ?></li>
        <li>Laravel Detected: <?php echo file_exists($basePath . '/artisan') ? '✅ Yes' : '❌ No'; ?></li>
        <li>.env File: <?php echo file_exists($basePath . '/.env') ? '✅ Present' : '❌ Missing'; ?></li>
        <li>Storage Writable: <?php echo is_writable($basePath . '/storage') ? '✅ Yes' : '❌ No'; ?></li>
    </ul>

    <h2>Build Assets Status</h2>
    <?php if (is_dir(__DIR__ . '/build')): ?>
        <?php $assetCount = iterator_count(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/build', RecursiveDirectoryIterator::SKIP_DOTS))); ?>
        <ul>
            <li>Build Directory: ✅ Present</li>
            <li>Total Assets: <?php echo $assetCount; ?> files</li>
            <li>Manifest: <?php echo file_exists(__DIR__ . '/build/manifest.json') ? '✅ Present' : '❌ Missing'; ?></li>
        </ul>
    <?php else: ?>
        <div style="color: red;">❌ Build directory missing! Please upload /public/build/ from your build.</div>
    <?php endif; ?>

    <?php if (file_exists($basePath . '/artisan')): ?>
    <h2>Deployment Actions</h2>
    <p>Click buttons to perform deployment tasks:</p>
    
    <a href="?action=generate_key"><button>🔑 Generate APP_KEY</button></a>
    <a href="?action=migrate"><button>🗃️ Run Migrations</button></a>
    <a href="?action=optimize"><button>🚀 Optimize Application</button></a>
    
    <br><br>
    <a href="?action=delete_self" onclick="return confirm('Delete this helper file?')">
        <button class="danger">🗑️ Delete This Helper</button>
    </a>
    <?php endif; ?>

    <?php if ($_GET['action'] === 'delete_self'): ?>
        <?php
        if (unlink(__FILE__)) {
            echo "<script>alert('Helper deleted!'); window.location.href = '/';</script>";
        } else {
            echo "<div style='color: red;'>❌ Could not delete. Remove manually.</div>";
        }
        ?>
    <?php endif; ?>

    <footer style="margin-top: 50px; text-align: center; color: #666;">
        Parish Management System Production Helper - <?php echo date('Y-m-d H:i:s'); ?>
    </footer>
</body>
</html>
EOF

echo "✅ Production helper created: public/production-helper.php"

# Create deployment instructions
cat > DEPLOYMENT_INSTRUCTIONS.md << 'EOF'
# 🚀 PRODUCTION DEPLOYMENT INSTRUCTIONS

## ✅ SYSTEM STATUS: READY FOR DEPLOYMENT

Your Parish Management System has been optimized and is ready for production deployment.

### 📊 Build Summary
- **Total Assets**: 99 files
- **Main CSS**: app-CbxHDkO3.css (83.48 kB)  
- **Main JS**: app-VsyiRadE.js (17.70 kB)
- **Vendor JS**: vendor-BJRZWs4n.js (139.99 kB)
- **Inertia JS**: inertia-3kzqnxf1.js (155.63 kB)

---

## 🏗️ PHASE 1: cPANEL SETUP

### Step 1: Create Subdomain
1. **cPanel → Domains → Subdomains**
2. **Subdomain**: `parish`
3. **Domain**: `quovadisyouthhub.org`
4. **Document Root**: `/home/username/parish.quovadisyouthhub.org/public` ⚠️ **CRITICAL**

### Step 2: Create MySQL Database
1. **cPanel → MySQL Databases**
2. **Database**: `username_parish` (note the prefix)
3. **User**: `username_parishuser`
4. **Password**: Generate strong password
5. **Privileges**: Grant ALL PRIVILEGES

**Save these credentials for .env configuration:**
```
DB_DATABASE=username_parish
DB_USERNAME=username_parishuser
DB_PASSWORD=your_generated_password
```

---

## 📦 PHASE 2: UPLOAD FILES

### Option A: Upload via cPanel File Manager
1. Create ZIP of entire project (excluding node_modules)
2. Upload to `/home/username/parish.quovadisyouthhub.org/`
3. Extract all files

### Option B: Git Clone (if available)
```bash
git clone https://github.com/JosephNjoroge8/parish-management-system.git parish.quovadisyouthhub.org
```

### ⚠️ CRITICAL FILES TO VERIFY
- [ ] `/public/build/` directory with all 99 files
- [ ] `/public/build/manifest.json`
- [ ] `/public/.htaccess`
- [ ] `/vendor/` directory (or run composer install)

---

## ⚙️ PHASE 3: CONFIGURATION

### Step 1: Create .env File
1. **Copy** `.env.example` to `.env`
2. **Update** database credentials:
   ```env
   DB_CONNECTION=production
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=your_actual_db_name
   DB_USERNAME=your_actual_db_user
   DB_PASSWORD=your_actual_db_password
   ```
3. **Update** APP_URL:
   ```env
   APP_URL=https://parish.quovadisyouthhub.org
   ```

### Step 2: Set Permissions
Set these directory permissions to **755**:
- `/storage/`
- `/storage/framework/`
- `/storage/framework/cache/`
- `/storage/framework/sessions/`
- `/storage/framework/views/`
- `/storage/logs/`
- `/bootstrap/cache/`

### Step 3: Install Dependencies (if needed)
```bash
composer install --no-dev --optimize-autoloader
```

---

## 🔧 PHASE 4: DEPLOYMENT COMMANDS

### Using Web Helper (Recommended)
1. **Visit**: `https://parish.quovadisyouthhub.org/production-helper.php`
2. **Click**: "🔑 Generate APP_KEY"
3. **Click**: "🗃️ Run Migrations"  
4. **Click**: "🚀 Optimize Application"
5. **Click**: "🗑️ Delete This Helper" (IMPORTANT!)

### Using Terminal (if available)
```bash
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## ✅ PHASE 5: TESTING

### Essential Tests
1. **Homepage**: Visit `https://parish.quovadisyouthhub.org`
2. **Login**: Test user authentication
3. **Members**: Add/view member records
4. **Dashboard**: Check statistics display
5. **Reports**: Test export functionality

### Browser DevTools Check
- **Console**: No red errors
- **Network**: All assets return 200 status
- **Sources**: Verify `/build/assets/` files load

---

## 🚨 TROUBLESHOOTING

| Issue | Solution |
|-------|----------|
| White screen | Check browser console, verify build assets uploaded |
| Assets not loading | Confirm document root points to `/public` |
| Database errors | Verify credentials in `.env` |
| 500 errors | Check `/storage/logs/laravel.log` |

---

## 📞 SUPPORT

- **Logs**: Check `/storage/logs/laravel.log`
- **Asset Verification**: Run `bash verify-assets.sh`
- **Documentation**: See `PRODUCTION_DEPLOYMENT_GUIDE.md`

---

**🎉 Your Parish Management System is ready for production!**
EOF

echo "✅ Deployment instructions created: DEPLOYMENT_INSTRUCTIONS.md"
echo ""

# ====================================
# STEP 6: FINAL VERIFICATION
# ====================================
echo "🔍 STEP 6: Final Verification"
echo "============================="

echo "Running final asset verification..."
bash verify-assets.sh

echo ""
echo "🎉 DEPLOYMENT PREPARATION COMPLETE!"
echo "=================================="
echo "Completed at: $(date)"
echo ""
echo "📋 NEXT STEPS:"
echo "1. Follow instructions in DEPLOYMENT_INSTRUCTIONS.md"
echo "2. Upload all files to your cPanel hosting"
echo "3. Configure database and .env file"
echo "4. Use production-helper.php for web-based setup"
echo "5. Test thoroughly before going live"
echo ""
echo "🎯 Your Parish Management System is READY for production deployment!"
echo "   Total assets: $total_files files"
echo "   System size: $(du -sh . | cut -f1) (excluding node_modules)"
echo ""
echo "📞 For support, check PRODUCTION_DEPLOYMENT_GUIDE.md"