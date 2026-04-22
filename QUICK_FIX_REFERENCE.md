# 🎯 PARISH SYSTEM - QUICK REFERENCE CARD

**Print this or bookmark for quick access**

---

## 🚨 SYSTEM IS DOWN? DO THIS NOW

### Step 1: Determine Issue Type
```bash
# In browser, check F12 Console
# ↑ MIME type error (text/html) → Go to "ASSET FIX"
# ↑ JavaScript error → Go to "ERROR FIX"  
# ↑ No error, blank page → Go to "DIAGNOSTIC"
```

### Step 2: Quick Fix (Pick One)

**If you have SSH access:**
```bash
ssh username@hostipinacle.com
cd /home2/shemidig/parish_system
rm -rf public/build && npm run build
chmod -R 755 public/build storage bootstrap/cache
php artisan cache:clear
echo "✅ Done - site should be online in 30 seconds"
```

**If no SSH (Git + cPanel Terminal):**
```bash
# Local machine:
rm -rf public/build && npm run build
git add public/build && git commit -m "Rebuild assets" && git push

# On server or via cPanel Terminal:
cd /home2/shemidig/parish_system
php artisan cache:clear
```

**If Git doesn't work (Upload manually):**
```
1. cPanel → File Manager
2. Navigate to /public/ → Delete 'build' folder
3. Upload your local public/build folder
4. Done
```

---

## 🔍 ASSET FIX (Most Common Issue)

**Error**: "Failed to load module script: Expected... but... text/html"

```bash
# Check if files exist
cd /home2/shemidig/parish_system
ls -la public/build/manifest.json

# If missing or empty:
npm run build
chmod -R 755 public/build

# Clear caches
php artisan config:clear
php artisan cache:clear

# Verify
cat public/build/manifest.json | head -5  # Should show JSON
curl -I https://parish.quovadisyouthhub.org/build/manifest.json  # Should show 200
```

---

## 🐛 ERROR FIX (JavaScript/App Errors)

```bash
# 1. Check Laravel logs
tail -50 storage/logs/laravel.log | grep ERROR

# 2. Check specific error
tail -20 storage/logs/laravel.log

# 3. If database error:
grep DB_ .env  # Verify credentials
php artisan tinker
> DB::connection()->getPdo();  # Test connection

# 4. If authentication error:
php artisan cache:clear
php artisan config:clear
```

---

## 🔧 DIAGNOSTIC PAGE

**Visit**: `https://parish.quovadisyouthhub.org/diagnostic.php`

Should show ✅ for all:
- ✅ Vite manifest found
- ✅ Build assets exist
- ✅ Permissions correct
- ✅ Database connected
- ✅ Cache working

If any ❌ appear, that's the issue. Fix:
```bash
# Permissions issue:
chmod -R 755 storage bootstrap/cache public/build

# Database issue:
grep DB_ .env  # Verify values
php artisan migrate --force

# Cache issue:
php artisan cache:clear --all
```

---

## 🚀 DEPLOYMENT COMMANDS

### Local (Before pushing)
```bash
npm run build                    # Build assets
php artisan test                 # Run tests
vendor/bin/pint --dirty          # Format code
git add -A && git commit -m "msg" && git push origin Main
```

### Production (After GitHub Actions)
```bash
cd /home2/shemidig/parish_system
php artisan cache:clear         # Clear caches
php artisan migrate --force     # Run migrations
chmod -R 755 storage bootstrap  # Fix permissions
php artisan up                  # Bring site online
```

---

## 📊 ESSENTIAL COMMANDS

```bash
# Check status
php artisan status              # App status
php artisan tinker              # Debug shell
php artisan migrate --pretend   # See migrations without running

# Fix issues
php artisan cache:clear --all   # Clear everything
php artisan config:cache        # Cache config (production)
php artisan storage:link        # Link storage directory
php artisan queue:restart       # Restart queue

# Database
php artisan migrate --force     # Run migrations
php artisan migrate:rollback    # Undo migrations
php artisan db:seed             # Seed database

# Logs
tail -50 storage/logs/laravel.log
grep ERROR storage/logs/laravel.log | tail -20

# Permissions
chmod -R 755 storage bootstrap/cache
chmod -R 777 storage/logs
```

---

## 🔗 IMPORTANT PATHS

```
Application Root:    /home2/shemidig/parish_system
Public Folder:       /home2/shemidig/parish_system/public
Build Assets:        /home2/shemidig/parish_system/public/build
Storage:             /home2/shemidig/parish_system/storage
Logs:                /home2/shemidig/parish_system/storage/logs
Environment:         /home2/shemidig/parish_system/.env
Database Config:     .env (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)
```

---

## 🌐 IMPORTANT URLs

```
Main Site:           https://parish.quovadisyouthhub.org
Diagnostic:          https://parish.quovadisyouthhub.org/diagnostic.php
Manifest:            https://parish.quovadisyouthhub.org/build/manifest.json
GitHub Repo:         https://github.com/JosephNjoroge8/parish-management-system
GitHub Actions:      GitHub → Your Repo → Actions tab
cPanel:              https://hostipinacle.com:2083 or /cpanel
```

---

## 🔐 CRITICAL CONFIG VALUES

**Must be in production .env**:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://parish.quovadisyouthhub.org
ASSET_URL=https://parish.quovadisyouthhub.org

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<cpanel_db_name>
DB_USERNAME=<cpanel_db_user>
DB_PASSWORD=<strong_password>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

---

## ✅ VERIFICATION CHECKLIST

After any deployment:
- [ ] Site loads: https://parish.quovadisyouthhub.org
- [ ] No console errors: F12 → Console (should be clean)
- [ ] Diagnostic passes: /diagnostic.php shows all ✅
- [ ] Can login: Try logging in if applicable
- [ ] Can view data: Check main features work
- [ ] Logs clean: `tail storage/logs/laravel.log` shows no errors

---

## 📞 COMMON ISSUES QUICK FIX

| Issue | Command | Next Step |
|-------|---------|-----------|
| Blank page | `npm run build` | Check build files exist |
| 500 error | `tail storage/logs/laravel.log` | Read error message |
| Database error | `grep DB_ .env` | Verify credentials in cPanel |
| Permission denied | `chmod -R 755 storage` | Clear caches |
| Assets not loading | `chmod -R 755 public/build` | Hard refresh F5 |
| Slow site | `php artisan cache:clear --all` | Optimize after |

---

## 🛑 WHEN TO ESCALATE

Contact HostPinacle if:
- Database won't connect (even with correct credentials)
- Node.js/npm not found
- Disk space showing < 100MB
- PHP version not 8.4+
- Can't SSH but need emergency access

Contact GitHub Support if:
- GitHub Actions workflow fails
- FTP/SSH deployment can't connect
- Secrets not working

---

## 📖 FULL DOCUMENTATION

For detailed info, see:
- **PRODUCTION_DIAGNOSTIC_REPORT.md** - Complete troubleshooting
- **DEPLOYMENT_RECOVERY_GUIDE.md** - Step-by-step recovery
- **DEPLOYMENT_PROCESS_RECOMMENDATIONS.md** - Best practices
- **DEPLOYMENT_INSTRUCTIONS.md** - Initial setup

---

## 🕐 DEPLOYMENT TIMES

- Local build: ~1-2 min
- GitHub Actions run: ~15-20 min
- cPanel deployment: ~5-10 min
- Total: ~30 min from push to live

---

**Keep this card handy!**  
**Updated**: April 22, 2026
