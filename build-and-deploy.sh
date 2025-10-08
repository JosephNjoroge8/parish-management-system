#!/bin/bash

# Local Build and Deploy Script for Parish Management System
# Run this script LOCALLY to build assets and prepare for production deployment

echo "🏗️  Parish Management System - Local Build & Deploy Preparation"
echo "=============================================================="

# Check if we're in the right directory
if [ ! -f "package.json" ] || [ ! -f "artisan" ]; then
    echo "❌ Error: package.json or artisan file not found. Please run this script from the Laravel root directory."
    exit 1
fi

# Set production environment for build
export NODE_ENV=production

# Install dependencies (including devDependencies for build)
echo "📦 Installing npm dependencies..."
if ! npm install; then
    echo "❌ npm install failed. Please check your package.json"
    exit 1
fi

# Verify Vite is available
echo "🔍 Verifying build tools..."
if ! npx vite --version &> /dev/null; then
    echo "❌ Vite not found. Trying to install..."
    npm install --save-dev vite || {
        echo "❌ Failed to install Vite"
        exit 1
    }
fi

# Clean previous builds
echo "🧹 Cleaning previous builds..."
rm -rf public/build/*

# Build production assets
echo "🔨 Building production assets..."
if npx vite build; then
    echo "✅ Build completed successfully!"
else
    echo "❌ Build failed! Trying alternative build command..."
    if npm run build:production; then
        echo "✅ Build completed with alternative command!"
    else
        echo "❌ All build attempts failed!"
        echo "📋 Debugging info:"
        echo "   Node version: $(node --version)"
        echo "   NPM version: $(npm --version)"
        echo "   Vite installed: $(npx vite --version 2>&1 || echo 'Not found')"
        exit 1
    fi
fi

# Show build results
echo ""
echo "📊 Build Results:"
echo "=================="
if [ -d "public/build" ]; then
    echo "📁 Build directory size:"
    du -sh public/build/
    echo ""
    echo "📄 Generated files:"
    find public/build -type f -name "*.js" -o -name "*.css" -o -name "*.json" | head -20
    echo ""
    if [ -f "public/build/manifest.json" ]; then
        echo "✅ Manifest file created"
    else
        echo "❌ Manifest file missing!"
    fi
else
    echo "❌ Build directory not created!"
    exit 1
fi

echo ""
echo "🚀 Next Steps for Production Deployment:"
echo "========================================"
echo "1. Upload these files to your production server:"
echo "   - All files in public/build/ directory"
echo "   - public/.htaccess (if updated)"
echo "   - production-deploy-fix.sh script"
echo ""
echo "2. On the production server, run:"
echo "   chmod +x production-deploy-fix.sh"
echo "   ./production-deploy-fix.sh"
echo ""
echo "📝 Files ready for deployment:"
echo "   public/build/ (entire directory)"
echo "   production-deploy-fix.sh"
echo ""
echo "🎯 Production URL: https://parish.quovadisyouthhub.org"
echo ""
echo "✅ Local build completed! Ready for production deployment."