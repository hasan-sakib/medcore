import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.tsx',
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        cors: true,
        hmr: {
            host: 'localhost',
        },
        proxy: {
            // Forward all non-Vite requests to nginx so localhost:5173
            // serves the full Laravel/Inertia app with HMR.
            '^/(?!(@vite|@react-refresh|@id|@fs|resources/|node_modules))': {
                target: 'http://nginx:80',
                changeOrigin: false,
                ws: false,
            },
        },
    },
    optimizeDeps: {
        esbuildOptions: {
            sourcemap: false,
        },
    },
});
