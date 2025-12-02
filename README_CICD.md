# 🚀 CI/CD Pipeline - Complete Package

## What's Been Created

Your Parish Management System now has a **fully automated CI/CD pipeline** for cPanel shared hosting using GitHub webhooks. This package includes everything you need for automated deployments.

---

## 📦 Package Contents

### Core Files

| File | Purpose | Location |
|------|---------|----------|
| `webhook.php` | Receives GitHub webhook events | `public/` |
| `deploy.php` | Executes automated deployment | `public/` |
| `laravel.yml` | GitHub Actions workflow | `.github/workflows/` |
| `setup-webhook.sh` | Configuration helper script | Root |
| `.htaccess-webhook-security` | Security rules for webhooks | `public/` |

### Documentation

| Document | Description |
|----------|-------------|
| **CICD_SETUP_GUIDE.md** | Complete setup guide (200+ pages) |
| **CICD_QUICK_START.md** | Quick setup guide (condensed) |
| **CICD_TESTING_GUIDE.md** | Testing and validation guide |
| **README_CICD.md** | This file |

---

## 🎯 What This Pipeline Does

### Automated Workflow

```
Developer Push → GitHub Actions → Webhook → Deployment Script → Production ✅
```

### Features

✅ **Automated Testing**
- Runs PHPUnit tests on every push
- Validates code quality with Laravel Pint
- Prevents broken code from deploying

✅ **Automated Building**
- Builds production React/Vite assets
- Optimizes for performance
- Generates source maps

✅ **Automated Deployment**
- Pulls latest code from GitHub
- Installs dependencies (Composer + NPM)
- Runs database migrations
- Clears and rebuilds caches
- Sets proper permissions

✅ **Safety Features**
- Creates backup before deployment
- Enables maintenance mode during deploy
- Automatic rollback on failure
- Detailed logging of all operations

✅ **Security**
- GitHub signature verification
- IP whitelisting (optional)
- Secret key authentication
- Protected deployment scripts

---

## 🚀 Quick Start

### Option 1: Automated Setup (Recommended)

```bash
cd /home/joseph/Desktop/parish-management-system
chmod +x setup-webhook.sh
bash setup-webhook.sh
```

The script will guide you through configuration.

### Option 2: Manual Setup

Follow the guide: [CICD_QUICK_START.md](CICD_QUICK_START.md)

---

## 📚 Documentation Overview

### For First-Time Setup
Start here: **[CICD_QUICK_START.md](CICD_QUICK_START.md)**
- Condensed 3-step setup process
- Essential configuration only
- Get up and running in 30 minutes

### For Complete Understanding
Read this: **[CICD_SETUP_GUIDE.md](CICD_SETUP_GUIDE.md)**
- Comprehensive 200+ page guide
- Detailed explanations of each component
- Troubleshooting for every scenario
- Security best practices
- Maintenance procedures
- Advanced configurations

### For Testing & Validation
Use this: **[CICD_TESTING_GUIDE.md](CICD_TESTING_GUIDE.md)**
- Complete testing procedures
- Validation checklists
- Performance benchmarks
- Debugging steps
- Sign-off checklist

---

## 🔧 Setup Summary

### Prerequisites Needed

**On Your Server (cPanel):**
- PHP 8.2 or higher
- MySQL/MariaDB database
- Git installed
- Composer support
- Node.js/NPM support
- Git Version Control feature (or Terminal access)

**On GitHub:**
- Repository admin access
- Ability to create webhooks

**Time Required:**
- First-time setup: 30-45 minutes
- Testing: 15-30 minutes
- **Total: ~1 hour**

### Configuration Steps

1. **Configure files** (5 min)
   - Run `setup-webhook.sh` OR
   - Manually update `webhook.php`, `deploy.php`, `.cpanel.yml`

2. **Setup cPanel** (15 min)
   - Create database
   - Configure PHP settings
   - Clone repository
   - Upload webhook files
   - Run initial setup

3. **Configure GitHub webhook** (5 min)
   - Add webhook in repository settings
   - Set secret and URL
   - Verify connection

4. **Test the pipeline** (10 min)
   - Push test commit
   - Monitor deployment
   - Verify production site

---

## 🎬 How It Works

### Step-by-Step Flow

1. **Developer pushes code to Main branch**
   ```bash
   git push origin Main
   ```

2. **GitHub Actions triggers automatically**
   - Runs tests (PHPUnit)
   - Checks code quality (Pint)
   - Builds production assets (Vite)
   - Duration: 5-8 minutes

3. **GitHub sends webhook to production server**
   - URL: `https://yourdomain.com/webhook.php`
   - Signed with secret key
   - Contains commit information

4. **Webhook verifies and triggers deployment**
   - Verifies GitHub signature
   - Checks IP address
   - Calls `deploy.php` script

5. **Deployment script executes**
   - Creates backup (in case of failure)
   - Enables maintenance mode
   - Pulls latest code via Git
   - Installs Composer dependencies
   - Installs NPM dependencies
   - Builds production assets
   - Runs database migrations
   - Clears Laravel caches
   - Rebuilds optimized caches
   - Sets file permissions
   - Disables maintenance mode
   - Duration: 3-7 minutes

6. **Production updated! ✅**
   - Changes are live
   - Logs available for review
   - Backup saved for rollback

### Total Time: 8-15 minutes from push to production

---

## 🛡️ Security Features

- **Signature Verification**: Every webhook request is verified using HMAC SHA-256
- **Secret Keys**: Strong random secrets prevent unauthorized deployments
- **IP Whitelisting**: Optional restriction to GitHub IP ranges
- **Rate Limiting**: Prevents multiple simultaneous deployments
- **Secure File Access**: Protected deployment scripts via `.htaccess`
- **Encrypted Communication**: All traffic over HTTPS
- **Audit Logging**: Complete logs of all deployments

---

## 📊 Monitoring & Logs

### Available Logs

```bash
# Webhook requests
storage/logs/webhook.log

# Deployment process
storage/logs/deployment.log

# PHP errors
storage/logs/webhook-errors.log

# Laravel application
storage/logs/laravel.log
```

### View Logs

```bash
# Via Terminal/SSH
tail -f storage/logs/deployment.log

# Via cPanel File Manager
Navigate to storage/logs/ and view files
```

### GitHub Monitoring

- **Actions Tab**: See test/build progress
- **Webhook Deliveries**: See webhook requests and responses

---

## 🔄 Daily Workflow

### Making Changes

```bash
# 1. Create feature branch
git checkout -b feature/new-feature

# 2. Make your changes
# ... edit files ...

# 3. Test locally
php artisan test
npm run build

# 4. Commit changes
git add .
git commit -m "Add new feature"

# 5. Push to GitHub
git push origin feature/new-feature

# 6. Create Pull Request on GitHub
# Tests will run automatically

# 7. After review, merge to Main
# Automatic deployment to production!
```

### Monitoring Deployment

```bash
# Watch deployment log
tail -f /home2/YOUR_USERNAME/parish_system/storage/logs/deployment.log

# Or check GitHub
# Repository → Actions → Latest workflow run
# Repository → Settings → Webhooks → Recent Deliveries
```

---

## 🆘 Troubleshooting

### Common Issues

| Issue | Quick Fix |
|-------|-----------|
| Webhook 403 | Check IP restrictions in `.htaccess` |
| Webhook 500 | Check PHP error logs and file permissions |
| Deployment fails | Review `storage/logs/deployment.log` |
| Assets not loading | Rebuild: `npm run build` + `php artisan optimize` |
| Database errors | Verify `.env` credentials |
| Tests fail | Fix tests before deployment allowed |

### Getting Help

1. **Check Logs First**
   - Deployment log
   - Webhook log
   - Laravel log

2. **Review Documentation**
   - [Quick Start Guide](CICD_QUICK_START.md)
   - [Complete Setup Guide](CICD_SETUP_GUIDE.md)
   - [Testing Guide](CICD_TESTING_GUIDE.md)

3. **Common Solutions**
   - Troubleshooting section in Complete Guide
   - FAQ section in Complete Guide

---

## 🔧 Maintenance

### Daily
- Monitor deployment logs
- Check for errors

### Weekly
- Review failed deployments
- Clean old logs (automatic)
- Verify backups exist

### Monthly
- Update dependencies
- Review security settings
- Optimize database
- Test rollback procedure

---

## 📈 Performance

### Typical Deployment Timeline

| Phase | Duration |
|-------|----------|
| Tests (GitHub Actions) | 2-3 min |
| Build (GitHub Actions) | 3-5 min |
| Webhook trigger | < 5 sec |
| Git pull | 10-30 sec |
| Composer install | 1-2 min |
| NPM install & build | 2-4 min |
| Migrations | 5-30 sec |
| Optimization | 10-20 sec |
| **Total** | **8-15 min** |

### Optimization Tips

- Use OPcache for PHP
- Enable Laravel caching in production
- Use CDN for assets (optional)
- Database indexing
- Regular cleanup of old logs/backups

---

## 🎓 Learning Resources

### Included Documentation

1. **[CICD_QUICK_START.md](CICD_QUICK_START.md)** - Get started fast
2. **[CICD_SETUP_GUIDE.md](CICD_SETUP_GUIDE.md)** - Complete reference
3. **[CICD_TESTING_GUIDE.md](CICD_TESTING_GUIDE.md)** - Testing procedures

### External Resources

- [Laravel Deployment Docs](https://laravel.com/docs/deployment)
- [GitHub Webhooks Docs](https://docs.github.com/en/webhooks)
- [GitHub Actions Docs](https://docs.github.com/en/actions)
- [cPanel Git Docs](https://docs.cpanel.net/cpanel/files/git-version-control/)

---

## ✅ Final Checklist

Before going live, ensure:

### Configuration
- [ ] `webhook.php` configured with your values
- [ ] `deploy.php` configured with your values
- [ ] `.cpanel.yml` configured with your paths
- [ ] Webhook secret is strong (32+ characters)
- [ ] All files committed to Git

### Server
- [ ] Database created and configured
- [ ] PHP 8.2+ with required extensions
- [ ] Git, Composer, NPM installed
- [ ] Repository cloned to server
- [ ] `.env` file created and configured
- [ ] Initial setup commands completed
- [ ] File permissions correct (755/644)
- [ ] Domain pointing to `public/` directory

### GitHub
- [ ] Webhook created and configured
- [ ] Webhook shows green checkmark
- [ ] Test delivery successful (200 OK)
- [ ] Actions workflow exists
- [ ] Tests pass in GitHub Actions

### Testing
- [ ] Webhook responds correctly
- [ ] Deployment script runs successfully
- [ ] Test push deploys to production
- [ ] Changes appear on live site
- [ ] No errors in logs
- [ ] Rollback tested and works

### Security
- [ ] Webhook secret is strong
- [ ] `.env` NOT in Git
- [ ] `APP_DEBUG=false` in production
- [ ] HTTPS enabled
- [ ] Proper file permissions
- [ ] IP whitelisting configured (optional)

---

## 🎉 Success!

You now have a fully automated CI/CD pipeline! Every push to Main branch will:

✅ Run automated tests  
✅ Build production assets  
✅ Deploy to production  
✅ Create backups  
✅ Handle rollbacks  
✅ Log everything  

### Next Steps

1. ✅ Complete setup following Quick Start guide
2. ✅ Test with a small change
3. ✅ Monitor first deployment
4. ✅ Set up monitoring (UptimeRobot, etc.)
5. ✅ Share workflow with your team
6. ✅ Iterate and improve

### Workflow Benefits

- 🚀 **Faster deployments** - No manual steps
- 🛡️ **Safer deployments** - Automatic testing
- 📝 **Better tracking** - Complete audit logs
- 🔄 **Easy rollbacks** - Automatic backups
- 👥 **Team collaboration** - Git-based workflow
- ⏰ **Time savings** - Hours saved per deployment

---

## 📞 Support

For issues or questions:

1. Check the documentation guides
2. Review troubleshooting section
3. Check deployment logs
4. Test components individually

---

## 📝 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | Dec 2, 2024 | Initial CI/CD pipeline release |

---

## 📄 License

This CI/CD pipeline is part of the Parish Management System.  
See LICENSE file for details.

---

**🎯 You're ready to deploy! Push to Main and watch the magic happen.**

For detailed setup instructions, continue to: [CICD_QUICK_START.md](CICD_QUICK_START.md)
