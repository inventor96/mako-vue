import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import vueDevTools from 'vite-plugin-vue-devtools';

export default defineConfig(({ mode }) => {
    // get environment variables
    const env = loadEnv(mode, process.cwd(), '');
    const viteDomain = env.VITE_DOMAIN || 'localhost';

    return {
        base: '/', // ensure bundled assets are served from the root
        plugins: [
            laravel({
                input: 'app/resources/js/app.js',
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
            vueDevTools({
                appendTo: 'app/resources/js/app.js',
            }),
        ],
        resolve: {
            alias: {
                '@': path.resolve(__dirname, 'app/resources/views'),
            },
        },
        build: {
            outDir: 'public',
            assetsDir: 'build',
            emptyOutDir: false, // don't delete the entire public directory, just the build assets
        },
        css: {
            preprocessorOptions: {
                scss: {
                    // ignore warnings from Bootstrap
                    silenceDeprecations: [
                        'import',
                        //'mixed-decls',
                        'color-functions',
                        'global-builtin',
                        'if-function',
                    ],
                },
            },
        },
        // custom networking settings to allow working with domains, docker, and https
        server: {
            host: true,
            port: 5173,
            https: false,
            hmr: {
                host: viteDomain,
                port: 5173,
                protocol: 'wss',
            },
            cors: {
                origin: [
                    `https://${viteDomain}:443`,
                    `https://${viteDomain}`,
                ],
            },
        },
    };
});