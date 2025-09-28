# Environment Variables Reference for cPanel

Since you have an existing .env file in cPanel, use this as a reference to ensure 
your current .env has all necessary variables for the Parish Management System.

## Critical Variables to Check in Your Existing .env:

### Application Settings (Must Have)
```
APP_NAME="Parish Management System"
APP_ENV=production
APP_KEY=base64:YOUR_EXISTING_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

### Database (Update with your cPanel MySQL info)
```
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_cpanel_database_name
DB_USERNAME=your_cpanel_db_user
DB_PASSWORD=your_cpanel_db_password
```

### Session & Security (Recommended for Production)
```
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_COOKIE_SECURE=true
SESSION_COOKIE_HTTPONLY=true
SESSION_SAME_SITE=strict
```

### Caching (Performance)
```
CACHE_DRIVER=file
CACHE_PREFIX=parish_
```

### Mail (Update with your hosting email settings)
```
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Parish Management System"
```

### Optional Performance Settings
```
QUEUE_CONNECTION=sync
BROADCAST_DRIVER=log
FILESYSTEM_DISK=local
LOG_CHANNEL=single
LOG_LEVEL=error
```

## Quick Check Commands:

1. **Verify your .env has an APP_KEY:**
   ```bash
   grep "APP_KEY" .env
   ```

2. **Test database connection:**
   ```bash
   php artisan migrate:status
   ```

3. **Check if authentication system works:**
   ```bash
   php artisan tinker --execute="echo App\Models\User::count() . ' users found';"
   ```

## If Variables Are Missing:

Add any missing variables from above to your existing .env file. The deployment scripts will NOT overwrite your existing .env file, so you have full control over the configuration.