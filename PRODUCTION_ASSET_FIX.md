# 🚨 PRODUCTION DEPLOYMENT GUIDE

## Quick Fix for Asset Loading Errors

If you see these errors:
- `NS_ERROR_CORRUPTED_CONTENT`
- `Loading module was blocked because of a disallowed MIME type ("text/html")`
- `Loading failed for the module with source`

## � ONE-COMMAND SOLUTION:

1. Upload all files to your production server
2. Run this single command in the production terminal:

```bash
bash production-deploy.sh
```

That's it! The script will:
- ✅ Clear all logs (Laravel, deployment, cache)
- ✅ Build production caches
- ✅ Verify frontend assets
- ✅ Set correct file permissions
- ✅ Run database migrations
- ✅ Create admin user
- ✅ Optimize entire system

## 📋 What the Script Does:

1. **Clears All Logs** - Removes development/production logs
2. **Clears Laravel Caches** - Removes old cached data
3. **Sets Production Environment** - Configures for production
4. **Builds Production Caches** - Optimizes performance
5. **Verifies Assets** - Ensures frontend builds exist
6. **Sets Permissions** - Configures file access
7. **Database Setup** - Runs migrations and creates admin
8. **Final Verification** - Confirms everything works

## � After Running Script:

Visit: https://parish.quovadisyouthhub.org
Login: admin@parishsystem.com / Admin123!

**Change the default password immediately!**

## 🆘 If Still Having Issues:

Check that:
- [ ] public/build/manifest.json exists
- [ ] public/build/assets/app-*.js files exist  
- [ ] .env file has correct database credentials
- [ ] Document root points to public/ folder

That's all you need! One script handles everything.