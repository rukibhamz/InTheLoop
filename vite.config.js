import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

/**
 * Apps served from a subdirectory (e.g. http://localhost/InTheLoop) must bake
 * that prefix into Vite asset URLs, or @font-face paths 404 at /build/...
 * Laravel's plugin expects the base to include the build directory.
 */
function resolveBase(env) {
    const raw = env.ASSET_URL || env.APP_URL || '';
    const buildDirectory = 'build';

    if (! raw) {
        return `/${buildDirectory}/`;
    }

    try {
        const pathname = new URL(raw).pathname.replace(/\/+$/, '');

        if (pathname === '' || pathname === '/') {
            return `/${buildDirectory}/`;
        }

        return `${pathname}/${buildDirectory}/`;
    } catch {
        return `/${buildDirectory}/`;
    }
}

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const base = resolveBase(env);

    return {
        base,
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
            tailwindcss(),
        ],
        server: {
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
