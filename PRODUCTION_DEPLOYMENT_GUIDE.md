# =============================================================================
# PARISH MANAGEMENT SYSTEM - PRODUCTION DEPLOYMENT GUIDE
# =============================================================================
# Complete guide to deploy the Parish Management System to production
# =============================================================================

## 🚀 Production Deployment Checklist

### Prerequisites
- [ ] Ubuntu/Debian server with root access
- [ ] Domain name pointed to your server
- [ ] SSL certificate (Let's Encrypt recommended)
- [ ] Basic server security (firewall, SSH keys)

### Phase 1: Server Preparation

1. **Update System**
```bash
sudo apt update && sudo apt upgrade -y
```

2. **Install Required Packages**
```bash
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-sqlite3 php8.2-curl \
    php8.2-zip php8.2-gd php8.2-mbstring php8.2-xml php8.2-intl php8.2-bcmath \
    nginx mysql-server redis-server nodejs npm composer git unzip curl
```

3. **Configure MySQL (if using MySQL)**
```bash
sudo mysql_secure_installation
sudo mysql -e "CREATE DATABASE parish_system_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'parish_user'@'localhost' IDENTIFIED BY 'SECURE_PASSWORD_HERE';"
sudo mysql -e "GRANT ALL PRIVILEGES ON parish_system_db.* TO 'parish_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### Phase 2: Application Deployment

1. **Clone Repository**
```bash
sudo mkdir -p /var/www/parish-system
cd /var/www/parish-system
sudo git clone https://github.com/JosephNjoroge8/parish-management-system.git .
```

2. **Set Permissions**
```bash
sudo chown -R www-data:www-data /var/www/parish-system
sudo chmod -R 755 /var/www/parish-system
```

3. **Run Deployment Script**
```bash
# Make script executable
chmod +x deploy-production-complete.sh

# Run deployment (as root)
sudo ./deploy-production-complete.sh
```

### Phase 3: Configuration

1. **Configure Environment**
```bash
# Copy production template
cp .env.production.template .env

# Edit with your settings
nano .env
```

2. **Configure Database Connection**
```bash
# For MySQL
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=parish_system_db
DB_USERNAME=parish_user
DB_PASSWORD=your_secure_password

# For SQLite (simpler option)
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/parish-system/database/database.sqlite
```

3. **Generate Application Key**
```bash
php artisan key:generate --force
```

### Phase 4: Web Server Setup

1. **Configure Nginx**
```bash
# Copy configuration
sudo cp nginx-production.conf /etc/nginx/sites-available/parish-system

# Update domain name in config
sudo sed -i 's/your-domain.com/actual-domain.com/g' /etc/nginx/sites-available/parish-system

# Enable site
sudo ln -s /etc/nginx/sites-available/parish-system /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Test and reload
sudo nginx -t
sudo systemctl reload nginx
```

2. **Configure PHP-FPM**
```bash
# Copy optimized configuration
sudo cp php-fpm-production.conf /etc/php/8.2/fpm/pool.d/parish-system.conf

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

### Phase 5: SSL Certificate

1. **Install Certbot**
```bash
sudo apt install certbot python3-certbot-nginx
```

2. **Obtain Certificate**
```bash
sudo certbot --nginx -d your-domain.com
```

3. **Test Auto-renewal**
```bash
sudo certbot renew --dry-run
```

### Phase 6: Optimization

1. **Run Optimization Script**
```bash
chmod +x optimize-for-production.sh
./optimize-for-production.sh
```

2. **Configure Caching**
```bash
# Enable Redis
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Update .env for Redis caching
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Phase 7: Security Hardening

1. **Firewall Configuration**
```bash
sudo ufw allow ssh
sudo ufw allow http
sudo ufw allow https
sudo ufw enable
```

2. **Fail2Ban (Optional)**
```bash
sudo apt install fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

3. **Secure File Permissions**
```bash
# Application files
sudo find /var/www/parish-system -type f -exec chmod 644 {} \;
sudo find /var/www/parish-system -type d -exec chmod 755 {} \;

# Writable directories
sudo chmod -R 775 /var/www/parish-system/storage
sudo chmod -R 775 /var/www/parish-system/bootstrap/cache

# Environment file
sudo chmod 600 /var/www/parish-system/.env
```

### Phase 8: Monitoring & Maintenance

1. **Log Rotation**
```bash
# Already configured in deployment script
cat /etc/logrotate.d/parish-system
```

2. **Cron Jobs**
```bash
# Add to crontab
sudo crontab -e

# Add these lines:
0 2 * * * cd /var/www/parish-system && php artisan schedule:run
*/5 * * * * /usr/local/bin/parish-system-monitor
```

3. **Backup Script** (Create manually)
```bash
#!/bin/bash
# /usr/local/bin/parish-backup.sh

BACKUP_DIR="/var/backups/parish-system"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
if grep -q "DB_CONNECTION=sqlite" /var/www/parish-system/.env; then
    cp /var/www/parish-system/database/database.sqlite $BACKUP_DIR/database_$DATE.sqlite
elif grep -q "DB_CONNECTION=mysql" /var/www/parish-system/.env; then
    mysqldump -u parish_user -p parish_system_db > $BACKUP_DIR/database_$DATE.sql
fi

# Files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz -C /var/www/parish-system storage public/uploads .env

# Clean old backups (keep 30 days)
find $BACKUP_DIR -name "*.sqlite" -mtime +30 -delete
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

### Phase 9: Testing

1. **Health Checks**
```bash
# Test health endpoints
curl http://your-domain.com/health
curl http://your-domain.com/health/detailed
```

2. **Application Tests**
```bash
cd /var/www/parish-system
php artisan test
```

3. **Performance Test**
```bash
# Using Apache Bench (install with: sudo apt install apache2-utils)
ab -n 100 -c 10 http://your-domain.com/
```

## 🔧 Maintenance Commands

### Regular Maintenance
```bash
# Clear caches
php artisan optimize:clear

# Rebuild optimizations
php artisan optimize

# Update dependencies
composer install --optimize-autoloader --no-dev

# Database maintenance (SQLite)
sqlite3 database/database.sqlite "VACUUM; PRAGMA optimize;"
```

### Monitoring
```bash
# View application logs
tail -f storage/logs/laravel.log

# View web server logs
sudo tail -f /var/log/nginx/parish-system-access.log
sudo tail -f /var/log/nginx/parish-system-error.log

# Check system resources
htop
df -h
free -h
```

### Troubleshooting
```bash
# Check service status
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
sudo systemctl status redis-server

# Restart services
sudo systemctl restart nginx php8.2-fpm

# Check configuration
sudo nginx -t
php artisan config:clear
```

## 📊 Performance Optimization

### Database Optimization
- Use MySQL for better performance over SQLite in production
- Enable Redis for caching and sessions
- Regular database optimization and cleanup
- Monitor slow queries

### Application Optimization
- Enable OPcache for PHP
- Use Redis for caching, sessions, and queues
- Enable Gzip/Brotli compression
- Implement CDN for static assets
- Regular cache clearing and rebuilding

### Server Optimization
- Tune PHP-FPM pool settings
- Configure Nginx worker processes
- Enable HTTP/2
- Set up proper caching headers
- Monitor server resources

## 🚨 Security Best Practices

1. **Keep System Updated**
   - Regular OS updates
   - PHP and package updates
   - Application dependency updates

2. **Access Control**
   - Use SSH keys instead of passwords
   - Implement fail2ban for brute force protection
   - Regular security audits

3. **Application Security**
   - Strong database passwords
   - Secure environment file permissions
   - HTTPS everywhere
   - Regular backup testing

4. **Monitoring**
   - Set up log monitoring
   - Implement alerting for errors
   - Regular health checks
   - Performance monitoring

## 📞 Support

For issues or questions:
1. Check application logs: `storage/logs/laravel.log`
2. Check web server logs: `/var/log/nginx/`
3. Test health endpoints: `/health` and `/health/detailed`
4. Review this deployment guide
5. Contact system administrator

---

**🎉 Congratulations! Your Parish Management System is now production-ready!**