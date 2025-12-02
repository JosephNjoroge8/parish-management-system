# 🧪 CI/CD Testing & Validation Guide

This guide will help you test and validate your CI/CD pipeline to ensure it's working correctly.

---

## Pre-Deployment Testing Checklist

Before setting up the live webhook, test each component individually.

### ✅ Phase 1: Local Testing

#### Test 1: Verify Configuration Files

```bash
cd /home/joseph/Desktop/parish-management-system

# Check webhook.php configuration
grep "WEBHOOK_SECRET" public/webhook.php
grep "DEPLOYMENT_PATH" public/webhook.php
grep "PHP_VERSION" public/webhook.php

# Check deploy.php configuration
grep "DEPLOYMENT_PATH" public/deploy.php
grep "DEPLOY_SECRET" public/deploy.php

# Verify secrets match
echo "Webhook secret and deploy secret must match!"
```

**Expected Result:**
- Both files have the same secret
- Paths are correct for your server
- PHP version matches your cPanel PHP version

#### Test 2: Verify GitHub Actions Workflow

```bash
# Check workflow file exists
cat .github/workflows/laravel.yml

# Validate YAML syntax
npm install -g yaml-validator
yaml-validator .github/workflows/laravel.yml
```

**Expected Result:**
- Workflow file exists
- No YAML syntax errors
- All jobs defined (test, build, code-quality, notify)

#### Test 3: Run Tests Locally

```bash
# Copy .env for testing
cp .env.example .env
php artisan key:generate

# Install dependencies
composer install
npm install

# Run tests
php artisan test

# Run code quality check
vendor/bin/pint --test

# Build assets
npm run build
```

**Expected Result:**
- All tests pass
- Pint shows no errors (or run `vendor/bin/pint` to fix)
- Build completes with manifest.json created

---

### ✅ Phase 2: Server Preparation Testing

#### Test 4: Verify Server Requirements

Create `public/server-check.php`:

```php
<?php
header('Content-Type: text/plain');

echo "Parish Management System - Server Requirements Check\n";
echo "======================================================\n\n";

// PHP Version
echo "✓ PHP Version: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
    echo "  Status: OK (>= 8.2 required)\n\n";
} else {
    echo "  Status: FAIL (8.2+ required)\n\n";
}

// Required Extensions
$required = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo'];
echo "Required PHP Extensions:\n";
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    echo ($loaded ? '✓' : '✗') . " {$ext}\n";
}
echo "\n";

// Required Functions
$functions = ['proc_open', 'exec', 'shell_exec', 'curl_exec'];
echo "Required Functions:\n";
foreach ($functions as $func) {
    $disabled = in_array($func, explode(',', ini_get('disable_functions')));
    echo ($disabled ? '✗' : '✓') . " {$func}\n";
}
echo "\n";

// Git
echo "Git: ";
$git = shell_exec('which git 2>/dev/null') ?: shell_exec('where git 2>/dev/null');
echo $git ? "✓ " . trim($git) . "\n" : "✗ Not found\n";

// Composer
echo "Composer: ";
$composer = shell_exec('which composer 2>/dev/null') ?: '/opt/cpanel/composer/bin/composer';
if (file_exists($composer)) {
    echo "✓ {$composer}\n";
} else {
    echo "✗ Not found\n";
}

// NPM
echo "NPM: ";
$npm = shell_exec('which npm 2>/dev/null');
echo $npm ? "✓ " . trim($npm) . "\n" : "✗ Not found\n";

// Writable Directories
echo "\nWritable Directories:\n";
$dirs = ['storage', 'bootstrap/cache'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/../' . $dir;
    $writable = is_writable($path);
    echo ($writable ? '✓' : '✗') . " {$dir}\n";
}

echo "\n";
echo "If all checks show ✓, your server is ready for CI/CD!\n";
```

Upload to server and visit: `https://yourdomain.com/server-check.php`

**Expected Result:**
- All required extensions: ✓
- All required functions: ✓
- Git, Composer, NPM: ✓
- Writable directories: ✓

#### Test 5: Test Database Connection

Create `public/db-check.php`:

```php
<?php
// Load environment
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

header('Content-Type: text/plain');

echo "Database Connection Test\n";
echo "========================\n\n";

try {
    $host = $_ENV['DB_HOST'];
    $database = $_ENV['DB_DATABASE'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];
    
    echo "Host: {$host}\n";
    echo "Database: {$database}\n";
    echo "Username: {$username}\n\n";
    
    $pdo = new PDO(
        "mysql:host={$host};dbname={$database}",
        $username,
        $password
    );
    
    echo "✓ Connection successful!\n\n";
    
    // Test query
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "MySQL Version: " . $version['version'] . "\n";
    
    // Test tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . count($tables) . "\n";
    
} catch (Exception $e) {
    echo "✗ Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
}
```

**Expected Result:**
- Connection successful
- Shows MySQL version
- Shows table count

---

### ✅ Phase 3: Webhook Testing

#### Test 6: Test Webhook File Upload

```bash
# Upload webhook.php to server
# Set permissions
chmod 644 public/webhook.php

# Test file is accessible (should return JSON error)
curl https://yourdomain.com/webhook.php
```

**Expected Result:**
```json
{
  "success": false,
  "message": "Only POST requests are accepted",
  "data": [],
  "timestamp": "2024-12-02T..."
}
```

#### Test 7: Test Webhook Signature Verification

Create `test-webhook.sh`:

```bash
#!/bin/bash

# Your webhook secret
SECRET="your-webhook-secret-here"

# Test payload
PAYLOAD='{"ref":"refs/heads/Main","repository":{"full_name":"test/repo"},"pusher":{"name":"tester"},"commits":[]}'

# Calculate signature
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | sed 's/^.* //')

# Send request
curl -X POST https://yourdomain.com/webhook.php \
  -H "Content-Type: application/json" \
  -H "X-GitHub-Event: push" \
  -H "X-Hub-Signature-256: sha256=$SIGNATURE" \
  -d "$PAYLOAD" \
  -v
```

**Expected Result:**
- Status: 200 OK
- Response indicates signature verified
- Webhook log shows request

#### Test 8: Test GitHub Webhook Delivery

1. Go to GitHub Repository Settings → Webhooks
2. Click on your webhook
3. Scroll to "Recent Deliveries"
4. Click "Redeliver" on any delivery
5. Check response

**Expected Result:**
- Response code: 200
- Response body: JSON with success message
- Check logs on server

---

### ✅ Phase 4: Deployment Testing

#### Test 9: Test Git Operations

SSH or use cPanel Terminal:

```bash
cd /home2/YOUR_USERNAME/parish_system

# Test git fetch
git fetch origin Main

# Test git reset
git reset --hard origin/Main

# Test git clean
git clean -fd

# Check status
git status
```

**Expected Result:**
- All commands execute without errors
- Repository is clean and up to date

#### Test 10: Test Composer Install

```bash
cd /home2/YOUR_USERNAME/parish_system

# Test composer
/opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader --no-interaction

# Check vendor directory
ls -la vendor/
```

**Expected Result:**
- Dependencies installed successfully
- `vendor/` directory exists and populated

#### Test 11: Test NPM Build

```bash
cd /home2/YOUR_USERNAME/parish_system

# Clean install
rm -rf node_modules package-lock.json
npm install

# Build
npm run build

# Verify output
ls -la public/build/
cat public/build/manifest.json
```

**Expected Result:**
- Build completes without errors
- `public/build/manifest.json` exists
- Asset files in `public/build/assets/`

#### Test 12: Test Laravel Commands

```bash
cd /home2/YOUR_USERNAME/parish_system

# Test artisan
/usr/local/bin/ea-php82 artisan --version

# Test migrations
/usr/local/bin/ea-php82 artisan migrate --pretend

# Test cache commands
/usr/local/bin/ea-php82 artisan config:cache
/usr/local/bin/ea-php82 artisan route:cache
/usr/local/bin/ea-php82 artisan view:cache

# Test optimization
/usr/local/bin/ea-php82 artisan optimize
```

**Expected Result:**
- All commands execute successfully
- No errors in output

#### Test 13: Test Complete Deployment Script

```bash
# Manually trigger deploy.php
curl -X POST https://yourdomain.com/deploy.php \
  -d "secret=$(php -r "echo hash_hmac('sha256', date('YmdH'), 'your-webhook-secret');")" \
  -d "branch=Main" \
  -d "commits=1" \
  -d "pusher=test"
```

**Expected Result:**
- Deployment executes
- Logs show each step
- Site remains functional

---

### ✅ Phase 5: GitHub Actions Testing

#### Test 14: Test GitHub Actions Workflow

1. Make a small change:
   ```bash
   echo "# CI/CD Test" >> README.md
   git add README.md
   git commit -m "Test GitHub Actions"
   git push origin Main
   ```

2. Go to GitHub → Actions tab
3. Watch the workflow run

**Expected Result:**
- Workflow triggers automatically
- All jobs complete successfully (green checkmarks)
- Artifacts are created (production-assets)

#### Test 15: Test Failed Workflow

1. Intentionally break a test:
   ```php
   // In tests/Feature/ExampleTest.php
   public function test_example(): void
   {
       $this->assertTrue(false); // Will fail
   }
   ```

2. Push to Main
3. Watch workflow fail

**Expected Result:**
- Workflow fails (red X)
- Webhook is NOT triggered
- Deployment does NOT occur
- No changes on production

4. Fix the test and push again

---

### ✅ Phase 6: End-to-End Testing

#### Test 16: Complete Pipeline Test

This tests the entire pipeline from push to deployment.

**Step 1: Prepare**
```bash
git checkout -b test/cicd-pipeline
```

**Step 2: Make a visible change**
```php
// In resources/js/Pages/Dashboard.tsx or similar
// Add a comment or small UI change
{/* CI/CD Pipeline Test - {new Date().toISOString()} */}
```

**Step 3: Commit and create PR**
```bash
git add .
git commit -m "Test complete CI/CD pipeline"
git push origin test/cicd-pipeline
```

Create Pull Request on GitHub.

**Step 4: Verify PR checks**
- GitHub Actions should run
- All tests should pass
- No deployment should occur (not Main branch)

**Step 5: Merge to Main**
- Merge the PR

**Step 6: Monitor deployment**

```bash
# Terminal 1: Watch webhook log
tail -f /home2/YOUR_USERNAME/parish_system/storage/logs/webhook.log

# Terminal 2: Watch deployment log
tail -f /home2/YOUR_USERNAME/parish_system/storage/logs/deployment.log

# Terminal 3: Watch Laravel log
tail -f /home2/YOUR_USERNAME/parish_system/storage/logs/laravel.log
```

**Step 7: Verify deployment**
1. Wait 8-15 minutes
2. Visit your production site
3. Verify changes appear
4. Check browser console (no errors)
5. Test login and key features

**Expected Timeline:**
- 00:00 - Push to Main
- 00:30 - GitHub Actions starts
- 05:00 - Tests complete
- 06:00 - Build completes
- 06:30 - Webhook triggered
- 07:00 - Deployment starts
- 12:00 - Deployment completes
- 12:30 - Changes live on production

---

## Validation Checklist

After testing, verify all these items:

### Repository
- [ ] GitHub Actions workflow file exists (`.github/workflows/laravel.yml`)
- [ ] Workflow runs on push to Main
- [ ] All tests pass locally
- [ ] All tests pass in GitHub Actions
- [ ] Build artifacts are created

### Webhook
- [ ] `webhook.php` uploaded to server
- [ ] Webhook accessible at URL
- [ ] Signature verification works
- [ ] GitHub webhook configured correctly
- [ ] GitHub webhook shows green checkmark
- [ ] Recent deliveries show 200 responses

### Deployment
- [ ] `deploy.php` uploaded to server
- [ ] Git repository cloned on server
- [ ] `.env` file configured correctly
- [ ] Database connection works
- [ ] Composer dependencies installed
- [ ] NPM dependencies installed
- [ ] Assets build successfully
- [ ] Migrations run successfully
- [ ] File permissions correct

### Security
- [ ] Webhook secret is strong (32+ chars)
- [ ] Secrets match in both files
- [ ] `.env` not in Git
- [ ] `APP_DEBUG=false` in production
- [ ] HTTPS enabled and forced
- [ ] IP restrictions configured (optional)
- [ ] File permissions secure (755/644)

### Functionality
- [ ] Site loads correctly
- [ ] Login works
- [ ] Assets load (CSS/JS)
- [ ] No console errors
- [ ] Database queries work
- [ ] Forms submit correctly
- [ ] File uploads work (if applicable)
- [ ] Email sending works (if applicable)

### Monitoring
- [ ] Deployment logs are created
- [ ] Webhook logs are created
- [ ] Laravel logs accessible
- [ ] Can view logs via cPanel
- [ ] Email notifications work (if enabled)

### Rollback
- [ ] Backups are created
- [ ] Can list backup files
- [ ] Can restore from backup
- [ ] Rollback works on failure

---

## Performance Benchmarks

After setup, benchmark your deployment:

| Metric | Target | Your Result |
|--------|--------|-------------|
| GitHub Actions (Tests) | < 3 min | _____ |
| GitHub Actions (Build) | < 5 min | _____ |
| Webhook Response Time | < 2 sec | _____ |
| Git Pull | < 30 sec | _____ |
| Composer Install | < 2 min | _____ |
| NPM Install | < 3 min | _____ |
| Asset Build | < 4 min | _____ |
| Migrations | < 30 sec | _____ |
| Cache Optimization | < 30 sec | _____ |
| **Total Deployment** | **< 15 min** | **_____** |

---

## Troubleshooting Tests

If any test fails, refer to the troubleshooting section in [CICD_SETUP_GUIDE.md](CICD_SETUP_GUIDE.md).

### Quick Debug Steps

1. **Check logs first:**
   ```bash
   tail -50 storage/logs/webhook.log
   tail -50 storage/logs/deployment.log
   tail -50 storage/logs/laravel.log
   ```

2. **Verify file permissions:**
   ```bash
   ls -la public/webhook.php
   ls -la public/deploy.php
   ls -la storage/
   ```

3. **Test manually:**
   ```bash
   # Test git
   cd /home2/YOUR_USERNAME/parish_system
   git fetch origin Main
   
   # Test composer
   /opt/cpanel/composer/bin/composer --version
   
   # Test npm
   npm --version
   
   # Test artisan
   /usr/local/bin/ea-php82 artisan --version
   ```

4. **Enable debug mode temporarily:**
   ```php
   // In webhook.php and deploy.php
   ini_set('display_errors', '1');
   error_reporting(E_ALL);
   ```

---

## Sign-off Checklist

Before going live with your CI/CD pipeline:

### Pre-Launch
- [ ] All tests pass (Phase 1-6)
- [ ] Validation checklist complete
- [ ] Performance benchmarks acceptable
- [ ] Security checklist reviewed
- [ ] Documentation read and understood
- [ ] Team trained on workflow
- [ ] Rollback procedure tested

### Launch
- [ ] Merge first feature successfully
- [ ] Monitor first deployment closely
- [ ] Verify production after deployment
- [ ] Check all logs for errors
- [ ] Test critical user paths
- [ ] Notify team of success

### Post-Launch
- [ ] Set up monitoring (UptimeRobot, etc.)
- [ ] Schedule maintenance tasks
- [ ] Document any custom configurations
- [ ] Create runbook for common issues
- [ ] Set up alerting for failures

---

## Testing Complete! 🎉

If all tests pass, your CI/CD pipeline is ready for production use.

**Next Steps:**
1. Start using the pipeline for all deployments
2. Monitor logs regularly
3. Keep documentation updated
4. Share knowledge with your team
5. Iterate and improve based on feedback

**Remember:** The first few deployments should be monitored closely. Over time, you'll gain confidence in the automated process.

For ongoing support, refer to:
- [CICD_SETUP_GUIDE.md](CICD_SETUP_GUIDE.md) - Complete documentation
- [CICD_QUICK_START.md](CICD_QUICK_START.md) - Quick reference guide

---

**Testing Guide Version:** 1.0  
**Last Updated:** December 2, 2025
