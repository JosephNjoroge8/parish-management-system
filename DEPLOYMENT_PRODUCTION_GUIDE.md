# Parish Management System - Deployment & Production Guide

## 🚀 Quick Deployment Guide

### For cPanel Hosting

#### Step 1: Repository Setup
1. **Repository:** `https://github.com/JosephNjoroge8/parish-management-system.git`
2. **Branch:** `Main`
3. **Deploy Path:** `/home2/shemidig/parish_system/`

#### Step 2: cPanel Git Deployment
1. Go to **cPanel → Git™ Version Control**
2. Click **"Create"** or **"Connect to Remote"**
3. Set Repository Root: `/home2/shemidig/repositories/parish-system`
4. Set Clone URL: Repository URL above
5. Click **"Pull or Deploy"** → **"Deploy HEAD Commit"**

#### Step 3: Manual Deployment (If Git Fails)
```bash
# Download ZIP from GitHub and extract to /home2/shemidig/parish_system/

# Run in cPanel Terminal:
cd /home2/shemidig/parish_system

# Copy Laravel public files to root
cp public/index.php ./
cp public/.htaccess ./
cp public/favicon.ico ./

# Fix index.php paths
sed -i 's|/../vendor/autoload.php|/vendor/autoload.php|g' index.php
sed -i 's|/../bootstrap/app.php|/bootstrap/app.php|g' index.php

# Create .env file
cp .env.example .env
# Edit .env with your database credentials

# Generate key and setup
/usr/local/bin/ea-php82 artisan key:generate --force
/usr/local/bin/ea-php82 artisan migrate --force
/usr/local/bin/ea-php82 artisan storage:link
/usr/local/bin/ea-php82 artisan config:cache

# Set permissions
chmod -R 755 ./
chmod -R 777 storage/
chmod -R 777 bootstrap/cache/
chmod 600 .env
```

### Environment Configuration

#### Required .env Settings
```env
APP_NAME="Parish Management System"
APP_ENV=production
APP_KEY=base64:your_generated_key_here
APP_DEBUG=false
APP_URL=http://parish.quovadisyouthhub.org

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

## 🔧 Troubleshooting

### Common Issues

#### 1. 404 Error
**Cause:** Laravel needs public folder contents at web root
**Solution:** Copy `public/*` to root and fix `index.php` paths

#### 2. Database Connection Error  
**Cause:** Incorrect .env database settings
**Solution:** Verify database credentials in cPanel MySQL section

#### 3. Permission Errors
**Cause:** Incorrect file permissions
**Solution:** 
```bash
chmod -R 755 ./
chmod -R 777 storage/
chmod -R 777 bootstrap/cache/
```

#### 4. Composer Dependencies Missing
**Cause:** Vendor folder not deployed
**Solution:** Repository includes vendor folder - should work automatically

#### 5. Assets Not Loading
**Cause:** Build files not in correct location
**Solution:** Copy `public/build/` to root if exists

### Performance Optimization

#### Production Commands
```bash
# Cache everything
php artisan config:cache
php artisan route:cache  
php artisan view:cache
php artisan event:cache

# Clear development caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

#### File Permissions
```bash
# Secure permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 777 storage/
chmod -R 777 bootstrap/cache/
chmod 600 .env
```

## 📊 System Features

### Core Functionality
- **Member Management:** Registration, profiles, family tracking
- **Financial Management:** Contributions, pledges, expenses
- **Event Management:** Services, meetings, special events  
- **Reporting System:** 25+ different report types
- **User Management:** Role-based access control
- **Export Capabilities:** PDF, Excel, CSV formats

### Database Schema
- **Members:** Personal information and family relationships
- **Contributions:** Tithe, offering, project contributions
- **Events:** Church activities and attendance tracking
- **Users:** Authentication and role management
- **Reports:** Generated report history and templates

### Security Features
- **Authentication:** Secure login with Laravel Sanctum
- **Authorization:** Role-based permissions
- **Data Protection:** Encrypted sensitive information
- **Input Validation:** Comprehensive form validation
- **CSRF Protection:** Cross-site request forgery prevention

## 🛠️ Development Information

### Technology Stack
- **Backend:** Laravel 11.x (PHP 8.2+)
- **Frontend:** React with TypeScript
- **Database:** MySQL 8.0+  
- **Build Tools:** Vite, Tailwind CSS
- **Authentication:** Laravel Sanctum

### System Requirements
- **PHP:** 8.2 or higher
- **MySQL:** 8.0 or higher
- **Node.js:** 18+ (for build process)
- **Extensions:** BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML

### File Structure
```
parish-management-system/
├── app/                 # Laravel application code
├── config/             # Configuration files
├── database/           # Migrations and seeders
├── public/             # Web-accessible files
├── resources/          # Views, assets, language files  
├── routes/             # Route definitions
├── storage/            # Generated files, logs, cache
├── vendor/             # PHP dependencies
├── .env.example        # Environment template
├── artisan             # Laravel command-line interface
├── composer.json       # PHP dependencies
└── package.json        # Node.js dependencies
```

## 📞 Support

### Getting Help
1. **Check logs:** `storage/logs/laravel.log`
2. **Verify environment:** Run `php artisan about`
3. **Test database:** Run `php artisan tinker` then `DB::connection()->getPdo();`
4. **Check permissions:** Ensure storage and bootstrap/cache are writable

### Contact Information
- **Repository:** https://github.com/JosephNjoroge8/parish-management-system
- **Documentation:** Available in repository
- **Issues:** Use GitHub Issues for bug reports

---

**✅ System Status:** Production Ready  
**🔒 Security:** Hardened for Production  
**📈 Performance:** Optimized  
**🚀 Deployment:** Automated via cPanel