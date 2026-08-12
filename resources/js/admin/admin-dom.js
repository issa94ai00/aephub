export const setupAdminMobileNav = () => {
    const btn = document.querySelector('[data-admin-nav-toggle]');
    const sidebar = document.querySelector('[data-admin-sidebar]');
    const overlay = document.querySelector('[data-admin-overlay]');
    if (!btn || !sidebar || !overlay) {
        return;
    }

    const dir = sidebar.dataset.sidebarDir === 'ltr' ? 'ltr' : 'rtl';
    const hiddenClass = dir === 'ltr' ? '-translate-x-full' : 'translate-x-full';
    const visibleClass = 'translate-x-0';

    const open = () => {
        sidebar.classList.remove(hiddenClass);
        sidebar.classList.add(visibleClass);
        overlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const close = () => {
        sidebar.classList.add(hiddenClass);
        sidebar.classList.remove(visibleClass);
        overlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    btn.addEventListener('click', () => {
        if (sidebar.classList.contains(hiddenClass)) {
            open();
        } else {
            close();
        }
    });

    overlay.addEventListener('click', close);

    window.addEventListener(
        'keydown',
        (e) => {
            if (e.key === 'Escape') {
                close();
            }
        },
        { passive: true }
    );
};

export const setupAdminScrollHeader = () => {
    const header = document.querySelector('[data-admin-header]');
    if (!header) {
        return;
    }

    const onScroll = () => {
        if (window.scrollY > 10) {
            header.classList.add('is-scrolled');
        } else {
            header.classList.remove('is-scrolled');
        }
    };

    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
};

export const setupAdminContentReveal = () => {
    const shell = document.querySelector('[data-admin-shell]');
    const container = shell?.querySelector('.admin-content');
    if (!container) {
        return;
    }

    const children = Array.from(container.children);
    if (!children.length) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduceMotion) {
        children.forEach((el) => {
            el.classList.add('admin-fade-up', 'is-visible');
        });
        return;
    }

    children.forEach((el) => {
        el.classList.add('admin-fade-up');
    });

    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting || entry.target !== container) {
                    return;
                }
                io.disconnect();
                children.forEach((el, i) => {
                    window.setTimeout(() => {
                        el.classList.add('is-visible');
                    }, i * 48);
                });
            });
        },
        { threshold: 0.04, rootMargin: '0px 0px -12px 0px' }
    );

    io.observe(container);
};

export const setupAdminBulkTables = () => {
    document.querySelectorAll('[data-bulk-table]').forEach((table) => {
        const container = table.closest('[data-bulk-container]') ?? table.parentElement;
        const checkboxes = table.querySelectorAll('[data-bulk-checkbox]');
        const selectAll = container.querySelector('[data-bulk-select-all]');
        const button = container.querySelector('[data-bulk-button]');
        const counter = container.querySelector('[data-bulk-counter]');
        if (!checkboxes.length || !button) {
            return;
        }

        const refresh = () => {
            const count = Array.from(checkboxes).filter((cb) => cb.checked).length;
            button.disabled = count === 0;
            button.classList.toggle('opacity-40', count === 0);
            if (counter) {
                counter.textContent = count > 0 ? `(${count})` : '';
            }
        };

        checkboxes.forEach((cb) => cb.addEventListener('change', refresh));

        selectAll?.addEventListener('change', (e) => {
            checkboxes.forEach((cb) => {
                cb.checked = e.target.checked;
            });
            refresh();
        });

        refresh();
    });
};

/**
 * Storage destination forms: show only the fields the chosen type uses.
 *
 * A local destination has no bucket, endpoint or keys, and only it has a
 * ceiling on a single request body. Hidden inputs are also disabled, because a
 * disabled input is not submitted — a hidden bucket that still posted would
 * fail the server's required rule for a type that never asked for it.
 *
 * Presentation only. The server validates per type regardless of what the
 * browser did, so a stale page cannot save a local destination with a bucket.
 *
 * Lives here rather than in a <script> on the page because admin screens are
 * injected into the Inertia shell with v-html, which does not run scripts.
 */
export const setupStorageDestinationForms = () => {
    const applyType = (select) => {
        const formId = select.getAttribute('data-storage-type');
        const isLocal = select.value === 'local';
        const scope = select.closest('form') ?? document;

        scope.querySelectorAll(`[data-form="${formId}"]`).forEach((group) => {
            const show = group.getAttribute('data-storage-group') === 'local' ? isLocal : !isLocal;
            group.style.display = show ? '' : 'none';

            group.querySelectorAll('input, select').forEach((input) => {
                input.disabled = !show;
            });
        });

        // S3 refuses multipart parts under 5 MiB; the local disk has no such
        // floor. The constraint moves with the type.
        const floor = isLocal ? 1 : 5;
        ['part_size_mb', 'recommended_part_size_mb'].forEach((name) => {
            const field = scope.querySelector(`[name="options[${name}]"]`);
            if (!field) {
                return;
            }
            field.min = String(floor);
            if (Number(field.value) < floor) {
                field.value = String(floor);
            }
        });
    };

    document.querySelectorAll('[data-storage-type]').forEach((select) => {
        if (select.dataset.storageTypeBound === '1') {
            return;
        }
        // The shell re-runs this after every visit; binding twice would apply
        // the toggle twice per change.
        select.dataset.storageTypeBound = '1';

        applyType(select);
        select.addEventListener('change', () => applyType(select));
    });
};

/**
 * Whether a storage transfer is currently running on this page.
 *
 * The move happens in a queue worker, so the page that started it has no way to
 * learn that it progressed. The shell polls while this marker is present.
 */
export const hasActiveStorageTransfer = () =>
    document.querySelector('[data-storage-transfer-active]') !== null;

export const setupAdminLoginReady = () => {
    const root = document.querySelector('[data-admin-login]');
    if (!root) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion) {
        root.classList.add('is-ready');
        return;
    }

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            root.classList.add('is-ready');
        });
    });
};
