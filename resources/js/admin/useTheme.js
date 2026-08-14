const STORAGE_KEY = 'admin-theme';
export const THEME_DARK = 'dark';
export const THEME_LIGHT = 'light';

const rootEl = () => (typeof document !== 'undefined' ? document.documentElement : null);

export function getStoredAdminTheme() {
    try {
        const value = localStorage.getItem(STORAGE_KEY);

        return value === THEME_LIGHT || value === THEME_DARK ? value : null;
    } catch {
        return null;
    }
}

export function getPreferredAdminTheme() {
    const stored = getStoredAdminTheme();
    if (stored) {
        return stored;
    }
    if (typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        return THEME_DARK;
    }

    return THEME_LIGHT;
}

export function isAdminLight() {
    return rootEl()?.classList.contains('light-theme') ?? false;
}

function storePreference(theme) {
    try {
        localStorage.setItem(STORAGE_KEY, theme);
    } catch {
        // localStorage unavailable (private mode / disabled) — ignore
    }
}

export function applyAdminTheme(theme, persist = true) {
    const html = rootEl();
    if (!html) {
        return;
    }
    if (theme === THEME_LIGHT) {
        html.classList.add('light-theme');
    } else {
        html.classList.remove('light-theme');
    }
    if (persist) {
        storePreference(theme);
    }
}

export function toggleAdminTheme() {
    const next = isAdminLight() ? THEME_DARK : THEME_LIGHT;
    applyAdminTheme(next, true);

    return next;
}

export function syncThemeToggleButtons() {
    const html = rootEl();
    if (!html) {
        return;
    }
    const isLight = html.classList.contains('light-theme');
    document.querySelectorAll('[data-admin-theme-toggle]').forEach((btn) => {
        btn.setAttribute('aria-pressed', isLight ? 'false' : 'true');

        const lightText = btn.getAttribute('data-admin-theme-label-light');
        const darkText = btn.getAttribute('data-admin-theme-label-dark');
        if (lightText && darkText) {
            btn.setAttribute('title', isLight ? darkText : lightText);
        }
    });
}

/**
 * Wires every `[data-admin-theme-toggle]` button in the document to the
 * admin day/night switch. Safe to call repeatedly: it is a no-op once bound
 * and re-syncs aria state on each invocation (the Blade shell re-renders the
 * header on every navigation).
 */
export function setupAdminTheme() {
    if (typeof document === 'undefined') {
        return;
    }

    applyAdminTheme(getPreferredAdminTheme(), false);

    document.querySelectorAll('[data-admin-theme-toggle]').forEach((btn) => {
        if (btn.dataset.adminThemeBound === '1') {
            return;
        }
        btn.dataset.adminThemeBound = '1';
        btn.addEventListener('click', () => {
            toggleAdminTheme();
            syncThemeToggleButtons();
        });
    });

    syncThemeToggleButtons();
}

export function initAdminTheme() {
    if (typeof document === 'undefined') {
        return;
    }
    applyAdminTheme(getPreferredAdminTheme(), false);
    syncThemeToggleButtons();
}
