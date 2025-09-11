#!/usr/bin/env node

/**
 * Production Build Verification Script
 * Ensures all assets are properly built for production
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

console.log('🔍 Verifying production build...\n');

// Check if build directory exists
const buildDir = path.join(__dirname, 'public', 'build');
if (!fs.existsSync(buildDir)) {
    console.error('❌ Build directory missing!');
    console.error('Run: npm run build');
    process.exit(1);
}

// Check if manifest exists
const manifestPath = path.join(buildDir, '.vite', 'manifest.json');
if (!fs.existsSync(manifestPath)) {
    console.error('❌ Build manifest missing!');
    console.error('Run: npm run build');
    process.exit(1);
}

// Read and verify manifest
const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
const manifestStr = JSON.stringify(manifest);

// Check for dev server URLs (should not exist in production)
const devPatterns = [
    'localhost:5173',
    '127.0.0.1:5173',
    'http://localhost',
    'http://127.0.0.1'
];

const hasDevUrls = devPatterns.some(pattern => manifestStr.includes(pattern));

if (hasDevUrls) {
    console.error('❌ Manifest contains development URLs!');
    console.error('This will cause ERR_BLOCKED_BY_CLIENT errors in production.');
    console.error('Check your Vite configuration.');
    process.exit(1);
}

// Check if critical assets exist
const criticalAssets = ['resources/js/app.tsx', 'resources/css/app.css'];
const missingAssets = criticalAssets.filter(asset => !manifest[asset]);

if (missingAssets.length > 0) {
    console.error('❌ Missing critical assets:', missingAssets);
    process.exit(1);
}

// Verify asset files actually exist
let missingFiles = 0;
Object.values(manifest).forEach(asset => {
    if (typeof asset === 'object' && asset.file) {
        const assetPath = path.join(buildDir, asset.file);
        if (!fs.existsSync(assetPath)) {
            console.error(`❌ Missing asset file: ${asset.file}`);
            missingFiles++;
        }
    }
});

if (missingFiles > 0) {
    console.error(`❌ ${missingFiles} asset files are missing!`);
    process.exit(1);
}

// Success
console.log('✅ Build verification passed!');
console.log('📦 Assets are ready for production deployment');
console.log('🚀 No dev server URLs detected');
console.log('📁 All asset files exist');
console.log('\n🎉 Your build is production-ready!');
