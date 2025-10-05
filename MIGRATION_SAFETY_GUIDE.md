# Safe Production Migration Guide

## 🔒 How to Safely Run Migrations in Production

This guide ensures you can update your database schema without losing existing data.

## 🛡️ Safety Principles

### 1. **Always Backup First**
- Never run migrations without a current backup
- Test restore procedures beforehand
- Keep multiple backup copies

### 2. **Use Laravel's Safe Migration Features**
- Laravel migrations are designed to be safe by default
- They only apply changes that haven't been run before
- Each migration is tracked in the `migrations` table

### 3. **Follow the Step-by-Step Process**

## 📋 Step-by-Step Production Migration Process

### Option A: Using the Enhanced Deployment Script
```bash
# This now includes automatic backups and safety checks
./deploy-production.sh
```

### Option B: Using the Safe Migration Script (Recommended for Critical Changes)
```bash
# Interactive script with confirmation prompts
./migrate-production-safe.sh
```

### Option C: Manual Step-by-Step Process

#### 1. Check Current Status
```bash
php artisan migrate:status
```

#### 2. Create Database Backup
```bash
# For MySQL
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# For SQLite
cp database/database.sqlite backup_$(date +%Y%m%d_%H%M%S).sqlite
```

#### 3. Review Pending Migrations
```bash
# See what will be applied
php artisan migrate:status | grep Pending

# View migration files
ls -la database/migrations/
```

#### 4. Run Migrations Safely
```bash
# Option 1: All at once (faster)
php artisan migrate --force

# Option 2: Step by step (safer for complex changes)
php artisan migrate --step --force

# Option 3: Pretend mode (see SQL without executing)
php artisan migrate --pretend
```

#### 5. Verify Results
```bash
php artisan migrate:status
```

## 🎯 Specific Safety for Your Parish Management System

### Your Recent Migration: `member_marriage_residence`
This migration is **SAFE** because it:
- ✅ Only **adds** a new column (`member_marriage_residence`)
- ✅ Uses `nullable()` so existing records aren't affected
- ✅ Doesn't modify or remove existing data
- ✅ Has a proper rollback in the `down()` method

```php
// Safe migration example
public function up()
{
    Schema::table('members', function (Blueprint $table) {
        $table->string('member_marriage_residence')->nullable()->after('spouse_marital_status');
    });
}

public function down()
{
    Schema::table('members', function (Blueprint $table) {
        $table->dropColumn('member_marriage_residence');
    });
}
```

## ⚠️ Migration Types by Risk Level

### 🟢 **LOW RISK** (Safe for production)
- Adding new nullable columns
- Adding new tables
- Adding indexes
- Adding foreign keys (with proper constraints)

### 🟡 **MEDIUM RISK** (Use caution)
- Adding non-nullable columns with defaults
- Renaming columns (use multiple steps)
- Modifying column types (compatible changes)

### 🔴 **HIGH RISK** (Requires special care)
- Dropping columns
- Dropping tables
- Changing column types (incompatible changes)
- Removing indexes used by the application

## 🛠️ Best Practices for Production Migrations

### 1. **Test First**
```bash
# Always test on staging environment first
php artisan migrate --env=staging
```

### 2. **Use Transactions** (Laravel does this automatically)
```php
// Laravel wraps each migration in a transaction
public function up()
{
    // If this fails, everything rolls back automatically
    Schema::table('members', function (Blueprint $table) {
        $table->string('new_column');
    });
}
```

### 3. **Backward Compatibility**
```php
// Good: Add nullable column first
Schema::table('members', function (Blueprint $table) {
    $table->string('new_field')->nullable();
});

// Later migration: Make it required after data population
Schema::table('members', function (Blueprint $table) {
    $table->string('new_field')->nullable(false)->change();
});
```

### 4. **Data Migrations**
```php
// Separate data operations from schema changes
public function up()
{
    // 1. Add column
    Schema::table('members', function (Blueprint $table) {
        $table->string('full_name')->nullable();
    });
    
    // 2. Populate data
    DB::table('members')->update([
        'full_name' => DB::raw("CONCAT(first_name, ' ', last_name)")
    ]);
}
```

## 🚨 Emergency Rollback Procedures

### If Migration Fails:
1. **Don't Panic** - Laravel automatically rolls back failed migrations
2. **Check the error** in the console output
3. **Restore from backup** if needed:
   ```bash
   # MySQL restore
   mysql -u username -p database_name < backup_file.sql
   
   # SQLite restore
   cp backup_file.sqlite database/database.sqlite
   ```
4. **Fix the migration** and try again

### Manual Rollback:
```bash
# Rollback last batch
php artisan migrate:rollback

# Rollback specific number of batches
php artisan migrate:rollback --step=1

# Rollback to specific migration
php artisan migrate:rollback --to=2024_01_01_000000_specific_migration
```

## 📊 Monitoring After Migration

### 1. **Check Application Health**
```bash
php artisan about
php artisan migrate:status
```

### 2. **Test Critical Features**
- Test member registration
- Test certificate generation
- Check data integrity

### 3. **Monitor Logs**
```bash
tail -f storage/logs/laravel.log
```

## 🎯 Summary for Your System

Your `member_marriage_residence` migration is **production-ready** because:
- ✅ It only adds data (no removal)
- ✅ Uses nullable column (no data corruption)
- ✅ Has been tested locally
- ✅ Has proper rollback method
- ✅ Follows Laravel best practices

**Recommended approach for your case:**
1. Run `./migrate-production-safe.sh` for interactive safety
2. Or use the enhanced `./deploy-production.sh` for automated deployment

Both scripts now include automatic backups and safety checks! 🚀