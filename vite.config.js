import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Les polices sont rapatriées et servies depuis notre propre
            // domaine, jamais depuis un CDN : l'application doit fonctionner
            // en mode avion, et une police qui ne charge pas casse la
            // lisibilité de tout l'écran.
            fonts: [
                bunny('Archivo', { weights: [600, 700] }),
                bunny('IBM Plex Sans', { weights: [400, 500, 600] }),
                bunny('IBM Plex Mono', { weights: [400, 500, 600] }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
