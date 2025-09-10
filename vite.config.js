import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react(),
    ],
    build: {
        // Production build optimizations
        rollupOptions: {
            output: {
                // Code splitting for better performance
                manualChunks: {
                    vendor: ['react', 'react-dom'],
                    ui: ['@headlessui/react', '@heroicons/react', 'lucide-react'],
                    forms: ['react-hook-form', '@hookform/resolvers', 'yup'],
                    charts: ['recharts'],
                    utils: ['date-fns', 'lodash'],
                },
                // Optimize chunk sizes
                chunkSizeWarningLimit: 600,
            },
            // Reduce bundle size
            external: (id) => {
                // Externalize heavy dependencies if they're available via CDN
                return false; // Keep everything bundled for now
            }
        },
        sourcemap: false, // Disable source maps in production
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true, // Remove console.logs in production
                drop_debugger: true,
                pure_funcs: ['console.log', 'console.warn'], // Remove specific functions
            },
            mangle: {
                safari10: true,
            },
        },
        // Optimize CSS
        cssCodeSplit: true,
        cssMinify: true,
        // Optimize asset handling
        assetsInlineLimit: 4096, // Inline assets smaller than 4kb
        reportCompressedSize: false, // Skip compressed size reporting for faster builds
    },
    server: {
        host: '127.0.0.1',
        port: 5173,
        hmr: {
            host: '127.0.0.1',
            port: 5173,
        },
        watch: {
            usePolling: true,
            interval: 100, // Reduce polling interval for better performance
        },
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
            'lucide-react',
            'react-hook-form',
            'recharts',
            'date-fns'
        ],
        exclude: ['@vite/client', '@vite/env'],
    },
    // Ensure proper base URL for production
    base: process.env.NODE_ENV === 'production' ? '/build/' : '/',
    // Performance optimizations
    esbuild: {
        target: 'es2020',
        legalComments: 'none',
    },
    // CSS preprocessing optimizations
    css: {
        devSourcemap: false,
        postcss: './postcss.config.js',
    },
});
