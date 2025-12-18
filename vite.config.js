import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig(({ mode }) => ({
    resolve: {
        alias: {
            '@assets': path.resolve(__dirname, 'resources/images'),
        },
    },
    plugins: [
        vue(),
        laravel({
            input: ['resources/sass/app.scss', 'resources/js/app.js'],
            refresh: mode === 'development',
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: false,
        allowedHosts: true,
        hmr: mode === 'development' ? {
            clientPort: 443,
            protocol: 'wss',
        } : false,
    },
    build: {
        manifest: 'manifest.json',
        outDir: 'public/build',
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
        },
    },
}));
