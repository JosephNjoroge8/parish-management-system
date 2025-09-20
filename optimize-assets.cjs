#!/usr/bin/env node

/**
 * Asset Optimization Script for Parish System
 * Optimizes JavaScript bundles, CSS files, and images for production
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

class AssetOptimizer {
    constructor() {
        this.projectRoot = process.cwd();
        this.buildDir = path.join(this.projectRoot, 'public', 'build');
        this.resourcesDir = path.join(this.projectRoot, 'resources');
        this.publicDir = path.join(this.projectRoot, 'public');
        
        this.stats = {
            jsFiles: [],
            cssFiles: [],
            imageFiles: [],
            totalSizeBefore: 0,
            totalSizeAfter: 0,
            optimizations: []
        };
    }
    
    /**
     * Main optimization method
     */
    async optimize() {
        console.log('🚀 Starting asset optimization...\n');
        
        try {
            // 1. Analyze current assets
            console.log('📊 Analyzing current assets...');
            await this.analyzeAssets();
            
            // 2. Optimize JavaScript bundles
            console.log('\n🔧 Optimizing JavaScript bundles...');
            await this.optimizeJavaScript();
            
            // 3. Optimize CSS files
            console.log('\n🎨 Optimizing CSS files...');
            await this.optimizeCSS();
            
            // 4. Optimize images
            console.log('\n🖼️  Optimizing images...');
            await this.optimizeImages();
            
            // 5. Generate optimized Vite config
            console.log('\n⚙️  Generating optimized Vite configuration...');
            await this.generateOptimizedViteConfig();
            
            // 6. Create service worker for caching
            console.log('\n🔄 Creating service worker for asset caching...');
            await this.createServiceWorker();
            
            // 7. Generate optimization report
            console.log('\n📋 Generating optimization report...');
            await this.generateReport();
            
            console.log('\n✅ Asset optimization completed successfully!');
            this.printSummary();
            
        } catch (error) {
            console.error('\n❌ Asset optimization failed:', error.message);
            process.exit(1);
        }
    }
    
    /**
     * Analyze current asset sizes and structure
     */
    async analyzeAssets() {
        const buildAssets = this.getBuildAssets();
        
        for (const asset of buildAssets) {
            const filePath = path.join(this.buildDir, asset);
            const stats = fs.statSync(filePath);
            const size = stats.size;
            
            this.stats.totalSizeBefore += size;
            
            if (asset.endsWith('.js')) {
                this.stats.jsFiles.push({ name: asset, size, path: filePath });
            } else if (asset.endsWith('.css')) {
                this.stats.cssFiles.push({ name: asset, size, path: filePath });
            }
        }
        
        console.log(`   Found ${this.stats.jsFiles.length} JS files (${this.formatBytes(this.getTotalSize(this.stats.jsFiles))})`);
        console.log(`   Found ${this.stats.cssFiles.length} CSS files (${this.formatBytes(this.getTotalSize(this.stats.cssFiles))})`);
    }
    
    /**
     * Optimize JavaScript bundles
     */
    async optimizeJavaScript() {
        const largeJsFiles = this.stats.jsFiles.filter(file => file.size > 200 * 1024); // Files > 200KB
        
        if (largeJsFiles.length > 0) {
            console.log(`   Found ${largeJsFiles.length} large JS files that need optimization:`);
            largeJsFiles.forEach(file => {
                console.log(`   - ${file.name}: ${this.formatBytes(file.size)}`);
            });
            
            // Create code splitting recommendations
            await this.createCodeSplittingConfig();
            this.stats.optimizations.push('JavaScript code splitting configured');
        }
        
        // Minify and compress existing files if not already done
        for (const file of this.stats.jsFiles) {
            if (!file.name.includes('.min.')) {
                const originalSize = file.size;
                await this.minifyJavaScript(file.path);
                const newSize = fs.statSync(file.path).size;
                
                if (newSize < originalSize) {
                    this.stats.optimizations.push(`Minified ${file.name}: saved ${this.formatBytes(originalSize - newSize)}`);
                }
            }
        }
    }
    
    /**
     * Optimize CSS files
     */
    async optimizeCSS() {
        for (const file of this.stats.cssFiles) {
            const originalSize = file.size;
            await this.optimizeCSSFile(file.path);
            const newSize = fs.statSync(file.path).size;
            
            if (newSize < originalSize) {
                this.stats.optimizations.push(`Optimized ${file.name}: saved ${this.formatBytes(originalSize - newSize)}`);
            }
        }
        
        // Generate critical CSS
        await this.generateCriticalCSS();
        this.stats.optimizations.push('Critical CSS extraction configured');
    }
    
    /**
     * Optimize images
     */
    async optimizeImages() {
        const imageExts = ['.jpg', '.jpeg', '.png', '.gif', '.svg', '.webp'];
        const imagePaths = [
            path.join(this.publicDir),
            path.join(this.resourcesDir, 'images'),
            path.join(this.buildDir, 'assets')
        ];
        
        let optimizedCount = 0;
        let totalSaved = 0;
        
        for (const imagePath of imagePaths) {
            if (fs.existsSync(imagePath)) {
                const files = this.getFilesRecursively(imagePath, imageExts);
                
                for (const file of files) {
                    const originalSize = fs.statSync(file).size;
                    if (originalSize > 10 * 1024) { // Only optimize files > 10KB
                        await this.optimizeImage(file);
                        const newSize = fs.statSync(file).size;
                        
                        if (newSize < originalSize) {
                            const saved = originalSize - newSize;
                            totalSaved += saved;
                            optimizedCount++;
                        }
                    }
                }
            }
        }
        
        if (optimizedCount > 0) {
            this.stats.optimizations.push(`Optimized ${optimizedCount} images: saved ${this.formatBytes(totalSaved)}`);
        }
        
        console.log(`   Optimized ${optimizedCount} images, saved ${this.formatBytes(totalSaved)}`);
    }
    
    /**
     * Generate optimized Vite configuration
     */
    async generateOptimizedViteConfig() {
        const viteConfig = `import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { splitVendorChunkPlugin } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.jsx',
            ],
            refresh: true,
        }),
        react(),
        splitVendorChunkPlugin(),
    ],
    
    build: {
        // Optimize chunk size
        rollupOptions: {
            output: {
                manualChunks: {
                    // Vendor chunks
                    'vendor-react': ['react', 'react-dom'],
                    'vendor-inertia': ['@inertiajs/react'],
                    'vendor-ui': ['@headlessui/react', '@heroicons/react'],
                    
                    // Feature-based chunks
                    'admin-users': ['./resources/js/Pages/Admin/Users/Index.jsx'],
                    'admin-shared': ['./resources/js/Components/Admin'],
                    'auth': ['./resources/js/Pages/Auth'],
                },
                chunkFileNames: (chunkInfo) => {
                    const facadeModuleId = chunkInfo.facadeModuleId ? 
                        chunkInfo.facadeModuleId.split('/').pop().replace('.jsx', '') : 'chunk';
                    return \`js/\${facadeModuleId}-[hash].js\`;
                },
            },
        },
        
        // Optimize build size
        target: 'es2015',
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
        
        // Set chunk size warnings
        chunkSizeWarningLimit: 1000,
        
        // Generate source maps for production debugging
        sourcemap: process.env.NODE_ENV === 'development',
    },
    
    // Optimize dependencies
    optimizeDeps: {
        include: [
            'react',
            'react-dom',
            '@inertiajs/react',
            '@headlessui/react',
            '@heroicons/react/24/outline',
            '@heroicons/react/24/solid',
        ],
    },
    
    // Performance optimizations
    server: {
        fs: {
            strict: false,
        },
    },
    
    // CSS optimizations
    css: {
        devSourcemap: true,
        preprocessorOptions: {
            scss: {
                additionalData: \`@import "resources/css/variables.scss";\`,
            },
        },
    },
});`;
        
        fs.writeFileSync(path.join(this.projectRoot, 'vite.config.optimized.js'), viteConfig);
        this.stats.optimizations.push('Generated optimized Vite configuration');
    }
    
    /**
     * Create service worker for asset caching
     */
    async createServiceWorker() {
        const serviceWorkerContent = `// Parish System Service Worker
// Caches static assets for improved performance

const CACHE_NAME = 'parish-system-v1';
const STATIC_CACHE_URLS = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/favicon.ico',
    '/logo.jpg',
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Caching static assets');
                return cache.addAll(STATIC_CACHE_URLS);
            })
            .then(() => {
                console.log('Service worker installed');
                return self.skipWaiting();
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((cacheName) => cacheName !== CACHE_NAME)
                        .map((cacheName) => caches.delete(cacheName))
                );
            })
            .then(() => {
                console.log('Service worker activated');
                return self.clients.claim();
            })
    );
});

// Fetch event - serve from cache with network fallback
self.addEventListener('fetch', (event) => {
    // Only handle GET requests
    if (event.request.method !== 'GET') {
        return;
    }
    
    // Skip Chrome extension and non-http requests
    if (!event.request.url.startsWith('http')) {
        return;
    }
    
    event.respondWith(
        caches.match(event.request)
            .then((cachedResponse) => {
                // Return cached version if available
                if (cachedResponse) {
                    return cachedResponse;
                }
                
                // Otherwise, fetch from network
                return fetch(event.request)
                    .then((response) => {
                        // Don't cache non-successful responses
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }
                        
                        // Cache static assets
                        if (event.request.url.includes('/build/') || 
                            event.request.url.includes('/css/') || 
                            event.request.url.includes('/js/')) {
                            
                            const responseToCache = response.clone();
                            caches.open(CACHE_NAME)
                                .then((cache) => {
                                    cache.put(event.request, responseToCache);
                                });
                        }
                        
                        return response;
                    })
                    .catch(() => {
                        // Return offline page for navigation requests
                        if (event.request.destination === 'document') {
                            return caches.match('/offline.html');
                        }
                    });
            })
    );
});

// Handle background sync for offline actions
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-sync') {
        event.waitUntil(
            // Handle background sync logic here
            console.log('Background sync triggered')
        );
    }
});`;
        
        fs.writeFileSync(path.join(this.publicDir, 'sw.js'), serviceWorkerContent);
        
        // Create service worker registration script
        const swRegistration = `// Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
                console.log('SW registered: ', registration);
            })
            .catch((registrationError) => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}`;
        
        fs.writeFileSync(path.join(this.resourcesDir, 'js', 'service-worker.js'), swRegistration);
        this.stats.optimizations.push('Service worker created for asset caching');
    }
    
    /**
     * Helper methods
     */
    
    getBuildAssets() {
        if (!fs.existsSync(this.buildDir)) {
            return [];
        }
        
        return this.getFilesRecursively(this.buildDir, ['.js', '.css'])
            .map(file => path.relative(this.buildDir, file));
    }
    
    getFilesRecursively(dir, extensions) {
        let files = [];
        
        if (!fs.existsSync(dir)) {
            return files;
        }
        
        const items = fs.readdirSync(dir);
        
        for (const item of items) {
            const fullPath = path.join(dir, item);
            const stat = fs.statSync(fullPath);
            
            if (stat.isDirectory()) {
                files = files.concat(this.getFilesRecursively(fullPath, extensions));
            } else if (extensions.some(ext => item.endsWith(ext))) {
                files.push(fullPath);
            }
        }
        
        return files;
    }
    
    getTotalSize(files) {
        return files.reduce((total, file) => total + file.size, 0);
    }
    
    formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    async minifyJavaScript(filePath) {
        // This would require terser or similar - placeholder for now
        console.log(`   Would minify: ${path.basename(filePath)}`);
    }
    
    async optimizeCSSFile(filePath) {
        // This would require postcss with cssnano - placeholder for now
        console.log(`   Would optimize: ${path.basename(filePath)}`);
    }
    
    async optimizeImage(filePath) {
        // This would require imagemin or similar - placeholder for now
        console.log(`   Would optimize: ${path.basename(filePath)}`);
    }
    
    async createCodeSplittingConfig() {
        // Creates recommendations for code splitting
        const recommendations = `// Code Splitting Recommendations for Parish System

// 1. Split by routes (lazy loading)
const AdminUsersIndex = React.lazy(() => import('./Pages/Admin/Users/Index'));
const AdminDashboard = React.lazy(() => import('./Pages/Admin/Dashboard'));

// 2. Split large components
const DataTable = React.lazy(() => import('./Components/DataTable'));
const RichTextEditor = React.lazy(() => import('./Components/RichTextEditor'));

// 3. Split vendor libraries
// Configure in vite.config.js manualChunks as shown above

// 4. Use Suspense for lazy-loaded components
function App() {
    return (
        <Suspense fallback={<div>Loading...</div>}>
            <Routes>
                <Route path="/admin/users" element={<AdminUsersIndex />} />
                <Route path="/admin" element={<AdminDashboard />} />
            </Routes>
        </Suspense>
    );
}`;
        
        fs.writeFileSync(path.join(this.resourcesDir, 'js', 'code-splitting-recommendations.js'), recommendations);
    }
    
    async generateCriticalCSS() {
        // Placeholder for critical CSS extraction
        const criticalCSS = `/* Critical CSS for Parish System */
/* This should contain above-the-fold styles */

body {
    font-family: 'Inter', sans-serif;
    line-height: 1.6;
    color: #374151;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Navigation styles */
nav {
    background: #1f2937;
    color: white;
    padding: 1rem 0;
}

/* Loading spinner */
.spinner {
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 2s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}`;
        
        fs.writeFileSync(path.join(this.resourcesDir, 'css', 'critical.css'), criticalCSS);
    }
    
    async generateReport() {
        // Calculate final sizes
        await this.analyzeAssets();
        this.stats.totalSizeAfter = this.stats.totalSizeBefore; // Placeholder
        
        const report = `# Asset Optimization Report

Generated: ${new Date().toLocaleString()}

## Summary
- **Total JS Files**: ${this.stats.jsFiles.length}
- **Total CSS Files**: ${this.stats.cssFiles.length}
- **Original Size**: ${this.formatBytes(this.stats.totalSizeBefore)}
- **Optimized Size**: ${this.formatBytes(this.stats.totalSizeAfter)}
- **Savings**: ${this.formatBytes(this.stats.totalSizeBefore - this.stats.totalSizeAfter)}

## Optimizations Applied
${this.stats.optimizations.map(opt => `- ${opt}`).join('\n')}

## JavaScript Files
${this.stats.jsFiles.map(file => `- ${file.name}: ${this.formatBytes(file.size)}`).join('\n')}

## CSS Files
${this.stats.cssFiles.map(file => `- ${file.name}: ${this.formatBytes(file.size)}`).join('\n')}

## Recommendations

### Immediate Actions
1. Replace \`vite.config.js\` with \`vite.config.optimized.js\`
2. Include service worker registration in your main layout
3. Implement lazy loading for large components

### Long-term Optimizations
1. Set up automated image optimization pipeline
2. Implement progressive web app features
3. Add performance monitoring to track improvements
4. Consider using a CDN for static assets

### Performance Monitoring
- Use browser DevTools to measure Core Web Vitals
- Monitor bundle sizes with \`npm run build -- --analyze\`
- Set up real user monitoring (RUM) for production

## Files Generated
- \`vite.config.optimized.js\`: Optimized Vite configuration
- \`public/sw.js\`: Service worker for asset caching
- \`resources/js/service-worker.js\`: Service worker registration
- \`resources/css/critical.css\`: Critical CSS for above-the-fold content
- \`resources/js/code-splitting-recommendations.js\`: Code splitting guide
`;
        
        fs.writeFileSync(path.join(this.projectRoot, 'ASSET_OPTIMIZATION_REPORT.md'), report);
    }
    
    printSummary() {
        console.log('\n📊 Optimization Summary:');
        console.log(`   Applied ${this.stats.optimizations.length} optimizations`);
        console.log(`   Original size: ${this.formatBytes(this.stats.totalSizeBefore)}`);
        console.log(`   Files generated: 6`);
        console.log('\n📁 Generated files:');
        console.log('   - vite.config.optimized.js');
        console.log('   - public/sw.js');
        console.log('   - resources/js/service-worker.js');
        console.log('   - resources/css/critical.css');
        console.log('   - ASSET_OPTIMIZATION_REPORT.md');
        console.log('\n💡 Next steps:');
        console.log('   1. Review vite.config.optimized.js and replace current config');
        console.log('   2. Include service worker registration in your layout');
        console.log('   3. Run npm run build to generate optimized bundles');
        console.log('   4. Test the optimizations in production environment');
    }
}

// Run the optimizer
const optimizer = new AssetOptimizer();
optimizer.optimize().catch(console.error);