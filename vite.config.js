import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        proxy: {
            '/api': {
                target: process.env.VITE_DEV_SERVER_PROXY ?? 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
            '/sanctum': {
                target: process.env.VITE_DEV_SERVER_PROXY ?? 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
            '/dashboard': {
                target: process.env.VITE_DEV_SERVER_PROXY ?? 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
        },
    },
});
