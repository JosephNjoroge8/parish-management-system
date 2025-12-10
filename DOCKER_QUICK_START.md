# 🐳 DOCKER QUICK START GUIDE
## Parish Management System - Docker Development Environment

> **⚠️ IMPORTANT:** Docker is for **LOCAL DEVELOPMENT ONLY**. Production uses traditional shared hosting (cPanel/Apache/MySQL). This setup provides a consistent development environment that mirrors production without requiring Docker on the server.

---

## 📋 Prerequisites

Before you begin, ensure you have:

- ✅ **Docker Desktop** installed ([Get Docker](https://docs.docker.com/get-docker/))
- ✅ **Docker Compose** v2.0+ (included with Docker Desktop)
- ✅ **Git** for cloning the repository
- ✅ At least **4GB RAM** available for Docker

---

## 🚀 Quick Start (30 seconds)

### Option 1: Automated Setup (Recommended)

```bash
# Make the setup script executable
chmod +x docker-setup.sh

# Run the automated setup
./docker-setup.sh
```

The script will:
1. ✅ Check Docker installation
2. ✅ Create/update .env file
3. ✅ Build Docker images
4. ✅ Start all services
5. ✅ Run database migrations
6. ✅ Seed initial data

**That's it!** Your application will be running at **http://localhost:8000**

---

### Option 2: Manual Setup

If you prefer to run commands manually:

```bash
# 1. Copy environment file
cp .env.docker .env

# 2. Build Docker images
docker-compose build

# 3. Start all services
docker-compose up -d

# 4. Wait for MySQL (10 seconds)
sleep 10

# 5. Run migrations
docker-compose exec app php artisan migrate

# 6. Seed database (optional)
docker-compose exec app php artisan db:seed

# 7. Link storage
docker-compose exec app php artisan storage:link
```

---

## 🌐 Access Points

Once running, access your application:

| Service | URL | Credentials |
|---------|-----|-------------|
| **Application** | http://localhost:8000 | Register new user |
| **MySQL Database** | localhost:3306 | User: `parish_user`<br>Pass: `parish_secret`<br>DB: `parish_system` |
| **Redis Cache** | localhost:6379 | No password |

---

## 🛠️ Essential Commands

### Container Management

```bash
# Start all services
docker-compose up -d

# Start with live logs
docker-compose up

# Stop all services
docker-compose down

# Stop and remove volumes (CAUTION: Deletes data!)
docker-compose down -v

# View container status
docker-compose ps

# View logs (all services)
docker-compose logs -f

# View logs (specific service)
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f db
```

### Laravel Artisan Commands

```bash
# Access app container shell
docker-compose exec app bash

# Run artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:list
docker-compose exec app php artisan queue:work

# Run tinker
docker-compose exec app php artisan tinker

# Run tests
docker-compose exec app php artisan test
```

### Database Commands

```bash
# Access MySQL shell
docker-compose exec db mysql -u parish_user -pparish_secret parish_system

# Backup database
docker-compose exec db mysqldump -u parish_user -pparish_secret parish_system > backup.sql

# Restore database
docker-compose exec -T db mysql -u parish_user -pparish_secret parish_system < backup.sql

# Fresh migration (CAUTION: Destroys data!)
docker-compose exec app php artisan migrate:fresh --seed
```

### Frontend Development

```bash
# Install npm dependencies
docker-compose exec app npm install

# Run Vite dev server
docker-compose exec app npm run dev

# Build for production
docker-compose exec app npm run build

# Watch mode (recommended for development)
docker-compose exec app npm run dev -- --host
```

### Composer Commands

```bash
# Install dependencies
docker-compose exec app composer install

# Update dependencies
docker-compose exec app composer update

# Require new package
docker-compose exec app composer require vendor/package
```

---

## 🏗️ Docker Architecture

### Services Overview

```
┌─────────────────────────────────────────────┐
│         Nginx (Web Server)                  │
│         Port: 8000 → 80                     │
└────────────────┬────────────────────────────┘
                 │
┌────────────────▼────────────────────────────┐
│         App (PHP-FPM 8.2)                   │
│         - Laravel Application               │
│         - Supervisor (Process Manager)      │
└─────┬──────────────────────────┬────────────┘
      │                          │
┌─────▼────────────┐   ┌─────────▼──────────┐
│  MySQL 8.0       │   │  Redis 6.2         │
│  Port: 3306      │   │  Port: 6379        │
│  - Database      │   │  - Cache           │
│  - Persistent    │   │  - Sessions        │
└──────────────────┘   │  - Queue           │
                       └────────────────────┘

┌──────────────────────────────────────────────┐
│  Queue Worker (Background Jobs)              │
│  - Processes queued jobs                     │
└──────────────────────────────────────────────┘

┌──────────────────────────────────────────────┐
│  Scheduler (Laravel Cron)                    │
│  - Runs scheduled tasks                      │
└──────────────────────────────────────────────┘
```

### Persistent Volumes

- **mysql-data**: Database files (survives container restarts)
- **redis-data**: Redis snapshots (survives container restarts)

---

## 🔧 Development Workflow

### Typical Development Session

```bash
# 1. Start services
docker-compose up -d

# 2. Watch logs (optional)
docker-compose logs -f app

# 3. Run migrations (if needed)
docker-compose exec app php artisan migrate

# 4. Start frontend dev server (in new terminal)
docker-compose exec app npm run dev

# 5. Make code changes in your editor
# Changes auto-reload with Vite

# 6. Run tests before committing
docker-compose exec app php artisan test

# 7. When done, stop services
docker-compose down
```

### Hot Reload Development

For automatic frontend reloading:

```bash
# Terminal 1: Start Docker services
docker-compose up

# Terminal 2: Start Vite dev server
docker-compose exec app npm run dev -- --host
```

Visit http://localhost:8000 and changes will hot-reload!

---

## 🐛 Troubleshooting

### Application won't start

```bash
# Check container status
docker-compose ps

# View logs
docker-compose logs app

# Rebuild images
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Database connection errors

```bash
# Verify MySQL is running
docker-compose ps db

# Check MySQL logs
docker-compose logs db

# Wait for MySQL to be ready
docker-compose exec db mysqladmin ping -h localhost

# Verify credentials in .env
cat .env | grep DB_
```

### Permission errors

```bash
# Fix storage permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Port already in use

```bash
# Find process using port 8000
sudo lsof -i :8000

# Kill the process or change port in docker-compose.yml
# Edit docker-compose.yml nginx ports to "8001:80"
```

### Clear all caches

```bash
docker-compose exec app php artisan optimize:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

### Fresh start (nuclear option)

```bash
# Stop and remove everything
docker-compose down -v

# Remove all images
docker-compose down --rmi all

# Remove build cache
docker builder prune -a

# Start fresh
./docker-setup.sh
```

---

## 🚀 Production Deployment (Shared Hosting)

> **Note:** Production does NOT use Docker. It runs on shared hosting (cPanel) with Apache and MySQL.

### Production Deployment Steps

For production deployment to shared hosting, use the existing guides:

1. **Follow the production guides:**
   - See `PRODUCTION_BLANK_PAGE_SOLUTION.md` for complete deployment steps
   - See `QUICK_FIX_GUIDE.md` for troubleshooting

2. **Build assets locally or in CI/CD:**
   ```bash
   # Build production assets (do this locally or let GitHub Actions do it)
   npm install
   npm run build
   ```

3. **Deploy via Git or FTP:**
   ```bash
   # Push to GitHub (triggers CI/CD)
   git push origin Main
   
   # Or manually upload via FTP/cPanel File Manager
   # Upload everything except: node_modules, .git, storage/logs/*
   ```

4. **On shared hosting (via SSH or cPanel Terminal):**
   ```bash
   cd /home2/shemidig/parish_system
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

### Production Environment

- **Server:** Shared hosting (cPanel)
- **Web Server:** Apache with .htaccess
- **Database:** MySQL (provided by hosting)
- **Cache:** File-based (Redis optional if available)
- **Queue:** Sync or Database (if cron available)

### Production Checklist

- [ ] `.env` has `APP_ENV=production`
- [ ] `.env` has `APP_DEBUG=false`
- [ ] `public/build/` directory has compiled assets
- [ ] `.htaccess` configured for asset serving
- [ ] Database credentials configured
- [ ] Storage permissions set (775)
- [ ] SSL certificate active
- [ ] Backup strategy in place

---

## 📊 Monitoring

### Health Checks

```bash
# Application health
curl http://localhost:8000

# MySQL health
docker-compose exec db mysqladmin ping

# Redis health
docker-compose exec redis redis-cli ping

# Queue worker status
docker-compose exec app php artisan queue:monitor
```

### Resource Usage

```bash
# Container stats
docker stats

# Disk usage
docker system df

# Image sizes
docker images
```

---

## 🔐 Security Best Practices

### Development
- ✅ Use `.env.docker` for local development
- ✅ Never commit `.env` files
- ✅ Use default passwords (they're in `.env.docker`)

### Production
- ✅ Use `.env.production.docker` as template
- ✅ Generate unique `APP_KEY`
- ✅ Use strong passwords (20+ characters)
- ✅ Enable Redis password authentication
- ✅ Configure firewall (allow only 80, 443)
- ✅ Use secrets management (Docker secrets, Vault)
- ✅ Regular security updates
- ✅ Enable SSL/TLS

---

## 📚 Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Laravel Deployment Docs](https://laravel.com/docs/deployment)
- [Docker Compose File Reference](https://docs.docker.com/compose/compose-file/)
- [MySQL Docker Hub](https://hub.docker.com/_/mysql)
- [Redis Docker Hub](https://hub.docker.com/_/redis)

---

## 🆘 Getting Help

If you encounter issues:

1. Check container logs: `docker-compose logs -f`
2. Verify `.env` configuration matches `.env.docker`
3. Ensure ports 8000, 3306, 6379 are available
4. Try fresh setup: `docker-compose down -v && ./docker-setup.sh`

---

## 🎉 Success Criteria

You're all set when:

- ✅ `docker-compose ps` shows all containers "Up"
- ✅ http://localhost:8000 loads the application
- ✅ You can register/login
- ✅ Tests pass: `docker-compose exec app php artisan test`

**Happy coding! 🚀**
