import { reactive } from 'vue';

export const state = reactive({
    boot: null,
    bootConsumed: false,
    locale: 'ar',
    translations: {},
    chrome: null,
    flash: null,
    errors: {},
    refreshTick: 0,
});

export function pathname(url) {
    if (!url) {
        return '';
    }
    try {
        return new URL(url, window.location.origin).pathname;
    } catch {
        return String(url);
    }
}

export function setBoot(payload) {
    state.boot = payload;
    applyPayload(payload);
}

export function setErrors(errors) {
    state.errors = errors && typeof errors === 'object' ? errors : {};
}

export function applyPayload(payload) {
    const props = payload?.props ?? {};

    if (typeof props.locale === 'string') {
        state.locale = props.locale;
    }
    if (props.translations && typeof props.translations === 'object') {
        state.translations = props.translations;
    }
    if (props.adminChrome !== undefined && props.adminChrome !== null) {
        state.chrome = props.adminChrome;
    }
    state.flash = props.flash?.status ?? null;
    setErrors(props.errors);
}

export function hasErrors(props) {
    const errors = props?.errors;
    if (!errors || typeof errors !== 'object') {
        return false;
    }
    return Object.values(errors).some((v) => (Array.isArray(v) ? v.some(Boolean) : Boolean(v)));
}

export function clearFlash() {
    state.flash = null;
}

export function bumpRefresh() {
    state.refreshTick++;
}

let pendingPage = null;

export function setPending(payload) {
    pendingPage = payload;
}

export function consumePending(path) {
    if (!pendingPage || pathname(pendingPage.url) !== path) {
        return null;
    }
    const p = pendingPage;
    pendingPage = null;
    return p;
}
