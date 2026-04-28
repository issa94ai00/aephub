<script setup>
import AdminLayout from '../../Layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const page = usePage();

const props = defineProps({
    html: { type: String, required: true },
    title: { type: String, required: true },
    heading: { type: String, required: true },
    subheading: { type: String, default: null },
});

const host = ref(null);

let unbindClick = () => {};
let unbindSubmit = () => {};

function adminPathMatches(pathname) {
    return pathname === '/admin' || pathname.startsWith('/admin/');
}

function routeNameMatches(current, pattern) {
    if (!current || !pattern) {
        return false;
    }
    if (pattern.endsWith('.*')) {
        const base = pattern.slice(0, -2);

        return current === base || current.startsWith(`${base}.`);
    }

    return current === pattern;
}

function isSpaAdminUrl(url) {
    if (url.pathname === '/admin/login') {
        return false;
    }
    return adminPathMatches(url.pathname);
}

function bindSpaNavigation(el) {
    const onClick = (e) => {
        const a = e.target.closest?.('a[href]');
        if (!a || !el.contains(a)) {
            return;
        }
        if (a.dataset.spaSkip === '1') {
            return;
        }
        const href = a.getAttribute('href');
        if (!href || href.startsWith('#')) {
            return;
        }
        if (a.target === '_blank' || a.hasAttribute('download')) {
            return;
        }
        let url;
        try {
            url = new URL(href, window.location.origin);
        } catch {
            return;
        }
        if (url.origin !== window.location.origin) {
            return;
        }
        if (!isSpaAdminUrl(url)) {
            return;
        }
        e.preventDefault();
        router.visit(`${url.pathname}${url.search}${url.hash}`);
    };
    el.addEventListener('click', onClick);

    return () => el.removeEventListener('click', onClick);
}

function bindSpaForms(el) {
    const onSubmit = (e) => {
        if (e.defaultPrevented) {
            return;
        }
        const form = e.target.closest?.('form');
        if (!form || !el.contains(form) || form.dataset.spaSkip === '1') {
            return;
        }
        let actionUrl;
        try {
            actionUrl = new URL(form.action, window.location.origin);
        } catch {
            return;
        }
        if (actionUrl.origin !== window.location.origin || !adminPathMatches(actionUrl.pathname)) {
            return;
        }
        e.preventDefault();
        const fd = new FormData(form);
        const methodField = form.querySelector('input[name="_method"]');
        const spoof = methodField?.value?.toLowerCase();
        const rawMethod = (form.getAttribute('method') || 'get').toLowerCase();
        const effective = spoof || rawMethod;

        if (effective === 'get') {
            const q = new URLSearchParams(fd).toString();
            const url = q ? `${actionUrl.pathname}?${q}` : actionUrl.pathname;
            router.visit(url);

            return;
        }

        router.visit(form.action, {
            method: 'post',
            data: fd,
            preserveScroll: true,
        });
    };
    el.addEventListener('submit', onSubmit);

    return () => el.removeEventListener('submit', onSubmit);
}

function rebind() {
    unbindClick();
    unbindSubmit();
    const el = host.value;
    if (!el) {
        return;
    }
    unbindClick = bindSpaNavigation(el);
    unbindSubmit = bindSpaForms(el);
}

onMounted(() => {
    nextTick(rebind);
});

watch(
    () => props.html,
    () => nextTick(rebind)
);

onBeforeUnmount(() => {
    unbindClick();
    unbindSubmit();
});

const flashStatus = () => page.props.flash?.status;
const errorEntries = () => {
    const err = page.props.errors;
    if (!err || typeof err !== 'object') {
        return [];
    }
    return Object.values(err).flatMap((v) => (Array.isArray(v) ? v : [v])).filter(Boolean);
};
</script>

<template>
    <Head :title="title" />

    <AdminLayout
        :heading="heading"
        :subheading="subheading"
        :route-name="page.props.adminChrome?.routeName"
        :nav="page.props.adminChrome?.nav ?? []"
    >
        <div v-if="flashStatus()" class="admin-fade-up is-visible mb-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
            {{ flashStatus() }}
        </div>

        <div v-if="errorEntries().length" class="admin-fade-up is-visible mb-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
            <ul class="list-inside list-disc space-y-1">
                <li v-for="(msg, i) in errorEntries()" :key="i">{{ msg }}</li>
            </ul>
        </div>

        <div ref="host" class="admin-content" v-html="html" />
    </AdminLayout>
</template>
