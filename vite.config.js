import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        origin: 'http://localhost:5185',
        cors: true,
        hmr: {
            host: 'localhost',
            port: 5185,
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
