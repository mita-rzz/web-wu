import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'CyberNotes';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.{tsx,jsx}');
        const candidates = [
            `./pages/${name}.tsx`,
            `./pages/${name}.jsx`,
            `./pages/${name.toLowerCase()}.tsx`,
            `./pages/${name.toLowerCase()}.jsx`,
        ];

        for (const path of candidates) {
            if (path in pages) {
                return typeof pages[path] === 'function' ? (pages[path]() as Promise<any>) : pages[path];
            }
        }

        return resolvePageComponent(`./pages/${name}.tsx`, pages);
    },
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<App {...props} />);
    },
    progress: {
        color: '#2a72de',
    },
});
