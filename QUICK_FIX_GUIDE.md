# 🚨 QUICK FIX GUIDE - Blank Page Production Error

## The Error
```
Failed to load module script: Expected a JavaScript module script 
but the server responded with a MIME type of "text/html"
```

## What It Means
**Your JavaScript files are missing from the production server!**

Browser asks for: `/build/assets/app-XXXXXX.js`  
Server can't find it → Returns HTML error page instead  
Browser expects JavaScript → Gets HTML → MIME type error → Blank page

---

## ⚡ FASTEST FIX (5 Minutes)

### On Your Local Machine:
```bash
# 1. Build assets
npm run build

# 2. Verify they exist
ls -la public/build/
# Should see: manifest.json and assets/ folder

# 3. Upload ONLY the public/build folder to your server
# Via cPanel File Manager:
#   - Navigate to /home2/shemidig/parish_system/public/
#   - Delete old 'build' folder (if exists)
#   - Upload your local public/build folder
```

### On Production Server (via cPanel Terminal or SSH):
```bash
# Fix permissions
chmod -R 755 /home2/shemidig/parish_system/public/build

# Clear Laravel caches
cd /home2/shemidig/parish_system
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Test:
1. Visit: `https://parish.quovadisyouthhub.org/diagnostic.php`
2. Should show all ✅ green checks
3. Visit: `https://parish.quovadisyouthhub.org`
4. Should load properly!

---

## 🔍 VERIFY THE FIX

### Check 1: Build Directory Exists
```bash
# On server, run:
ls -la /home2/shemidig/parish_system/public/build/

# Should show:
# drwxr-xr-x  assets/
# -rw-r--r--  manifest.json
```

### Check 2: Manifest is Valid
```bash
# On server, run:
cat /home2/shemidig/parish_system/public/build/manifest.json | head -20

# Should show JSON, not "file not found"
```

### Check 3: Files Are Accessible
Visit in browser:
```
https://parish.quovadisyouthhub.org/build/manifest.json
```
Should show JSON, NOT a 404 error or HTML page

### Check 4: Diagnostic Page
Visit:
```
https://parish.quovadisyouthhub.org/diagnostic.php
```
Should show all green ✅ indicators

---

## 🛠️ IF STILL NOT WORKING

### Problem: Can't upload files via cPanel
**Solution:** Use Git deployment
```bash
# On local machine:
git add public/build
git commit -m "Add production build"
git push origin main

# On server:
cd /home2/shemidig/parish_system
git pull origin main
chmod -R 755 public/build
```

### Problem: Build folder keeps getting deleted
**Solution:** Add to .gitignore exception
```bash
# Edit .gitignore, add:
!public/build/
!public/build/**/*
```

### Problem: "npm run build" fails locally
**Solution:**
```bash
# Clear and reinstall
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Problem: Assets load but page still blank
**Check browser console:**
- F12 → Console tab
- Look for different errors (not MIME type)
- Might be JavaScript errors, API issues, or Auth problems

---

## 📱 QUICK DIAGNOSTIC

### Browser Test (F12):
```
Console Tab:
✅ No "MIME type" errors → Assets are loading
✅ No red errors → App is working
❌ MIME type error → Assets missing (follow this guide)
❌ Other JS errors → Different problem (not asset related)

Network Tab:
✅ /build/assets/*.js returns 200 → Good!
❌ /build/assets/*.js returns 404 → Files missing
❌ /build/assets/*.js returns HTML → .htaccess issue
```

### Server Test (SSH/Terminal):
```bash
# Check if files exist:
ls /home2/shemidig/parish_system/public/build/assets/ | wc -l

# Should return a number > 50 (like 97)
# If returns 0 or "no such file" → Files are missing!
```

---

## 📋 PREVENTION CHECKLIST

Before every deployment:
- [ ] Run `npm run build` locally
- [ ] Verify `public/build/` exists and has files
- [ ] Commit `public/build/` to Git (or upload separately)
- [ ] After deployment, run `chmod -R 755 public/build`
- [ ] Test `/diagnostic.php` before announcing "live"

---

## 🆘 STILL STUCK?

### Run Diagnostic Scripts:
```bash
# On local machine:
bash pre-deployment-check.sh

# On server:
bash verify-vite-assets.sh  # (if uploaded)
```

### Check These Files:
1. `SYSTEM_ANALYSIS_COMPLETE.md` - Full technical explanation
2. `PRODUCTION_BLANK_PAGE_SOLUTION.md` - Detailed deployment guide
3. `/diagnostic.php` - Live production checker

### Contact Info:
- Check Laravel logs: `storage/logs/laravel.log`
- Browser console: F12 → Console
- Network requests: F12 → Network

---

## ✅ SUCCESS INDICATORS

You've fixed it when:
1. `/diagnostic.php` = All green ✅
2. Main page loads with colors/styling
3. Console (F12) = No errors
4. Can click around and everything works

---

## 🎯 REMEMBER

**The #1 cause of blank page in production:**
→ **Missing `public/build/` directory or its contents**

**The #1 fix:**
→ **Build locally with `npm run build` and upload to server**

**The #1 verification:**
→ **Visit `/diagnostic.php` - must show all green!**

---

That's it! This error is almost always about missing build files. Build → Upload → Fix permissions → Done! 🚀
