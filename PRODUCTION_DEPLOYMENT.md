# Parish Management System - Production Deployment Guide

## 🚀 Production Deployment Steps

### 1. **Pre-Deployment Checklist**

- ✅ All migrations pushed to GitHub
- ✅ Foreign key constraint fixes implemented
- ✅ Environment variables configured
- ✅ Database connection tested
- ✅ SSL certificate configured (if applicable)

### 2. **Database Setup Commands**

```bash
# Run fresh migrations (CAUTION: This drops all existing data)
php artisan migrate:fresh --seed

# OR for existing production data (safer approach)
php artisan migrate --force
php artisan db:seed --class=UserSeeder --force
php artisan db:seed --class=Enhanced30MemberSeeder --force
```

### 3. **Production Environment Configuration**

Ensure your `.env` file has:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-parish-domain.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=your-database-host
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_secure_password

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your-mail-host
MAIL_PORT=587
MAIL_USERNAME=your-email@parish.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@parish.com
MAIL_FROM_NAME="Parish Management System"
```

### 4. **Fixed Issues for Production**

#### ✅ **Foreign Key Constraint Fix**
The `Enhanced30MemberSeeder` now properly handles MySQL foreign key constraints:

- Detects database driver (SQLite vs MySQL)
- Disables foreign key checks during seeding
- Uses `DELETE` instead of `TRUNCATE` for safer data clearing
- Re-enables foreign key checks after seeding

#### ✅ **Database Compatibility**
- **Development:** Uses SQLite (database/database.sqlite)
- **Production:** Uses MySQL/MariaDB with proper constraint handling

### 5. **Production Deployment Commands**

```bash
# 1. Pull latest code
git pull origin Main

# 2. Install/Update dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 3. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 4. Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Run migrations (choose one option)
# Option A: Fresh setup (DROPS ALL DATA)
php artisan migrate:fresh --seed --force

# Option B: Safe migration (preserves existing data)
php artisan migrate --force

# 6. Create admin user (if not using fresh)
php artisan db:seed --class=UserSeeder --force

# 7. Set proper permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 6. **Login Credentials**

After successful deployment, use these credentials:

- **🔑 Super Admin:** `admin@parish.local` / `parish123`
- **🔑 Backup Admin:** `admin@parish.com` / `admin123`
- **👤 Staff User:** `staff@parish.local` / `staff123`
- **📝 Secretary:** `secretary@parish.local` / `secretary123`

### 7. **Post-Deployment Verification**

1. **Test Login:** Access admin panel with super admin credentials
2. **Check Members:** Verify member data is properly seeded
3. **Test Search:** Ensure real-time search works without refresh
4. **PDF Generation:** Test baptism card downloads
5. **User Management:** Verify all CRUD operations work
6. **Database Integrity:** Check all relationships and constraints

### 8. **Production Security Notes**

- **Change Default Passwords:** Update all seeded user passwords
- **Email Verification:** Enable email verification for new users
- **HTTPS:** Ensure SSL certificate is properly configured
- **Backup Schedule:** Set up automated database backups
- **Monitoring:** Configure error logging and monitoring

### 9. **Troubleshooting Common Issues**

#### Foreign Key Constraint Error
```
SQLSTATE[42000]: Cannot truncate a table referenced in a foreign key constraint
```
**Solution:** The latest code already fixes this. Pull the latest changes from GitHub.

#### Permission Errors
```bash
sudo chmod -R 755 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

#### Cache Issues
```bash
php artisan config:clear
php artisan cache:clear
composer dump-autoload
```

### 10. **Maintenance Commands**

```bash
# Regular maintenance
php artisan queue:work --daemon  # If using queues
php artisan schedule:run         # For scheduled tasks

# Database backup (example)
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Monitor logs
tail -f storage/logs/laravel.log
```

## 🎯 **Production Ready Features**

- ✅ **Real-time Search:** Fixed debounce functionality
- ✅ **PDF Generation:** Baptism cards with proper field mapping
- ✅ **User Management:** Complete CRUD with authentication
- ✅ **Member Management:** Comprehensive parish member system
- ✅ **Data Export:** Excel/PDF export capabilities
- ✅ **Responsive Design:** Mobile-friendly interface
- ✅ **Database Optimization:** Proper indexing and relationships

## 🔧 **Support**

For issues or questions:
1. Check the GitHub repository issues
2. Review Laravel logs: `storage/logs/laravel.log`
3. Enable debug mode temporarily: `APP_DEBUG=true`
4. Use `php artisan tinker` for database debugging

---
**Last Updated:** October 21, 2025  
**Version:** 1.0.0  
**Repository:** https://github.com/JosephNjoroge8/parish-management-system