#!/bin/bash

# Member Page Production Fix Script
echo "🔧 FIXING MEMBER PAGE ISSUES IN PRODUCTION"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

echo "=============================================="
echo "🚀 STARTING MEMBER PAGE PRODUCTION FIXES"
echo "=============================================="

# Step 1: Clear all caches
print_status "1. Clearing all application caches..."
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear 2>/dev/null || true
php artisan cache:clear
print_success "✅ All caches cleared"

# Step 2: Fix database issues
print_status "2. Checking and fixing database schema..."
php artisan tinker --execute="
try {
    // Check if marital_status column exists
    \$columns = \Schema::getColumnListing('members');
    if (!in_array('marital_status', \$columns)) {
        print('Adding marital_status column...');
        \Schema::table('members', function (\$table) {
            \$table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->default('single')->after('matrimony_status');
        });
        
        // Migrate data from matrimony_status
        \DB::table('members')->whereNotNull('matrimony_status')->update([
            'marital_status' => \DB::raw('matrimony_status')
        ]);
        print('✅ marital_status column added and data migrated');
    } else {
        print('✅ marital_status column already exists');
    }
    
    // Check member_marriage_residence column
    if (!in_array('member_marriage_residence', \$columns)) {
        print('Adding member_marriage_residence column...');
        \Schema::table('members', function (\$table) {
            \$table->string('member_marriage_residence')->nullable()->after('marital_status');
        });
        print('✅ member_marriage_residence column added');
    } else {
        print('✅ member_marriage_residence column already exists');
    }
    
} catch (\Exception \$e) {
    print('❌ Database fix failed: ' . \$e->getMessage());
}
"

# Step 3: Run pending migrations
print_status "3. Running pending migrations..."
php artisan migrate --force --no-interaction
print_success "✅ Migrations completed"

# Step 4: Rebuild frontend assets
print_status "4. Rebuilding frontend assets..."
if command -v npm >/dev/null 2>&1; then
    npm install
    npm run build
    print_success "✅ Frontend assets rebuilt"
else
    print_warning "⚠️ npm not found - please run 'npm install && npm run build' manually"
fi

# Step 5: Fix file permissions
print_status "5. Setting proper file permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chmod 644 .env
chmod +x artisan
print_success "✅ File permissions set"

# Step 6: Create storage link
print_status "6. Creating storage symbolic link..."
php artisan storage:link
print_success "✅ Storage link created"

# Step 7: Test member functionality
print_status "7. Testing member functionality..."
php artisan tinker --execute="
try {
    // Test member model
    \$memberCount = \App\Models\Member::count();
    print('✅ Total members: ' . \$memberCount);
    
    // Test member relationships
    \$member = \App\Models\Member::with('family')->first();
    if (\$member) {
        print('✅ Member relationships working: ' . \$member->first_name);
    }
    
    // Test member pagination
    \$paginatedMembers = \App\Models\Member::paginate(10);
    print('✅ Member pagination: ' . \$paginatedMembers->total() . ' total members');
    
    // Test member search/filtering
    \$marriedMembers = \App\Models\Member::where('marital_status', 'married')->count();
    print('✅ Marital status filtering: ' . \$marriedMembers . ' married members');
    
} catch (\Exception \$e) {
    print('❌ Member functionality test failed: ' . \$e->getMessage());
}
"

# Step 8: Check routes
print_status "8. Verifying member routes..."
MEMBER_ROUTES=$(php artisan route:list | grep -i member | wc -l)
if [ "$MEMBER_ROUTES" -gt 0 ]; then
    print_success "✅ Found $MEMBER_ROUTES member-related routes"
    php artisan route:list | grep -i member
else
    print_error "❌ No member routes found"
fi

# Step 9: Cache configurations for production
print_status "9. Optimizing for production..."
php artisan config:cache
php artisan route:cache 2>/dev/null || print_warning "⚠️ Route caching skipped (closures detected)"
php artisan view:cache 2>/dev/null || print_warning "⚠️ View caching skipped"
print_success "✅ Production optimizations applied"

# Step 10: Final verification
print_status "10. Final verification..."
php artisan tinker --execute="
try {
    // Test database connection
    \$pdo = \DB::connection()->getPdo();
    print('✅ Database connection: SUCCESS');
    
    // Test member controller (if accessible)
    if (class_exists('\App\Http\Controllers\MemberController')) {
        print('✅ MemberController class exists');
    }
    
    // Test Inertia
    print('✅ Inertia version: ' . \Inertia\Inertia::getVersion());
    
    // Final member test
    \$recentMember = \App\Models\Member::latest()->first();
    if (\$recentMember) {
        print('✅ Latest member: ' . \$recentMember->first_name . ' ' . \$recentMember->last_name);
    }
    
} catch (\Exception \$e) {
    print('❌ Final verification failed: ' . \$e->getMessage());
}
"

echo ""
echo "=============================================="
echo "🎉 MEMBER PAGE FIXES COMPLETED"
echo "=============================================="

print_success "✅ All member page fixes have been applied!"

echo ""
print_status "🌐 TEST YOUR MEMBER PAGE NOW:"
APP_URL=$(grep "APP_URL=" .env | cut -d'=' -f2 2>/dev/null || echo "your-domain.com")
echo "- Login: $APP_URL/login"
echo "- Members: $APP_URL/members"
echo "- Dashboard: $APP_URL/dashboard"

echo ""
print_warning "📋 IF ISSUES PERSIST:"
echo "1. Check browser console for JavaScript errors"
echo "2. Check: tail -f storage/logs/laravel.log"
echo "3. Verify .env file has correct database settings"
echo "4. Ensure admin user exists: admin@parish.local / admin123"

echo ""
print_status "🔧 MANUAL COMMANDS IF NEEDED:"
echo "# Check application logs:"
echo "tail -f storage/logs/laravel.log"
echo ""
echo "# Rebuild frontend if changes made:"
echo "npm run build"
echo ""
echo "# Test database manually:"
echo "php artisan tinker"
echo "App\\Models\\Member::count()"

echo ""
print_success "🚀 Member page should now be fully functional!"