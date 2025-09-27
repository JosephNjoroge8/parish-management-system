# PHP Deprecation Fix - User Model

## Issue Fixed
```
PHP Deprecated: App\Models\User::hasRole(): Implicitly marking parameter $guard as nullable is deprecated, the explicit nullable type must be used instead
```

## Changes Made

### 1. Fixed hasRole() Method Signature
**Before:**
```php
public function hasRole($roles, string $guard = null): bool
```

**After:**
```php
public function hasRole($roles, ?string $guard = null): bool
```

### 2. Fixed hasPermissionTo() Method Signature
**Before:**
```php
public function hasPermissionTo($permission, $guardName = null): bool
```

**After:**
```php
public function hasPermissionTo($permission, ?string $guardName = null): bool
```

## What Changed
- Added explicit nullable type declaration (`?string`) instead of implicit nullable parameters
- This resolves PHP 8.2+ deprecation warnings about implicit nullable parameters
- No functional changes - methods work exactly the same way

## Testing
✅ User model loads without deprecation warnings  
✅ hasRole() method works correctly  
✅ hasPermissionTo() method works correctly  
✅ All existing functionality preserved

## Impact
- Eliminates PHP deprecation warnings in logs
- Ensures compatibility with PHP 8.2+
- Maintains backward compatibility
- No breaking changes to existing code

This fix ensures the Parish Management System runs cleanly on modern PHP versions without deprecation warnings.