# 🚀 DEPLOYMENT STRATEGY

## Overview

This project uses **different environments for development and production**:

- **Local Development:** Docker containers (MySQL, Redis, Nginx)
- **Production:** Shared hosting with cPanel (Apache, MySQL)

---

## 📍 Environment Details

### Local Development (Docker)

**Purpose:** Consistent development environment that mirrors production setup

**Stack:**
- Docker + Docker Compose
- Nginx web server (port 8000)
- PHP 8.2-FPM
- MySQL 8.0 (containerized)
- Redis 6.2 (cache/queue)
- Queue worker + Scheduler

**Why Docker for Development?**
- ✅ Consistent environment across all developers
- ✅ No need to install MySQL/Redis locally
- ✅ Easy to start/stop services
- ✅ Matches production database (MySQL vs SQLite)
- ✅ Queue and scheduler work like production

**Setup:**
```bash
./docker-setup.sh
# Access at http://localhost:8000
```

---

### Production (Shared Hosting)

**Purpose:** Live application accessible to users

**Stack:**
- Shared hosting (cPanel)
- Apache web server with .htaccess
- PHP 8.4.11 (hosting-provided)
- MySQL database (hosting-provided)
- File-based cache
- URL: https://parish.quovadisyouthhub.org

**Why NOT Docker for Production?**
- ❌ Shared hosting doesn't support Docker
- ✅ Hosting already provides Apache + MySQL + PHP
- ✅ No need for containerization complexity
- ✅ Cost-effective
- ✅ Easy to manage via cPanel

**Deployment:**
```bash
# Build assets locally
npm run build

# Push to GitHub (triggers CI/CD)
git push origin Main

# On server via SSH
git pull && composer install --no-dev
php artisan migrate --force
php artisan optimize
```

---

### Testing (CI/CD)

**Purpose:** Automated testing in GitHub Actions

**Stack:**
- GitHub Actions runners
- PHP 8.2
- MySQL test database
- Same database engine as production

**Why MySQL for Tests?**
- ✅ Same database engine as production and development
- ✅ Catches MySQL-specific issues
- ✅ Production-like behavior
- ✅ Accurate integration testing

---

## 🔄 Workflow

```
┌─────────────────────────────────────────────────────┐
│  DEVELOPER MACHINE (You)                            │
│                                                     │
│  1. Write code                                      │
│  2. Test with Docker (MySQL)                        │
│     → docker-compose up -d                          │
│     → http://localhost:8000                         │
│  3. Run tests (MySQL)                               │
│     → docker-compose exec app php artisan test      │
│  4. Commit & Push                                   │
│     → git push origin Main                          │
└─────────────────┬───────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────┐
│  GITHUB (CI/CD Pipeline)                            │
│                                                     │
│  1. Run tests (MySQL test database)                 │
│  2. Build frontend assets (Vite)                    │
│  3. Code quality checks (Pint)                      │
│  4. Deploy to production (if all pass)              │
└─────────────────┬───────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────┐
│  PRODUCTION SERVER (Shared Hosting)                 │
│                                                     │
│  1. Pull latest code                                │
│  2. Install Composer dependencies                   │
│  3. Run migrations                                  │
│  4. Cache configs/routes/views                      │
│  5. Live at https://parish.quovadisyouthhub.org     │
└─────────────────────────────────────────────────────┘
```

---

## 🎯 Quick Reference

### I want to...

**Develop locally**
```bash
./docker-setup.sh
# http://localhost:8000
```

**Run tests**
```bash
docker-compose exec app php artisan test
```

**Build for production**
```bash
npm run build
git add public/build
git commit -m "Build assets"
```

**Deploy to production**
```bash
git push origin Main
# GitHub Actions will deploy automatically
```

**SSH to production**
```bash
ssh username@host
cd /home2/shemidig/parish_system
```

---

## 📊 Environment Comparison

| Feature | Development (Docker) | Production (Shared) | Testing (CI/CD) |
|---------|---------------------|---------------------|-----------------|
| **Web Server** | Nginx | Apache | N/A |
| **PHP Version** | 8.2-FPM | 8.4.11 | 8.2 |
| **Database** | MySQL 8.0 | MySQL (hosting) | MySQL (testing) |
| **Cache** | Redis | File | Array |
| **Queue** | Redis | Sync/Database | Sync |
| **URL** | localhost:8000 | parish.quovadisyouthhub.org | N/A |
| **Setup** | `./docker-setup.sh` | cPanel + Git | Automatic |
| **Start Time** | 30 seconds | Always running | On commit |

---

## 🔐 Environment Files

### `.env.docker` (Development)
- Use `DB_HOST=db` (Docker container)
- MySQL: `parish_system` / `parish_user` / `parish_secret`
- Redis: `REDIS_HOST=redis`
- Debug enabled
- Local URL

### `.env` (Production - on server)
- Use `DB_HOST=localhost` (shared hosting)
- MySQL credentials from cPanel
- File-based cache
- Debug disabled
- Production URL

### `phpunit.xml` (Testing)
- MySQL test database (parish_testing)
- Array cache driver
- Sync queue
- Production-like testing

---

## 🚦 Deployment Checklist

### Before Pushing to Production

- [ ] All tests passing locally: `docker-compose exec app php artisan test`
- [ ] Frontend built: `npm run build`
- [ ] No debug code left in
- [ ] `.env` not committed (in `.gitignore`)
- [ ] Assets in `public/build/` committed

### On Production Server

- [ ] `.env` has production credentials
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] Storage writable: `chmod -R 775 storage bootstrap/cache`
- [ ] Configs cached: `php artisan optimize`
- [ ] SSL certificate active

---

## 🆘 Troubleshooting

### "It works in Docker but not production"

**Check:**
1. Assets built? `ls public/build/manifest.json`
2. `.htaccess` correct? See `PRODUCTION_BLANK_PAGE_SOLUTION.md`
3. Storage writable? `ls -la storage/`
4. Configs cached? `php artisan config:clear && php artisan config:cache`

### "Tests pass but production fails"

**Common causes:**
- SQLite ≠ MySQL behavior (use Docker for testing MySQL)
- File permissions on shared hosting
- PHP version differences (8.2 vs 8.4)
- Missing environment variables

### "Docker containers won't start"

**Solutions:**
- Check ports: `docker-compose down && docker-compose up -d`
- Rebuild: `docker-compose build --no-cache`
- Fresh start: `docker-compose down -v && ./docker-setup.sh`

---

## 📚 Related Documentation

- **Docker Development:** `DOCKER_QUICK_START.md`
- **Production Deployment:** `PRODUCTION_BLANK_PAGE_SOLUTION.md`
- **Emergency Fixes:** `QUICK_FIX_GUIDE.md`
- **All Documentation:** `DOCUMENTATION_INDEX.md`

---

## 💡 Best Practices

### Development
1. Always use Docker for consistency
2. Test with MySQL (same as production)
3. Run tests before committing
4. Keep containers running during development

### Production
1. Never deploy directly without testing
2. Always build assets before pushing
3. Use GitHub Actions for deployment
4. Keep `.env` secure (never commit)
5. Monitor logs: `storage/logs/laravel.log`

### Testing
1. Tests use MySQL for production parity
2. Run full test suite before pushing
3. Check CI/CD status after push
4. Fix failed tests immediately

---

## 🎉 Summary

- **Development = Docker** (consistent, powerful, MySQL + Redis)
- **Production = Shared Hosting** (cost-effective, Apache + MySQL)
- **Testing = MySQL** (production-like, accurate)

This strategy gives you:
- ✅ Best development experience (Docker)
- ✅ Cost-effective production (shared hosting)
- ✅ Production-like testing (MySQL everywhere)
- ✅ Consistent deployments (CI/CD)

**Questions?** Check `DOCUMENTATION_INDEX.md` for all guides.
