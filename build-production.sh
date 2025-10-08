#!/bin/bash

# Local Build Script for Parish Management System
# Builds assets locally for production deployment

echo "🏗️  Parish Management System - Production Build"
echo "=============================================="

# Check directory
if [ ! -f "package.json" ] || [ ! -f "artisan" ]; then
    echo "❌ Error: Run this script from the Laravel root directory."
    exit 1
fi

# Set production environment
export NODE_ENV=production

# Clean and build
echo "🧹 Cleaning previous builds..."
rm -rf public/build/*

echo "🔨 Building production assets..."
if npm run build; then
    echo "✅ Build completed successfully!"
else
    echo "❌ Build failed!"
    exit 1
fi

# Verify build
if [ -f "public/build/manifest.json" ]; then
    echo "✅ Build verified - manifest.json created"
    echo "📊 Build summary:"
    du -sh public/build/
    echo "📄 Asset files: $(find public/build -name "*.js" -o -name "*.css" | wc -l)"
else
    echo "❌ Build verification failed!"
    exit 1
fi

echo ""
echo "🚀 Ready for production deployment!"
echo "Upload public/build/ directory and production-deploy-fix.sh to server"