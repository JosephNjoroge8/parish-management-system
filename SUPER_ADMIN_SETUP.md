# 🔐 Super Admin User Creation Commands

## Overview
Your Parish Management System uses a simple admin system with the `is_admin` flag. Any user with `is_admin = 1` has full access to all system features.

---

## 🚀 Method 1: MySQL Direct Insert (Recommended)

### Option A: Default Admin User
```sql
-- Create a super admin user with full system access
INSERT INTO users (name, email, password, email_verified_at, is_admin, is_active, phone, created_at, updated_at) 
VALUES (
    'Super Administrator',
    'admin@parish.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Password: password
    NOW(),
    1,  -- is_admin = true (gives full access)
    1,  -- is_active = true
    NULL,  -- phone (optional)
    NOW(),
    NOW()
);
```

**Login Credentials:**
- **Email**: admin@parish.com
- **Password**: password

### Option B: Custom Admin Credentials
```sql
-- Replace with your preferred email and details
INSERT INTO users (name, email, password, email_verified_at, is_admin, is_active, phone, created_at, updated_at) 
VALUES (
    'Parish Administrator',                    -- Your preferred name
    'your-email@domain.com',                  -- Your email
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Default: 'password'
    NOW(),
    1,  -- is_admin = true (gives full access)
    1,  -- is_active = true  
    '+1234567890',  -- Your phone (optional)
    NOW(),
    NOW()
);
```

**Default Password**: `password` (change after first login)

---

## 🎯 Method 2: Laravel Artisan Command (Alternative)

### Run this in your terminal:
```bash
# Navigate to your Laravel project
cd /path/to/your/parish-system

# Run the admin seeder
php artisan db:seed --class=AdminUserSeeder
```

**This creates:**
- **Email**: admin@parish.com
- **Password**: admin123
- **Admin Access**: Full system access via is_admin flag

---

## � Method 3: Custom Password Hash

If you want to set a specific password, generate the hash first:

### Using Laravel Tinker:
```bash
php artisan tinker
```

```php
// In tinker, generate your password hash
use Illuminate\Support\Facades\Hash;
echo Hash::make('YourSecurePassword123!');
exit
```

### Then use the generated hash in MySQL:
```sql
INSERT INTO users (name, email, password, email_verified_at, is_admin, is_active, phone, created_at, updated_at) 
VALUES (
    'Your Name',
    'your-email@domain.com',
    'YOUR_GENERATED_HASH_HERE',  -- Replace with hash from tinker
    NOW(),
    1,  -- is_admin = true (full access)
    1,  -- is_active = true
    NULL,  -- phone (optional)
    NOW(),
    NOW()
);
```

---

## 🔧 User Access Levels

Your system uses a simple admin flag approach:

- **is_admin = 1** - Full system access (can do everything)
- **is_admin = 0** - Regular user access (limited features)
- **is_active = 1** - User can login
- **is_active = 0** - User account disabled

---

## ✅ Verification Commands

### Check if admin user was created:
```sql
SELECT id, name, email, is_admin, is_active, created_at 
FROM users 
WHERE email = 'admin@parish.com';
```

### List all admin users:
```sql
SELECT id, name, email, phone, is_admin, is_active, created_at 
FROM users 
WHERE is_admin = 1
ORDER BY created_at DESC;
```

### Count total users by type:
```sql
SELECT 
    COUNT(*) as total_users,
    SUM(is_admin) as admin_users,
    SUM(CASE WHEN is_admin = 0 THEN 1 ELSE 0 END) as regular_users,
    SUM(is_active) as active_users
FROM users;
```

---

## 🚨 Security Notes

1. **Change Default Passwords**: Always change default passwords after first login
2. **Use Strong Passwords**: Minimum 8 characters with mixed case, numbers, and symbols
3. **Secure Email**: Use a secure email address you control
4. **Database Backup**: Backup your database before making changes
5. **Test First**: Test user creation in development environment first

---

## 🔧 Production Deployment

If you're setting this up on your live cPanel hosting:

### 1. Access your database via cPanel phpMyAdmin
### 2. Select your parish management database
### 3. Go to SQL tab
### 4. Run one of the MySQL commands above
### 5. Verify the user was created
### 6. Test login on your live site

---

**Recommended**: Use **Method 1 (MySQL Direct Insert)** for the quickest setup - just one simple SQL command!