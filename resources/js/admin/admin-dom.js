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
