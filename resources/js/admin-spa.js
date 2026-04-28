import './bootstrap';
import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { setupAdminContentReveal, setupAdminMobileNav, setupAdminScrollHeader } from './admin/admin-dom';

const appName = import.meta.env.VITE_APP_NAME || 'LMS';

const bootChrome = () => {
    setupAdminMobileNav();
    setupAdminScrollHeader();
    setupAdminContentReveal();
};

router.on('finish', () => {
    bootChrome();
});

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);
        app.mount(el);
        bootChrome();
    },
    progress: {
        color: '#34d399',
    },
});
