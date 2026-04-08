import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'resources/js/reconciliation.js',
                'resources/js/reconciliation-audit.js'
            ],
            refresh: true,
        }),
    ],
});
