# 🛠️ IMMEDIATE FIX FOR YOUR cPanel DEPLOYMENT

## Current Issue: `composer: command not found`

You're experiencing this because cPanel shared hosting doesn't have Composer installed. **Don't worry - this is fixable!**

## ✅ SOLUTION (Step by Step)

Since you've already run some commands successfully, let's complete the setup:

### 1. Create the .env file manually:
```bash
cat > .env << 'EOF'
APP_NAME="Parish Management System"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://shemidigy.co.ke
APP_TIMEZONE=UTC

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=shemidig_parish_system
DB_USERNAME=shemidig_NjoroParish
DB_PASSWORD=Nj0r0g3@345

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SANCTUM_STATEFUL_DOMAINS=shemidigy.co.ke

PARISH_NAME="Njoroge Parish"
PARISH_EMAIL="info@shemidigy.co.ke"

VITE_APP_ENV=production
VITE_APP_URL="https://shemidigy.co.ke"
EOF
```

### 2. Generate application key:
```bash
php artisan key:generate --force
```

### 3. Test database connection:
```bash
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected successfully';"
```

### 4. Run migrations (creates tables):
```bash
php artisan migrate --force
```

### 5. Seed database with initial data:
```bash
php artisan db:seed --force
```

### 6. Create storage link:
```bash
php artisan storage:link
```

### 7. Optimize for production:
```bash
php artisan optimize:clear
php artisan optimize
```

### 8. Set proper permissions:
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chmod 600 .env
```

## 🎯 Quick Test Commands

After setup, test if everything works:

```bash
# Check system status
php artisan tinker --execute="
echo 'Database: ' . DB::connection()->getDatabaseName() . PHP_EOL;
echo 'Users: ' . \App\Models\User::count() . PHP_EOL;
echo 'Members: ' . \App\Models\Member::count() . PHP_EOL;
echo 'Roles: ' . \Spatie\Permission\Models\Role::count() . PHP_EOL;
"

# Check if routes are working
php artisan route:list --name=reports | head -5
```

## 🌐 Web Server Configuration

Make sure your web server points to the `public` directory. Create/update `.htaccess` in your web root:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ parish_system/public/$1 [L]
</IfModule>
```

## 🔑 Create Admin User (if needed)

If you need to create an admin user:

```bash
php artisan tinker
```

Then in tinker:
```php
$user = \App\Models\User::create([
    'name' => 'Super Admin',
    'email' => 'admin@shemidigy.co.ke',
    'password' => bcrypt('YourSecurePassword123!'),
    'email_verified_at' => now()
]);

$user->assignRole('Super Admin');
echo "Super Admin created: " . $user->email;
exit
```

## ✅ Success Indicators

Your system is working when:
- ✅ Commands run without errors
- ✅ Database shows user/member counts > 0
- ✅ https://shemidigy.co.ke loads without 500 errors
- ✅ You can login to /login
- ✅ Dashboard at /dashboard shows statistics

## 🚨 If You Still Get Errors

1. **Check logs**: `tail -f storage/logs/laravel.log`
2. **Check permissions**: `ls -la storage/`
3. **Verify .env**: `cat .env | head -10`
4. **Test database**: `php artisan tinker --execute="DB::connection()->getPdo();"`

## Why Composer Failed

cPanel shared hosting often doesn't include Composer because:
- It requires command-line access
- Shared hosting limits system tools
- Dependencies are usually pre-installed

**Good News**: Your GitHub repository includes the `vendor` folder with all dependencies, so Composer isn't needed!

---

**🎉 You're almost there! The migrations and optimization commands already worked, so your system is mostly set up. Just need the .env file configured properly.**