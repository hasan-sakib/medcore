import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ReactDOMServer from 'react-dom/server';
import { PermissionProvider } from '@/contexts/PermissionContext';

const appName = import.meta.env.VITE_APP_NAME || 'MedCore';

export default createInertiaApp({
    title: (title) => `${title} — ${appName}`,

    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),

    setup({ App, props }) {
        return ReactDOMServer.renderToString(
            <PermissionProvider
                permissions={(props.initialPage.props as Record<string, unknown>).permissions as string[] ?? []}
                roles={(props.initialPage.props as Record<string, unknown>).roles as string[] ?? []}
            >
                <App {...props} />
            </PermissionProvider>,
        );
    },
});
