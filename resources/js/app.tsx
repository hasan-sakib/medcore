import './bootstrap';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { PermissionProvider } from '@/contexts/PermissionContext';

const appName = import.meta.env.VITE_APP_NAME || 'MedCore';

createInertiaApp({
    title: (title) => `${title} — ${appName}`,

    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),

    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(
            <PermissionProvider
                permissions={(props.initialPage.props as Record<string, unknown>).permissions as string[] ?? []}
                roles={(props.initialPage.props as Record<string, unknown>).roles as string[] ?? []}
            >
                <App {...props} />
            </PermissionProvider>,
        );
    },

    progress: {
        color: '#2563eb',
    },
});
