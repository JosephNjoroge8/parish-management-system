# 🚀 CI/CD Quick Start Guide

## Overview
This is a condensed version of the complete setup guide. For detailed instructions, see [CICD_SETUP_GUIDE.md](CICD_SETUP_GUIDE.md).

---

## Prerequisites
- ✅ cPanel hosting account
- ✅ GitHub repository access
- ✅ PHP 8.2+, MySQL, Git, Composer, NPM on server
- ✅ 30 minutes for setup

---

## Quick Setup (3 Steps)

### Step 1: Configure Files (5 minutes)

Run the setup script:
```bash
chmod +x setup-webhook.sh
bash setup-webhook.sh
```

Or manually update these values in:
- `public/webhook.php` (lines 24, 27, 30)
- `public/deploy.php` (lines 20, 21, 29)
- `.cpanel.yml` (lines 9, 10)

**Required values:**
- Webhook secret: `openssl rand -hex 32`
- Deployment path: `/home2/YOUR_USERNAME/parish_system`
- PHP version: `82` (or your version)

Commit changes:
```bash
git add .
git commit -m "Configure CI/CD pipeline"
git push origin Main
```

### Step 2: Setup cPanel (15 minutes)

1. **Create database** (cPanel → MySQL Databases)
   - Database: `username_parish`
   - User: `username_parish_user`
   - Password: Strong password
   - Grant ALL PRIVILEGES

2. **Configure PHP** (cPanel → Select PHP Version)
   - Version: PHP 8.2+
   - Extensions: Enable PDO, pdo_mysql, mbstring, openssl, etc.
   - Options: `memory_limit = 512M`, `max_execution_time = 300`

3. **Upload files** (cPanel → File Manager)
   - Upload `public/webhook.php` to your public directory
   - Upload `public/deploy.php` to your public directory
   - Set permissions: 644

4. **Clone repository** (cPanel → Git Version Control or Terminal)
   ```bash
   git clone -b Main https://github.com/JosephNjoroge8/parish-management-system.git /home2/YOUR_USERNAME/parish_system
   ```

5. **Create .env file**
   ```bash
   cd /home2/YOUR_USERNAME/parish_system
   cp .env.production .env
   # Edit .env with your database credentials
   ```

6. **Run initial setup**
   ```bash
   /usr/local/bin/ea-php82 /opt/cpanel/composer/bin/composer install --no-dev
   /usr/local/bin/ea-php82 artisan key:generate
   /usr/local/bin/ea-php82 artisan migrate --force
   npm install && npm run build
   /usr/local/bin/ea-php82 artisan optimize
   ```

7. **Set permissions**
   ```bash
   chmod -R 755 /home2/YOUR_USERNAME/parish_system
   chmod -R 775 storage bootstrap/cache
   ```

8. **Configure domain**
   - Point domain/subdomain to `parish_system/public` directory

### Step 3: Configure GitHub Webhook (5 minutes)

1. Go to: `https://github.com/JosephNjoroge8/parish-management-system/settings/hooks`

2. Click **Add webhook**

3. Configure:
   - **Payload URL**: `https://parish.quovadisyouthhub.org/webhook.php`
   - **Content type**: `application/json`
   - **Secret**: Your webhook secret from Step 1
   - **Events**: Just the push event
   - **Active**: ✅ Checked

4. Click **Add webhook**

5. Verify: Green ✅ checkmark appears

---

## Test Deployment

### Quick Test
```bash
echo "// CI/CD test" >> README.md
git add README.md
git commit -m "Test CI/CD pipeline"
git push origin Main
```

### Monitor Progress
1. **GitHub Actions**: https://github.com/JosephNjoroge8/parish-management-system/actions
2. **Webhook**: GitHub → Settings → Webhooks → Recent Deliveries
3. **Logs**: 
   ```bash
   tail -f /home2/YOUR_USERNAME/parish_system/storage/logs/deployment.log
   ```

### Expected Timeline
- GitHub Actions: 5-8 minutes
- Deployment: 3-7 minutes
- **Total**: 8-15 minutes

---

## Verify Deployment

After pushing to Main:

1. ✅ GitHub Actions shows green checkmarks
2. ✅ Webhook Recent Deliveries shows 200 response
3. ✅ Site loads at your domain
4. ✅ Changes appear on production
5. ✅ No errors in browser console

---

## Common Issues & Quick Fixes

### Webhook 403 Error
```apache
# Add to public/.htaccess
<Files "webhook.php">
    Order Deny,Allow
    Deny from all
    Allow from all  # Temporary for testing
</Files>
```

### Deployment 500 Error
```bash
# Check logs
tail -f storage/logs/webhook-errors.log
tail -f storage/logs/deployment.log

# Check permissions
chmod 644 public/webhook.php
chmod 644 public/deploy.php
```

### Assets Not Loading
```bash
# Rebuild assets manually
cd /home2/YOUR_USERNAME/parish_system
npm install
npm run build
php artisan optimize:clear
php artisan optimize
```

### Database Connection Failed
```bash
# Verify credentials in .env
cat .env | grep DB_

# Test connection
php artisan tinker
>>> DB::connection()->getPdo();
```

---

## Important Files

| File | Purpose | Location |
|------|---------|----------|
| `webhook.php` | Receives GitHub webhooks | `public/` |
| `deploy.php` | Executes deployment | `public/` |
| `.env` | Production config | Root (not in Git) |
| `.cpanel.yml` | cPanel Git deployment | Root |
| `laravel.yml` | GitHub Actions workflow | `.github/workflows/` |

---

## Daily Operations

### Push Changes
```bash
git add .
git commit -m "Your changes"
git push origin Main
# Automatic deployment starts
```

### Manual Deployment
```bash
# Via cPanel Git Version Control
# Click "Pull or Deploy"

# Or directly
/usr/local/bin/ea-php82 /home2/YOUR_USERNAME/parish_system/public/deploy.php
```

### Rollback
```bash
cd /home2/YOUR_USERNAME/parish_system
tar -xzf backups/backup_LATEST.tar.gz
php artisan optimize
```

### View Logs
```bash
# Deployment logs
tail -f storage/logs/deployment.log

# Webhook logs
tail -f storage/logs/webhook.log

# Application errors
tail -f storage/logs/laravel.log
```

---

## Security Checklist

After setup, ensure:
- [ ] Webhook secret is strong (32+ characters)
- [ ] `.env` file is NOT in Git
- [ ] `APP_DEBUG=false` in production
- [ ] Database password is strong
- [ ] HTTPS is enabled and forced
- [ ] File permissions are correct (755/644)
- [ ] Email notifications configured (optional)

---

## Next Steps

1. ✅ Complete setup following steps above
2. ✅ Test with a small code change
3. ✅ Monitor first deployment in logs
4. ✅ Verify site works correctly
5. ✅ Set up monitoring (UptimeRobot, etc.)
6. ✅ Schedule regular maintenance
7. ✅ Read full guide: [CICD_SETUP_GUIDE.md](CICD_SETUP_GUIDE.md)

---

## Getting Help

- **Full Documentation**: [CICD_SETUP_GUIDE.md](CICD_SETUP_GUIDE.md)
- **Troubleshooting**: See full guide troubleshooting section
- **Logs**: Always check deployment and webhook logs first

---

## Workflow Summary

```
Developer Push → GitHub Actions (test/build) → Webhook → Deploy Script → Production ✅
     ↓                    ↓                        ↓           ↓              ↓
  Main Branch      Tests Pass (2-3min)      Verified     Git Pull      Site Updated
                   Build Assets (3-5min)    Signature    Install Deps   (8-15min total)
                                                         Run Migrations
                                                         Optimize
```

---

**That's it!** You now have a fully automated CI/CD pipeline. Every push to Main automatically deploys to production with testing, building, and rollback capabilities.

For detailed explanations, advanced configurations, and troubleshooting, see the [complete guide](CICD_SETUP_GUIDE.md).
