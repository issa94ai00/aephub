import './bootstrap';
import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { bootSiteDom } from './site/site-dom';

const appName = import.meta.env.VITE_APP_NAME || 'LMS';

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);
        app.mount(el);
        bootSiteDom();
    },
    progress: {
        color: '#059669',
    },
});

router.on('finish', () => bootSiteDom());
