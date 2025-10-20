# 🚀 cPanel Git Deployment Setup Guide

## Overview
This guide will help you set up automatic deployment for your Parish Management System using cPanel's Git Version Control feature.

## 📋 Prerequisites

### 1. Required Access
- cPanel hosting account with Git Version Control feature
- SSH access (optional but recommended)
- Domain or subdomain configured

### 2. Hosting Requirements
- PHP 8.2 or higher
- MySQL database
- Node.js support (most cPanel hosts support this)
- Composer support

## 🔧 Step-by-Step Setup

### Step 1: Prepare Your Hosting Environment

#### 1.1 Create Database
1. Login to cPanel
2. Go to `MySQL Databases`
3. Create a new database (e.g., `yourusername_parish`)
4. Create a database user and assign to the database
5. Note down the database name, username, and password

#### 1.2 Check PHP Version
1. Go to `Select PHP Version` in cPanel
2. Ensure PHP 8.2 is selected
3. Enable required extensions:
   - PDO
   - PDO_MySQL
   - OpenSSL
   - Mbstring
   - Tokenizer
   - XML
   - Ctype
   - JSON
   - BCMath
   - Fileinfo
   - GD

### Step 2: Configure Git Repository in cPanel

#### 2.1 Access Git Version Control
1. Login to cPanel
2. Navigate to `Files` section
3. Click on `Git™ Version Control`

#### 2.2 Clone Repository
1. Click `Create` button
2. Select `Clone a Repository`
3. Fill in the details:
   - **Repository Path**: `/home2/yourusername/repositories/parish-system`
   - **Repository URL**: `https://github.com/JosephNjoroge8/parish-management-system.git`
   - **Repository Name**: `parish-system`
4. Click `Create`

#### 2.3 Configure Deployment
1. After creation, click `Manage` on your repository
2. Go to `Pull or Deploy` tab
3. Set the **Deployment Path** to your website directory:
   - For main domain: `/home2/yourusername/public_html`
   - For subdomain: `/home2/yourusername/public_html/subdomain`
   - For addon domain: `/home2/yourusername/public_html/yourdomain.com`

### Step 3: Update Configuration Files

#### 3.1 Update .cpanel.yml
Update the DEPLOYPATH in your `.cpanel.yml` file to match your hosting structure:

```yaml
- export DEPLOYPATH=/home2/yourusername/public_html  # Update this path
```

#### 3.2 Create Production Environment File
1. On your production server, copy `.env.production` to `.env`
2. Update the following values in `.env`:

```bash
# Update these values
APP_URL=https://yourdomain.com
DB_DATABASE=yourusername_parish
DB_USERNAME=yourusername_parishuser
DB_PASSWORD=your_secure_password
MAIL_HOST=mail.yourdomain.com
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
```

### Step 4: Configure Domain/Subdomain

#### 4.1 For Main Domain
- Files should deploy to `/home2/yourusername/public_html`
- Laravel's `public` folder content should be in the root

#### 4.2 For Subdomain
1. Create subdomain in cPanel (`Subdomains`)
2. Set document root to `/home2/yourusername/public_html/subdomain/public`
3. Deploy to `/home2/yourusername/public_html/subdomain`

#### 4.3 For Addon Domain
1. Create addon domain in cPanel
2. Set document root to `/home2/yourusername/public_html/yourdomain.com/public`
3. Deploy to `/home2/yourusername/public_html/yourdomain.com`

### Step 5: Initial Deployment

#### 5.1 Manual First Deployment
1. In cPanel Git Version Control, click `Manage` on your repository
2. Click `Pull or Deploy`
3. Click `Deploy HEAD Commit`
4. Monitor the deployment log for any errors

#### 5.2 Verify Deployment
1. Check deployment logs in cPanel
2. Visit your website URL
3. Verify all pages load correctly
4. Test login functionality

### Step 6: Enable Automatic Deployment

#### 6.1 Set Up Webhook (Optional)
1. In your GitHub repository settings
2. Go to `Webhooks`
3. Add webhook URL (provided by cPanel)
4. Set content type to `application/json`
5. Select `Just the push event`

#### 6.2 Test Automatic Deployment
1. Make a small change to your code
2. Commit and push to GitHub
3. Check if deployment triggers automatically
4. Verify changes appear on your live site

## 🔍 Troubleshooting

### Common Issues

#### 1. Permission Errors
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
```

#### 2. Database Connection Issues
- Verify database credentials in `.env`
- Check if database exists
- Ensure database user has proper permissions

#### 3. Composer Issues
- Ensure PHP version matches
- Check if all required extensions are enabled

#### 4. Node.js/NPM Issues
- Verify Node.js is available on your hosting
- Check npm installation logs

### Deployment Logs
Check deployment logs at:
- cPanel Git Version Control → Manage → View Logs
- Application logs: `storage/logs/deployment.log`

## 📝 Best Practices

### 1. Testing
- Always test changes in a staging environment first
- Keep backups of your production database
- Monitor deployment logs

### 2. Security
- Use strong database passwords
- Keep `.env` file secure (never commit to Git)
- Enable SSL/HTTPS for your domain

### 3. Performance
- Enable Laravel caching (already configured in .cpanel.yml)
- Use production-optimized settings
- Monitor server resources

## 🔄 Updating the System

After initial setup, updates are automatic:

1. **Push to GitHub**: `git push origin Main`
2. **Automatic Deployment**: cPanel detects the push and runs deployment
3. **Verification**: Check your live site for updates

## 📞 Support

If you encounter issues:
1. Check cPanel deployment logs
2. Verify all configuration files
3. Contact your hosting provider for cPanel-specific issues
4. Check Laravel logs in `storage/logs/`

---

**Remember**: Replace `yourusername` and `yourdomain.com` with your actual hosting username and domain throughout this guide.