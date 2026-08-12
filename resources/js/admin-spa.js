import './bootstrap';
import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import {
    setupAdminContentReveal,
    setupAdminMobileNav,
    setupAdminScrollHeader,
    setupAdminBulkTables,
    setupStorageDestinationForms,
    hasActiveStorageTransfer,
} from './admin/admin-dom';

const appName = import.meta.env.VITE_APP_NAME || 'LMS';

/** How often a running storage move is re-checked, in milliseconds. */
const STORAGE_TRANSFER_POLL_MS = 15000;

let storageTransferTimer = null;

/**
 * Keeps the storage move's progress bar honest.
 *
 * The move runs in a queue worker, so nothing pushes an update to the page that
 * started it. A partial reload re-fetches the props without a full navigation,
 * and stops as soon as the marker is gone — which is to say, as soon as the
 * transfer finished, failed or was stopped.
 */
const watchStorageTransfer = () => {
    if (storageTransferTimer) {
        window.clearTimeout(storageTransferTimer);
        storageTransferTimer = null;
    }

    if (!hasActiveStorageTransfer()) {
        return;
    }

    storageTransferTimer = window.setTimeout(() => {
        router.reload({ preserveScroll: true });
    }, STORAGE_TRANSFER_POLL_MS);
};

const bootChrome = () => {
    setupAdminMobileNav();
    setupAdminScrollHeader();
    setupAdminContentReveal();
    setupAdminBulkTables();
    setupStorageDestinationForms();
    watchStorageTransfer();
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
