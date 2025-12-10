# 🎯 QUICK REFERENCE CARD

**Parish Management System - Command Cheat Sheet**

---

## 🐳 Docker Development

```bash
# START EVERYTHING (first time)
./docker-setup.sh

# Daily commands
docker-compose up -d              # Start services
docker-compose down               # Stop services
docker-compose ps                 # Check status
docker-compose logs -f app        # Watch logs

# Development
docker-compose exec app bash      # Enter container
docker-compose exec app php artisan test  # Run tests
docker-compose exec app npm run dev       # Frontend dev mode
```

---

## 🛠️ Laravel Commands (in Docker)

```bash
# Artisan
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan tinker
docker-compose exec app php artisan route:list
docker-compose exec app php artisan queue:work

# Cache management
docker-compose exec app php artisan optimize:clear
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Testing
docker-compose exec app php artisan test
docker-compose exec app php artisan test --filter=testName
```

---

## 📦 Frontend Commands (in Docker)

```bash
# Install dependencies
docker-compose exec app npm install

# Development (hot reload)
docker-compose exec app npm run dev

# Build for production
docker-compose exec app npm run build

# Check build
ls -lh public/build/
```

---

## 🗄️ Database Commands (in Docker)

```bash
# Access MySQL
docker-compose exec db mysql -u parish_user -pparish_secret parish_system

# Backup
docker-compose exec db mysqldump -u parish_user -pparish_secret parish_system > backup.sql

# Restore
docker-compose exec -T db mysql -u parish_user -pparish_secret parish_system < backup.sql

# Fresh start (WARNING: Destroys data!)
docker-compose exec app php artisan migrate:fresh --seed
```

---

## 🚀 Production Deployment (Shared Hosting)

```bash
# LOCAL: Build assets
npm run build

# LOCAL: Commit and push
git add public/build
git commit -m "Build production assets"
git push origin Main

# REMOTE: SSH to server
ssh user@host
cd /home2/shemidig/parish_system

# REMOTE: Deploy
git pull origin Main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
chmod -R 755 storage bootstrap/cache
```

---

## 🔍 Troubleshooting

```bash
# Check Docker status
docker --version
docker-compose --version
docker-compose ps

# Restart everything
docker-compose down
docker-compose up -d

# Fresh Docker setup
docker-compose down -v
./docker-setup.sh

# Check production status
curl https://parish.quovadisyouthhub.org

# View production logs (SSH)
tail -f storage/logs/laravel.log
```

---

## ✅ Verification Commands

```bash
# Development ready?
curl http://localhost:8000
docker-compose exec app php artisan test

# Production ready?
./pre-deployment-check.sh
./verify-vite-assets.sh

# CI/CD status
./check-cicd-status.sh
```

---

## 📊 Quick Status Checks

```bash
# Docker services
docker-compose ps

# Disk usage
docker system df

# Container stats
docker stats

# Git status
git status
git log --oneline -5

# Production health (SSH)
php artisan about
php artisan queue:monitor
```

---

## 🎯 Common Workflows

### Morning Startup
```bash
cd /home/joseph/Desktop/parish-management-system
docker-compose up -d
docker-compose logs -f app  # Watch logs (Ctrl+C to exit)
```

### Make Changes
```bash
# Edit code in VS Code
# Changes auto-reload with Vite

# Run tests
docker-compose exec app php artisan test

# Commit
git add .
git commit -m "Your message"
git push origin Main
```

### Deploy to Production
```bash
# Build assets
npm run build

# Pre-deployment check
./pre-deployment-check.sh

# Push (triggers auto-deploy)
git push origin Main

# Verify
./check-cicd-status.sh
```

### End of Day
```bash
docker-compose down  # Stop containers
# Or leave them running for faster next startup
```

---

## 🆘 Emergency Fixes

```bash
# Production blank page?
# See: QUICK_FIX_GUIDE.md

# Docker won't start?
docker-compose down -v
./docker-setup.sh

# Tests failing?
docker-compose exec app php artisan migrate:fresh --seed
docker-compose exec app php artisan test

# Assets not loading?
npm run build
git add public/build
git commit -m "Rebuild assets"
git push origin Main
```

---

## 📚 Documentation

```bash
# Read guides
cat DEPLOYMENT_STRATEGY.md      # Start here
cat DOCKER_QUICK_START.md        # Docker guide
cat QUICK_FIX_GUIDE.md           # Emergency fixes
cat DOCUMENTATION_INDEX.md       # All docs
```

---

## 🔗 URLs

| Environment | URL |
|-------------|-----|
| **Development** | http://localhost:8000 |
| **Production** | https://parish.quovadisyouthhub.org |
| **Repository** | https://github.com/JosephNjoroge8/parish-management-system |

---

## 📞 Access

| Service | Host | Port | User | Password |
|---------|------|------|------|----------|
| **Dev MySQL** | localhost | 3306 | parish_user | parish_secret |
| **Dev Redis** | localhost | 6379 | - | - |
| **Dev App** | localhost | 8000 | - | - |

---

## 🎨 Aliases (Optional)

Add to `~/.zshrc` for shortcuts:

```bash
# Parish Management System
alias parish-start='cd ~/Desktop/parish-management-system && docker-compose up -d'
alias parish-stop='cd ~/Desktop/parish-management-system && docker-compose down'
alias parish-test='cd ~/Desktop/parish-management-system && docker-compose exec app php artisan test'
alias parish-shell='cd ~/Desktop/parish-management-system && docker-compose exec app bash'
alias parish-artisan='cd ~/Desktop/parish-management-system && docker-compose exec app php artisan'
alias parish-npm='cd ~/Desktop/parish-management-system && docker-compose exec app npm'
alias parish-build='cd ~/Desktop/parish-management-system && npm run build'
alias parish-deploy='cd ~/Desktop/parish-management-system && npm run build && git add public/build && git commit -m "Build assets" && git push'
```

Then:
```bash
source ~/.zshrc

# Now you can use:
parish-start
parish-test
parish-artisan migrate
parish-build
```

---

**Print this card or keep it handy! 📌**
