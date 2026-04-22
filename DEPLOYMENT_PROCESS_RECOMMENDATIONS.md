# 🔄 DEPLOYMENT PROCESS & BEST PRACTICES RECOMMENDATIONS

**For**: Parish Management System on HostPinacle cPanel  
**Prepared**: April 22, 2026  
**Goal**: Prevent production issues and ensure reliable deployments

---

## 📋 EXECUTIVE SUMMARY

Your system is configured for **automated deployment via GitHub Actions**, which is excellent. However, the current setup has several **critical gaps** that caused the blank page issue.

### What Went Wrong
1. ❌ Build assets (`public/build/`) not reliably deployed
2. ❌ No pre-deployment verification
3. ❌ Unclear deployment path / credentials
4. ❌ No health checks after deployment
5. ❌ Missing .env validation
6. ❌ No rollback strategy

### What We're Fixing
1. ✅ Complete build asset deployment
2. ✅ Multi-stage verification
3. ✅ Clear deployment documentation
4. ✅ Automated health checks
5. ✅ Environment validation
6. ✅ Rollback procedures

---

## 🏗️ RECOMMENDED DEPLOYMENT ARCHITECTURE

### Current State
```
Your Code → GitHub → GitHub Actions → cPanel Server
                            ↓
                    Build tests/assets
                    Deploy via FTP/SSH
                            ↓
                    Blank page ❌
```

### Recommended State
```
Your Code → GitHub → GitHub Actions → cPanel Server
   ↓                        ↓                ↓
✅ Pre-flight       ✅ Build + Test    ✅ Health Check
   Checks          ✅ Assets Build    ✅ Monitoring
   Linting         ✅ Verification    ✅ Rollback Ready
   Assets          ✅ Deploy          ✅ Alerts
   Database        ✅ Post-Deploy     ✅ Logs
   Config             Checks
```

---

## 🚀 RECOMMENDED DEPLOYMENT PROCESS (Step-by-Step)

### PHASE 1: LOCAL DEVELOPMENT (Your Machine)

#### 1.1 Before Committing
```bash
# 1. Make your code changes
# ... edit files ...

# 2. Ensure tests pass
php artisan test

# 3. Format code
vendor/bin/pint --dirty

# 4. Build assets
npm run build

# 5. Verify build
if [ ! -f "public/build/manifest.json" ]; then
    echo "Build failed!"; 
    exit 1; 
fi

# 6. Clean test artifacts
php artisan cache:clear
rm -rf storage/logs/laravel.log*

# 7. Commit with meaningful message
git add -A
git commit -m "Feature: [description] - Ready for production"
git push origin Main
```

#### 1.2 Environment Considerations
```bash
# Local: Keep .env.example updated
# Server: Uses .env.production or specific .env

# Before each push, verify:
cat .env | grep -E "APP_ENV|APP_DEBUG|APP_URL"
# Local should show: local, true, http://localhost:8000
```

---

### PHASE 2: CONTINUOUS INTEGRATION (GitHub Actions)

#### 2.1 What Happens Automatically
```
1. Tests Run (5 min)
   - Starts MySQL test database
   - Runs PHP tests
   - If fails → STOP, notify developer

2. Assets Build (3 min)
   - npm install
   - npm run build
   - Verify manifest.json created
   - If fails → STOP, notify developer

3. Code Quality (2 min)
   - Pint checks
   - If fails → STOP, notify developer

4. Deploy (if all pass) (10 min)
   - Download built assets
   - Connect via FTP/SSH to cPanel
   - Upload all files
   - Run post-deployment commands
   - Run health check
   - If fails → ROLLBACK, notify developer
```

#### 2.2 GitHub Actions Workflow Status
Check status: `GitHub repo → Actions tab`

**Expected results**:
- ✅ All tests pass
- ✅ Build succeeds
- ✅ Assets have manifest.json
- ✅ Deploy completes
- ✅ Health check passes

**If any fail**:
- Click that step to see error
- Fix locally
- Commit and push again

---

### PHASE 3: DEPLOYMENT TO PRODUCTION (Automatic via cPanel)

#### 3.1 .cpanel.yml Orchestration

The `.cpanel.yml` file runs these steps automatically:

```bash
1. Copy files to /home2/shemidig/parish_system/
2. Install Composer (production): composer install --no-dev
3. Install Node & Build Assets: npm ci && npm run build
4. Set Permissions: chmod -R 755 for all
5. Clear Caches: config:clear, cache:clear, etc.
6. Run Migrations: php artisan migrate --force
7. Optimize: config:cache, route:cache, view:cache
```

#### 3.2 Critical Environment Configuration

Must be set in production `.env`:

```env
# Core
APP_ENV=production
APP_DEBUG=false
APP_URL=https://parish.quovadisyouthhub.org
ASSET_URL=https://parish.quovadisyouthhub.org

# Database (from cPanel MySQL Databases)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=shemidig_parish_db
DB_USERNAME=shemidig_parishuser
DB_PASSWORD=<strong_password>

# Security
BCRYPT_ROUNDS=12
APP_KEY=base64:xxxxx

# Session & Cache (production optimized)
SESSION_DRIVER=database
CACHE_STORE=database
CACHE_PREFIX=parish_prod_

# Queue
QUEUE_CONNECTION=database

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

#### 3.3 Post-Deployment Verification

The workflow automatically checks:

```bash
1. ✅ HTTP response from site (should be 200)
2. ✅ No PHP errors in logs
3. ✅ Database migrations succeeded
4. ✅ Asset manifest exists and is valid
5. ✅ All storage/bootstrap permissions correct
```

---

## 🔍 DEPLOYMENT VERIFICATION STEPS

### Immediate Verification (Right After Deployment)

```bash
# 1. Check deployment completed
# → GitHub Actions "Deploy" step should show ✅

# 2. Check site is online
curl -I https://parish.quovadisyouthhub.org
# Should return: HTTP/2 200

# 3. Check assets loaded
curl -I https://parish.quovadisyouthhub.org/build/manifest.json
# Should return: HTTP/2 200, Content-Type: application/json

# 4. Browser test
# → Visit https://parish.quovadisyouthhub.org
# → Should load with full styling
# → Press F12 → Console should be clean (no red errors)

# 5. Diagnostic page
# → Visit https://parish.quovadisyouthhub.org/diagnostic.php
# → Should show all green ✅ checkmarks
```

### Deep Verification (If Issues Persist)

```bash
# On production server (SSH):
cd /home2/shemidig/parish_system

# 1. Verify build files
ls -lh public/build/manifest.json

# 2. Verify manifest content
head -20 public/build/manifest.json | python3 -m json.tool

# 3. Count asset files
find public/build/assets -type f | wc -l
# Should be > 50

# 4. Check permissions
ls -ld storage bootstrap/cache
# Should show drwxrwxr-x (775) or similar

# 5. Check database connection
php artisan tinker
> DB::connection()->getPdo();
> exit;

# 6. Check logs
tail -50 storage/logs/laravel.log | grep -i error
```

---

## 🛡️ FAILURE PREVENTION STRATEGY

### Daily/Weekly Checks

```bash
# 1. Monitor GitHub Actions
# → Set up email notifications for failed jobs
# → Check Actions tab daily

# 2. Monitor Application Errors
# → Visit https://parish.quovadisyouthhub.org/diagnostic.php
# → Check for any red X's

# 3. Monitor Server Resources
# → cPanel → System Status
# → Check disk space (need > 1GB free)
# → Check database size
```

### Backup Strategy

```bash
# Automated: cPanel does this
# → cPanel → Backups (usually daily)
# → Verify backup schedule is enabled
# → Test restore process quarterly

# Manual: Before major changes
cd /home2/shemidig
tar -czf parish_backup_$(date +%Y%m%d).tar.gz parish_system/
```

### Rollback Strategy

If deployment breaks production:

```bash
# Option 1: Revert last commit (if issue is in code)
git revert HEAD
git push origin Main
# New deployment will be triggered

# Option 2: Restore from backup (if infrastructure issue)
cd /home2/shemidig
tar -xzf parish_backup_YYYYMMDD.tar.gz
# Replace parish_system with backup version

# Option 3: Manual rollback
cd /home2/shemidig/parish_system
git log --oneline -n 5  # See recent commits
git reset --hard <commit_hash>  # Revert to specific commit
```

---

## 🔧 INFRASTRUCTURE IMPROVEMENTS

### Recommendation 1: Better Asset Management

**Current Issue**: Build files sometimes not uploaded

**Solution**:
```yaml
# In .cpanel.yml, ensure assets are built and not removed:
- cd ${DEPLOYPATH} && npm ci
- cd ${DEPLOYPATH} && npm run build
- cd ${DEPLOYPATH} && find public/build -type f -exec chmod 644 {} \;
```

**Benefit**: Guarantees build files are present on every deployment

---

### Recommendation 2: Health Monitoring

**Current Issue**: No alerts when site goes down

**Solution**: Set up monitoring
```bash
# Option A: Use GitHub Actions (free)
# Create daily workflow that checks site

# Option B: Use external service
# → StatusPage.io
# → Uptime Robot
# → Better Uptime

# Option C: Use cPanel webhooks
# → Custom notification on deployment success/failure
```

**Benefit**: Immediate notification of issues

---

### Recommendation 3: Staged Deployments

**Current Issue**: Deploy directly to production

**Suggested Process**:
```
1. Push to "staging" branch
   ↓ (deploys to staging.domain.com)
2. Test on staging (24 hours)
3. Manually promote to "main"
   ↓ (deploys to production)
```

**Benefit**: Catch issues before users see them

---

### Recommendation 4: Database Backup

**Current**: cPanel automatic backups (good)

**Improve**: Add time-based snapshots
```bash
# In cPanel Automation:
# Daily 2am: mysqldump -u user -p db > /backups/db_$(date).sql
# Keep last 30 days
# Test restore monthly
```

**Benefit**: Faster recovery if database corrupted

---

### Recommendation 5: Log Aggregation

**Current Issue**: Logs only on server, hard to access

**Solution**: 
```bash
# Option A: Use Laravel Telescope (dev only)
# Option B: Use external service (Sentry, LogRocket)
# Option C: Stream logs to cPanel File Manager daily
```

**Benefit**: Easier to track issues historically

---

## 📊 DEPLOYMENT CHECKLIST

### Before Every Deployment (Developer)

- [ ] Local `.env` is correct (APP_ENV=local)
- [ ] Tests pass: `php artisan test`
- [ ] Code formatted: `vendor/bin/pint --dirty`
- [ ] Assets build: `npm run build`
- [ ] manifest.json exists: `ls public/build/manifest.json`
- [ ] No uncommitted changes: `git status` shows clean
- [ ] Meaningful commit message: `git log -1`

### Before Pushing to Main

- [ ] Tested locally: `php artisan serve` works
- [ ] Tested in browser: Page loads, no console errors
- [ ] Feature works as expected
- [ ] No debug code left: `console.log`, `dd()`, etc.
- [ ] Database migrations tested (if applicable)

### After GitHub Actions Completes

- [ ] Actions tab shows ✅ all green
- [ ] Check production: `https://parish.quovadisyouthhub.org`
- [ ] Browser F12 Console: No red errors
- [ ] Test key features: Login, view data, etc.
- [ ] Check diagnostic: `/diagnostic.php` all green

---

## 🚨 TROUBLESHOOTING DECISION TREE

```
Site shows blank page?
├─ Check F12 Console
│  ├─ MIME type error (JS as HTML)?
│  │  └─ Asset loading problem → See "Asset Fix" in PRODUCTION_DIAGNOSTIC_REPORT.md
│  ├─ Other JavaScript error?
│  │  └─ App logic error → Check storage/logs/laravel.log
│  └─ No errors, but blank?
│     └─ CSS/styling issue → Check Network tab, verify CSS loads
│
├─ Check HTTP status
│  ├─ 500 error?
│  │  └─ Laravel error → Check storage/logs/laravel.log
│  ├─ 404 error?
│  │  └─ Routing issue → Verify routes and .htaccess
│  └─ 200 but blank?
│     └─ Asset or rendering issue → Check above
│
└─ Check deployment
   ├─ GitHub Actions failed?
   │  └─ Click step to see error → Fix and retry
   ├─ GitHub Actions passed but site still broken?
   │  └─ Deployment didn't complete → Check cPanel logs
   └─ Everything looks good but still broken?
      └─ Browser cache issue → Hard refresh (Ctrl+Shift+R)
```

---

## 📈 SCALING FOR FUTURE GROWTH

### Current Limits
- Single server: Max ~500 concurrent users
- File-based cache: Slow for high traffic
- Database sessions: Scalable to 1000s users

### For 1000+ Users
1. **Switch to Redis** for caching
   ```env
   CACHE_STORE=redis
   SESSION_DRIVER=redis
   QUEUE_CONNECTION=redis
   ```

2. **Offload static assets**
   - Use CDN (Cloudflare, AWS CloudFront)
   - Set Cache-Control headers

3. **Database optimization**
   - Add indexes on frequently queried columns
   - Consider read replicas for reporting

4. **Async processing**
   - Move heavy operations to queue
   - Use background job processors

### Recommended Tools
- **Monitoring**: New Relic, DataDog, or Sentry
- **CDN**: Cloudflare (free tier available)
- **Backup**: AWS S3, Google Cloud Storage
- **Analytics**: Google Analytics, Plausible

---

## 🎓 CONTINUOUS LEARNING & IMPROVEMENT

### After Each Deployment

1. **Review logs**:
   ```bash
   tail -100 storage/logs/laravel.log
   ```

2. **Check error rates**:
   - Any warnings in logs?
   - Any failed requests?
   - Any slow queries?

3. **User feedback**:
   - Any reported issues?
   - Performance problems?
   - Broken features?

4. **Document lessons**:
   - What went well?
   - What could improve?
   - Update runbooks/guides

---

## 📚 DEPLOYMENT DOCUMENTATION STRUCTURE

Your project now has:

1. **PRODUCTION_DIAGNOSTIC_REPORT.md** ← Start here for issues
2. **DEPLOYMENT_RECOVERY_GUIDE.md** ← Quick fix procedures
3. **DEPLOYMENT_INSTRUCTIONS.md** ← Initial setup
4. **DEPLOYMENT_STRATEGY.md** ← Architecture overview
5. **DEPLOYMENT_PROCESS_RECOMMENDATIONS.md** ← This file

**Keep these updated** as you make infrastructure changes.

---

## ✅ ACTION ITEMS FOR YOU NOW

1. **Immediate** (Next 1 hour):
   - [ ] Read `PRODUCTION_DIAGNOSTIC_REPORT.md`
   - [ ] Follow recovery steps for your situation
   - [ ] Test site is online

2. **Short-term** (Next 1 day):
   - [ ] Verify all GitHub Secrets are correct
   - [ ] Test a new deployment (make small change, push)
   - [ ] Document your HostPinacle access details securely

3. **Medium-term** (Next 1 week):
   - [ ] Set up monitoring/health checks
   - [ ] Create backup verification script
   - [ ] Train team on deployment process

4. **Long-term** (Next 1 month):
   - [ ] Implement staging environment
   - [ ] Set up error tracking (Sentry)
   - [ ] Plan for scale improvements

---

## 📞 SUPPORT & ESCALATION

### Self-Help (Solve in <30 min)
1. Read `PRODUCTION_DIAGNOSTIC_REPORT.md`
2. Run diagnostic scripts
3. Check GitHub Actions logs
4. Check Laravel logs

### HostPinacle Support (If needed)
Ask them to:
1. Verify PHP version is 8.4+
2. Verify Node.js is installed
3. Verify MySQL is running
4. Check disk space (>1GB free)
5. Verify deployment path permissions

### GitHub Support (If CI/CD fails)
Share:
1. Workflow file (`.github/workflows/laravel.yml`)
2. Error from Actions tab
3. Repository structure

---

**Document Version**: 1.0  
**Last Updated**: April 22, 2026  
**Status**: Recommended Implementation  
**Application**: Parish Management System
