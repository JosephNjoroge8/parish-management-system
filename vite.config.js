import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react({
            // Simplified React configuration
            fastRefresh: true,
        }),
    ],
    server: {
        host: 'localhost',
        port: 5173,
        hmr: {
            host: 'localhost',
            port: 5173,
            clientPort: 5173,
        },
        watch: {
            usePolling: true,
        },
    },
    build: {
        outDir: 'public/build',
        manifest: 'manifest.json',
        emptyOutDir: true,
        sourcemap: false,
        minify: 'terser',
        cssMinify: true,
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['react', 'react-dom'],
                    inertia: ['@inertiajs/react'],
                    lodash: ['lodash'],
                },
                assetFileNames: 'assets/[name]-[hash].[ext]',
                chunkFileNames: 'assets/[name]-[hash].js',
                entryFileNames: 'assets/[name]-[hash].js',
                // Ensure proper file extensions
                format: 'es',
            },
            onwarn(warning, warn) {
                // Suppress certain warnings
                if (warning.code === 'MODULE_LEVEL_DIRECTIVE') {
                    return;
                }
                warn(warning);
            }
        },
        terserOptions: {
            compress: {
                drop_console: process.env.NODE_ENV === 'production',
                drop_debugger: true,
                pure_funcs: process.env.NODE_ENV === 'production' ? ['console.log'] : [],
            },
            mangle: {
                safari10: true, // Fix Safari 10 issues
            },
            format: {
                comments: false,
            }
        },
        chunkSizeWarningLimit: 1000,
        // Ensure CSS is properly processed
        cssCodeSplit: true,
        assetsInlineLimit: 4096,
        // Production-specific optimizations
        target: 'es2020',
        modulePreload: {
            polyfill: true
        },
    },
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    // Improve error handling
    optimizeDeps: {
        exclude: ['laravel-vite-plugin'],
        include: ['react', 'react-dom']
    },
    // Fix CSS issues
    css: {
        devSourcemap: true,
        preprocessorOptions: {
            css: {
                charset: false
            }
        }
    },
    // Add error overlay configuration
    define: {
        __DEV__: process.env.NODE_ENV !== 'production',
    }
});
