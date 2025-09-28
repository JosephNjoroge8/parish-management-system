# 🚨 503 Service Unavailable Error - Troubleshooting Guide

## 🔍 What 503 Errors Mean

**503 Service Unavailable** indicates the server is temporarily unable to handle requests. In Laravel/cPanel context, this usually means:

1. **PHP processes crashed or overloaded**
2. **Laravel application fatal errors**
3. **Missing routes or controllers**
4. **Database connection failures**
5. **Memory/resource exhaustion**

## 🛠️ Step-by-Step Troubleshooting

### Step 1: Check Laravel Error Logs

**In cPanel File Manager or Terminal:**
```bash
# Check Laravel application logs
cd /home2/shemidig/parish_system
tail -50 storage/logs/laravel.log

# Check for recent errors
grep -i "error\|fatal\|exception" storage/logs/laravel.log | tail -20
```

### Step 2: Check PHP Error Logs

**In cPanel → Error Logs:**
- Look for PHP fatal errors
- Check for memory limit exceeded
- Look for database connection errors

**Common error patterns:**
```
PHP Fatal error: Allowed memory size exhausted
PHP Fatal error: Class not found
PHP Fatal error: Call to undefined method
SQLSTATE[HY000] [2002] Connection refused
```

### Step 3: Verify Route Registration

**Test if routes exist:**
```bash
cd /home2/shemidig/parish_system

# List all routes to check if /admin/users exists
/usr/local/bin/ea-php82 artisan route:list | grep admin

# Check specific routes causing 503
/usr/local/bin/ea-php82 artisan route:list | grep "admin/users"
/usr/local/bin/ea-php82 artisan route:list | grep "admin/settings"
```

### Step 4: Test Laravel Application Health

```bash
cd /home2/shemidig/parish_system

# Test if Laravel boots successfully
/usr/local/bin/ea-php82 artisan about

# Test database connection
/usr/local/bin/ea-php82 artisan tinker
# Then run: DB::connection()->getPdo();

# Clear all caches (might resolve issues)
/usr/local/bin/ea-php82 artisan config:clear
/usr/local/bin/ea-php82 artisan cache:clear
/usr/local/bin/ea-php82 artisan route:clear
/usr/local/bin/ea-php82 artisan view:clear
```

### Step 5: Check File Permissions

```bash
cd /home2/shemidig/parish_system

# Verify permissions are correct
ls -la storage/
ls -la bootstrap/cache/

# Fix permissions if needed
chmod -R 777 storage/
chmod -R 777 bootstrap/cache/
```

### Step 6: Memory and Resource Checks

**In cPanel → Resource Usage:**
- Check if you're hitting memory limits
- Look for CPU usage spikes
- Check for too many active processes

**Increase PHP memory limit in .htaccess (if needed):**
```apache
# Add to .htaccess file
php_value memory_limit 512M
php_value max_execution_time 300
php_value max_input_vars 3000
```

## 🎯 Quick Fixes to Try

### Fix 1: Regenerate Application Cache
```bash
cd /home2/shemidig/parish_system
/usr/local/bin/ea-php82 artisan config:cache
/usr/local/bin/ea-php82 artisan route:cache
```

### Fix 2: Ensure .env File is Correct
```bash
# Verify .env exists and is readable
ls -la .env
head -10 .env

# Test database connection
/usr/local/bin/ea-php82 artisan migrate:status
```

### Fix 3: Check if Controllers Exist
```bash
# Verify admin controllers exist
ls -la app/Http/Controllers/Admin/
ls -la app/Http/Controllers/*Controller.php | grep -i user
```

### Fix 4: Restart PHP-FPM (if possible)
**Contact hosting provider or try:**
- Kill any stuck PHP processes
- Clear OPcache if available
- Restart the domain in cPanel

## 🚀 Most Likely Solutions

### Solution A: Missing Admin Controllers

The routes `/admin/users` and `/admin/settings` suggest you need Admin controllers:

```bash
# Check if these files exist:
app/Http/Controllers/Admin/UserController.php
app/Http/Controllers/Admin/SettingsController.php
```

### Solution B: Route Registration Issue

**Check routes/web.php for:**
```php
Route::prefix('admin')->middleware(['auth', 'role:admin|super-admin'])->group(function () {
    Route::resource('users', UserController::class);
    Route::get('settings', [SettingsController::class, 'index']);
});
```

### Solution C: Database Connection Failure

**Update .env database settings:**
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=shemidig_parish_system
DB_USERNAME=shemidig_NjoroParish
DB_PASSWORD=Nj0r0g3@345
```

## 📞 Emergency Recovery

### If Application Won't Start At All:

1. **Create a simple test file:**
```php
<?php
// Create: test-php.php
phpinfo();
echo "<br>PHP is working!";

// Test database
try {
    $pdo = new PDO('mysql:host=localhost;dbname=shemidig_parish_system', 'shemidig_NjoroParish', 'Nj0r0g3@345');
    echo "<br>Database connection successful!";
} catch (Exception $e) {
    echo "<br>Database error: " . $e->getMessage();
}
?>
```

2. **Upload to:** `/home2/shemidig/parish_system/test-php.php`
3. **Visit:** `http://parish.quovadisyouthhub.org/test-php.php`

### If Routes Are Missing:

```bash
# Regenerate routes and check
cd /home2/shemidig/parish_system
/usr/local/bin/ea-php82 artisan route:clear
/usr/local/bin/ea-php82 artisan route:cache
/usr/local/bin/ea-php82 artisan route:list
```

## 🔧 Next Steps

1. **Run the diagnostics above**
2. **Check the specific error logs**
3. **Identify if it's routes, database, or PHP issues**
4. **Apply the appropriate fix**
5. **Test the application**

**Most common cause:** Missing Admin controllers or incorrectly registered routes for the admin section.