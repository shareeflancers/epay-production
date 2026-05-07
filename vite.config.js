import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        // --- ADD THIS SECTION ---
        hmr: {
            host: 'finch-runaround-bamboo.ngrok-free.dev',
            protocol: 'wss', // Uses secure websockets
        },
        // -------------------------
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
