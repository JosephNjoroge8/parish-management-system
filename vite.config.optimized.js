import { defineConfig } from 'vite';
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
                    return `js/${facadeModuleId}-[hash].js`;
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
                additionalData: `@import "resources/css/variables.scss";`,
            },
        },
    },
});