#!/bin/bash

# Member Page Production Diagnostic Script
echo "🔍 DIAGNOSING MEMBER PAGE ISSUES IN PRODUCTION"

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
echo "1. CHECKING DATABASE CONNECTION & MEMBER DATA"
echo "=============================================="

php artisan tinker --execute="
try {
    // Test database connection
    \$memberCount = \App\Models\Member::count();
    echo '✅ Members table accessible: ' . \$memberCount . ' total members';
    
    // Check for critical columns
    \$columns = \Schema::getColumnListing('members');
    echo 'Available columns: ' . implode(', ', \$columns);
    
    // Check for problematic columns
    \$criticalColumns = ['marital_status', 'member_marriage_residence', 'first_name', 'last_name'];
    foreach (\$criticalColumns as \$col) {
        if (in_array(\$col, \$columns)) {
            echo '✅ Column exists: ' . \$col;
        } else {
            echo '❌ Missing column: ' . \$col;
        }
    }
    
    // Test member retrieval with relationships
    \$member = \App\Models\Member::with('family')->first();
    if (\$member) {
        echo '✅ Member relationships working: ' . \$member->first_name;
    }
    
    // Test pagination
    \$paginatedMembers = \App\Models\Member::paginate(10);
    echo '✅ Member pagination working: ' . \$paginatedMembers->total() . ' total, ' . \$paginatedMembers->count() . ' on page';
    
} catch (\Exception \$e) {
    echo '❌ Database error: ' . \$e->getMessage();
    echo 'Stack trace: ' . \$e->getTraceAsString();
}
"

echo ""
echo "=============================================="
echo "2. CHECKING MEMBER ROUTES & CONTROLLERS"
echo "=============================================="

# Check if member routes are registered
echo "Member routes:"
php artisan route:list | grep -i member || echo "❌ No member routes found"

# Check if MemberController exists
if [ -f "app/Http/Controllers/MemberController.php" ]; then
    echo "✅ MemberController exists"
else
    echo "❌ MemberController missing"
fi

echo ""
echo "=============================================="
echo "3. CHECKING FRONTEND ASSETS"
echo "=============================================="

# Check if build files exist
if [ -d "public/build" ]; then
    echo "✅ Build directory exists"
    ls -la public/build/
else
    echo "❌ Build directory missing - run 'npm run build'"
fi

# Check if Vite manifest exists
if [ -f "public/build/manifest.json" ]; then
    echo "✅ Vite manifest exists"
else
    echo "❌ Vite manifest missing"
fi

echo ""
echo "=============================================="
echo "4. CHECKING INERTIA CONFIGURATION"
echo "=============================================="

php artisan tinker --execute="
try {
    // Check Inertia middleware
    echo 'Inertia version: ' . \Inertia\Inertia::getVersion();
    
    // Check if Members page component exists
    \$membersPageExists = file_exists(resource_path('js/Pages/Members/Index.jsx')) || 
                         file_exists(resource_path('js/Pages/Members/Index.tsx')) ||
                         file_exists(resource_path('js/Pages/Members.jsx'));
    
    if (\$membersPageExists) {
        echo '✅ Members page component found';
    } else {
        echo '❌ Members page component missing';
    }
    
} catch (\Exception \$e) {
    echo '❌ Inertia check failed: ' . \$e->getMessage();
}
"

echo ""
echo "=============================================="
echo "5. TESTING MEMBER PAGE ACCESS"
echo "=============================================="

php artisan tinker --execute="
try {
    // Simulate member index request
    \$request = \Illuminate\Http\Request::create('/members', 'GET');
    \$request->headers->set('X-Inertia', 'true');
    
    echo 'Testing member index route...';
    
    // Check if route exists
    \$route = \Route::getRoutes()->match(\$request);
    if (\$route) {
        echo '✅ Member route matched: ' . \$route->getName();
    } else {
        echo '❌ Member route not found';
    }
    
} catch (\Exception \$e) {
    echo '❌ Route test failed: ' . \$e->getMessage();
}
"

echo ""
echo "=============================================="
echo "6. CHECKING PERMISSIONS & AUTHENTICATION"
echo "=============================================="

php artisan tinker --execute="
try {
    // Check if admin user exists and can access members
    \$admin = \App\Models\User::where('is_admin', true)->first();
    if (\$admin) {
        echo '✅ Admin user exists: ' . \$admin->email;
        
        // Test authentication
        \Auth::login(\$admin);
        echo '✅ Admin authentication working';
        
        // Test authorization (if policies exist)
        if (class_exists('\App\Policies\MemberPolicy')) {
            echo 'Member policy exists - checking permissions';
        }
    } else {
        echo '❌ No admin user found';
    }
    
} catch (\Exception \$e) {
    echo '❌ Authentication test failed: ' . \$e->getMessage();
}
"

echo ""
echo "=============================================="
echo "7. RECOMMENDATIONS"
echo "=============================================="

echo "Based on the diagnosis above, run these commands to fix common issues:"
echo ""
echo "🔧 FRONTEND ISSUES:"
echo "npm install && npm run build"
echo ""
echo "🔧 CACHE ISSUES:"
echo "php artisan optimize:clear"
echo "php artisan config:cache"
echo ""
echo "🔧 DATABASE ISSUES:"
echo "php artisan migrate:status"
echo "php artisan migrate --force"
echo ""
echo "🔧 PERMISSIONS:"
echo "chmod -R 775 storage bootstrap/cache"
echo "chmod 644 .env"
echo ""
echo "🔧 INERTIA ISSUES:"
echo "php artisan route:clear"
echo "npm run build"

echo ""
echo "=============================================="
echo "DIAGNOSIS COMPLETE"
echo "=============================================="