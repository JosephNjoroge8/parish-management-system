#!/bin/bash

# Quick Asset Verification Script
# Run this on production server to check asset files

echo "🔍 Asset Verification Report"
echo "============================"
echo "Date: $(date)"
echo ""

# Check if we're in Laravel directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Not in Laravel directory"
    exit 1
fi

echo "📁 Build Directory Status:"
if [ -d "public/build" ]; then
    echo "✅ public/build/ exists"
    
    # Count files
    CSS_COUNT=$(find public/build -name "*.css" | wc -l)
    JS_COUNT=$(find public/build -name "*.js" | wc -l)
    TOTAL_COUNT=$(find public/build -type f | wc -l)
    
    echo "📊 File counts:"
    echo "   CSS files: $CSS_COUNT"
    echo "   JS files: $JS_COUNT"
    echo "   Total files: $TOTAL_COUNT"
    
    if [ "$TOTAL_COUNT" -lt 50 ]; then
        echo "⚠️  Warning: Only $TOTAL_COUNT files found. Expected 80+"
    else
        echo "✅ Asset count looks good"
    fi
else
    echo "❌ public/build/ directory missing!"
fi

echo ""
echo "📋 Manifest Check:"
if [ -f "public/build/manifest.json" ]; then
    echo "✅ manifest.json exists"
    
    # Show key assets
    echo ""
    echo "🔑 Key assets in manifest:"
    if command -v jq &> /dev/null; then
        echo "Main app files:"
        jq -r 'to_entries[] | select(.key | test("resources/js/app")) | .value.file' public/build/manifest.json 2>/dev/null || echo "Unable to parse with jq"
    else
        echo "Main CSS:"
        grep -o '"assets/app-[^"]*\.css"' public/build/manifest.json | head -1
        echo "Main JS:"
        grep -o '"assets/app-[^"]*\.js"' public/build/manifest.json | head -1
        echo "Vendor JS:"
        grep -o '"assets/vendor-[^"]*\.js"' public/build/manifest.json | head -1
        echo "Inertia JS:"
        grep -o '"assets/inertia-[^"]*\.js"' public/build/manifest.json | head -1
    fi
else
    echo "❌ manifest.json missing!"
fi

echo ""
echo "🔍 Sample Asset Files:"
echo "====================="
if [ -d "public/build/assets" ]; then
    echo "First 10 files in assets directory:"
    ls -la public/build/assets/ | head -11
else
    echo "❌ assets directory missing!"
fi

echo ""
echo "🔗 Testing Asset Access:"
echo "========================"
if [ -f "public/build/manifest.json" ]; then
    # Test if we can read a sample asset
    SAMPLE_CSS=$(find public/build/assets -name "app-*.css" | head -1)
    SAMPLE_JS=$(find public/build/assets -name "app-*.js" | head -1)
    
    if [ -n "$SAMPLE_CSS" ] && [ -f "$SAMPLE_CSS" ]; then
        CSS_SIZE=$(stat -f%z "$SAMPLE_CSS" 2>/dev/null || stat -c%s "$SAMPLE_CSS" 2>/dev/null || echo "unknown")
        echo "✅ CSS asset accessible: $(basename $SAMPLE_CSS) ($CSS_SIZE bytes)"
    else
        echo "❌ No CSS assets found!"
    fi
    
    if [ -n "$SAMPLE_JS" ] && [ -f "$SAMPLE_JS" ]; then
        JS_SIZE=$(stat -f%z "$SAMPLE_JS" 2>/dev/null || stat -c%s "$SAMPLE_JS" 2>/dev/null || echo "unknown")
        echo "✅ JS asset accessible: $(basename $SAMPLE_JS) ($JS_SIZE bytes)"
    else
        echo "❌ No JS assets found!"
    fi
fi

echo ""
echo "📝 Recommendations:"
echo "=================="
if [ "$TOTAL_COUNT" -lt 50 ]; then
    echo "🚨 CRITICAL: Asset count is too low"
    echo "   - Run 'git pull origin Main' to get latest assets"
    echo "   - Verify all files were uploaded properly"
    echo "   - Check if .gitignore is blocking asset files"
fi

if [ ! -f "public/build/manifest.json" ]; then
    echo "🚨 CRITICAL: Missing manifest.json"
    echo "   - Run 'npm run build' locally"
    echo "   - Commit and push the public/build/ directory"
    echo "   - Pull changes on production server"
fi

echo ""
echo "✅ Asset verification completed"