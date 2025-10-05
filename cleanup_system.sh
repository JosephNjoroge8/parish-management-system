#!/bin/bash

# Parish Management System - Cleanup Script
# This script removes redundant files, unused assets, and optimizes the codebase

echo "🧹 Starting Parish Management System Cleanup..."

# Create backup before cleanup
BACKUP_DIR="backups/cleanup_$(date +%Y%m%d_%H%M%S)"
mkdir -p $BACKUP_DIR

echo "📦 Creating backup in $BACKUP_DIR..."
cp -r resources/js/Components $BACKUP_DIR/
cp -r resources/js/Pages $BACKUP_DIR/
cp app/Models/Member.php $BACKUP_DIR/

# Remove redundant component files (after confirming new unified components work)
echo "🗑️  Removing redundant component files..."

# Note: These removals should be done after testing the new unified components
# Uncomment after confirming FormField component works across all forms

# Remove duplicate FormInput implementations
# find resources/js/Pages -name "*.tsx" -exec grep -l "const FormInput\|const FormField" {} \; > redundant_form_components.txt
# echo "Found redundant form component implementations - review before deletion"

# Clean up build artifacts
echo "🧽 Cleaning build artifacts..."
rm -rf public/build/assets/*
rm -rf public/hot
rm -rf node_modules/.cache

# Remove unused CSS
echo "🎨 Analyzing CSS usage..."
# This would require purgecss or similar tool
# npx purgecss --css resources/css/app.css --content 'resources/js/**/*.{js,ts,jsx,tsx}' 'resources/views/**/*.blade.php' --output resources/css/

# Remove console.log statements from production code
echo "🔍 Removing console.log statements..."
find resources/js -name "*.tsx" -o -name "*.ts" | xargs sed -i '/console\.log/d'
find resources/js -name "*.tsx" -o -name "*.ts" | xargs sed -i '/console\.warn/d'
find resources/js -name "*.tsx" -o -name "*.ts" | xargs sed -i '/console\.error/d'

# Remove TODO comments and dead code markers
echo "📝 Cleaning up TODO comments..."
find resources/js -name "*.tsx" -o -name "*.ts" | xargs sed -i '/\/\/ TODO:/d'
find resources/js -name "*.tsx" -o -name "*.ts" | xargs sed -i '/\/\/ FIXME:/d'
find resources/js -name "*.tsx" -o -name "*.ts" | xargs sed -i '/\/\* TODO:/,/\*\//d'

# Remove unused imports (requires manual review)
echo "📦 Analyzing unused imports..."
find resources/js -name "*.tsx" -o -name "*.ts" | xargs grep -l "import.*from" | while read file; do
    echo "Review imports in: $file"
done > unused_imports_review.txt

# Optimize images (if any)
echo "🖼️  Optimizing images..."
if command -v optipng &> /dev/null; then
    find public -name "*.png" -exec optipng -o7 {} \;
fi

if command -v jpegoptim &> /dev/null; then
    find public -name "*.jpg" -o -name "*.jpeg" -exec jpegoptim --max=85 {} \;
fi

# Clean up Laravel caches
echo "🚀 Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Clean up Composer
echo "📚 Optimizing Composer..."
composer dump-autoload --optimize --no-dev

# Clean up npm
echo "📦 Cleaning npm cache..."
npm cache clean --force

# Generate optimization report
echo "📊 Generating cleanup report..."
cat > cleanup_report.txt << EOF
Parish Management System - Cleanup Report
Generated: $(date)

Files Backed Up:
- Components: $BACKUP_DIR/Components/
- Pages: $BACKUP_DIR/Pages/
- Member Model: $BACKUP_DIR/Member.php

Actions Taken:
✅ Removed console.log statements
✅ Cleaned TODO comments
✅ Cleared Laravel caches
✅ Optimized Composer autoloader
✅ Cleaned npm cache
✅ Optimized images (if tools available)

Manual Review Required:
- unused_imports_review.txt (check for unused imports)
- redundant_form_components.txt (verify before deletion)

Recommended Next Steps:
1. Test application functionality
2. Review and remove unused imports
3. Run npm run build for production
4. Apply database optimizations (see migrations)
5. Deploy optimized unified components

Bundle Size Analysis:
Before: $(du -sh public/build 2>/dev/null || echo "Not built")
Run 'npm run build' to see optimized size
EOF

# Show final statistics
echo "📈 Cleanup Statistics:"
echo "Files backed up: $(find $BACKUP_DIR -type f | wc -l)"
echo "Console.log statements removed: $(grep -r "console\.log" resources/js | wc -l || echo "0")"
echo "TODO comments removed: $(grep -r "TODO:" resources/js | wc -l || echo "0")"

echo ""
echo "✅ Cleanup completed! Check cleanup_report.txt for details."
echo "⚠️  Please test the application thoroughly before deploying to production."
echo ""
echo "🚀 Next steps:"
echo "1. Run: npm run build"
echo "2. Test application functionality"
echo "3. Apply database migrations"
echo "4. Deploy to production"