<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const site = computed(() => page.props.site);
const t = computed(() => page.props.translations.site);
const chrome = computed(() => page.props.siteChrome);

const mobileOpen = ref(false);

const siteName = computed(() => {
    const s = site.value;
    const resolved = String(s?.site_name_resolved ?? '').trim();
    if (resolved !== '') {
        return resolved;
    }
    return s?.site_name ?? 'LMS';
});

const segOn =
    'bg-gradient-to-b from-amber-500 to-orange-600 text-white shadow-md shadow-orange-900/25 dark:from-amber-500 dark:to-orange-600';
const segOff =
    'text-slate-600 hover:bg-slate-100/90 dark:text-slate-300 dark:hover:bg-slate-800/80';

const localeArActive = computed(() => chrome.value?.locale_active === 'ar');
const localeEnActive = computed(() => chrome.value?.locale_active === 'en');
const routes = computed(() => chrome.value?.routes ?? {});
const faqNavIsHash = computed(() => (chrome.value?.nav_faq_href ?? '') === '#faq');
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <header
            class="sticky top-0 z-40"
            @keydown.escape.window="mobileOpen = false"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    data-nav
                    class="top-nav relative mt-3 flex flex-wrap items-center justify-between gap-3 py-3 sm:py-4"
                >
                    <Link
                        :href="routes.home"
                        class="flex min-w-0 items-center gap-3 rounded-2xl outline-none ring-amber-500/0 transition hover:ring-2 focus-visible:ring-amber-500/35"
                    >
                        <span
                            class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-2xl border border-slate-200/80 bg-white/95 shadow-md shadow-slate-900/5 ring-1 ring-white/80 backdrop-blur-md dark:border-slate-600 dark:bg-slate-800/95 dark:shadow-black/20 dark:ring-slate-700/60"
                        >
                            <img
                                v-if="site?.site_logo_url"
                                :src="site.site_logo_url"
                                :alt="siteName"
                                class="h-full w-full object-contain p-1"
                                width="44"
                                height="44"
                                decoding="async"
                            />
                            <svg
                                v-else
                                viewBox="0 0 24 24"
                                class="h-5 w-5 text-emerald-600 dark:text-emerald-400"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                                aria-hidden="true"
                            >
                                <path
                                    d="M12 3L2 9l10 6 10-6-10-6Z"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                />
                                <path
                                    d="M2 9v8l10 6 10-6V9"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    opacity=".85"
                                />
                            </svg>
                        </span>
                        <div class="leading-tight">
                            <div class="text-sm font-bold tracking-tight text-slate-900 dark:text-slate-50">
                                {{ siteName }}
                            </div>
                            <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                {{ t.tagline }}
                            </div>
                        </div>
                    </Link>

                    <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 sm:gap-3">
                        <nav
                            class="hidden items-center gap-1 md:flex lg:gap-2"
                            :aria-label="t.nav?.main"
                        >
                            <a :href="chrome.nav_courses_href" class="site-nav-link rounded-lg px-3 py-2">{{
                                t.nav?.courses
                            }}</a>
                            <a :href="chrome.nav_universities_href" class="site-nav-link rounded-lg px-3 py-2">{{
                                t.nav?.universities
                            }}</a>
                            <a :href="chrome.nav_why_href" class="site-nav-link rounded-lg px-3 py-2">{{
                                t.nav?.why
                            }}</a>
                            <a
                                v-if="faqNavIsHash"
                                :href="chrome.nav_faq_href"
                                class="site-nav-link rounded-lg px-3 py-2"
                                >{{ t.nav?.faq }}</a
                            >
                            <Link
                                v-else
                                :href="routes.faq"
                                class="site-nav-link rounded-lg px-3 py-2"
                                >{{ t.nav?.faq }}</Link
                            >
                            <Link
                                :href="routes.register"
                                class="site-nav-link rounded-lg px-3 py-2"
                                >{{ t.nav?.subscribe }}</Link
                            >
                            <Link
                                :href="routes.android"
                                class="rounded-lg px-3 py-2 text-sm font-semibold text-sky-700 transition duration-300 hover:text-sky-900 dark:text-sky-400 dark:hover:text-sky-300"
                                >{{ t.nav?.download_app }}</Link
                            >
                        </nav>

                        <div
                            class="hidden items-center rounded-xl border border-slate-200/80 bg-white/80 p-0.5 shadow-sm ring-1 ring-slate-200/60 dark:border-slate-600 dark:bg-slate-800/80 dark:ring-slate-600/60 md:flex"
                            role="group"
                            :aria-label="t.locale?.switch"
                        >
                            <a
                                :href="chrome.locale_ar"
                                :class="['rounded-lg px-2 py-1.5 text-[11px] font-bold transition', localeArActive ? segOn : segOff]"
                                >{{ t.locale?.ar }}</a
                            >
                            <a
                                :href="chrome.locale_en"
                                :class="['rounded-lg px-2 py-1.5 text-[11px] font-bold transition', localeEnActive ? segOn : segOff]"
                                >{{ t.locale?.en }}</a
                            >
                        </div>

                        <div
                            class="flex items-center rounded-xl border border-slate-200/80 bg-white/80 p-0.5 shadow-sm ring-1 ring-slate-200/60 dark:border-slate-600 dark:bg-slate-800/80 dark:ring-slate-600/60 md:hidden"
                            role="group"
                            :aria-label="t.locale?.switch"
                        >
                            <a
                                :href="chrome.locale_ar"
                                :class="[
                                    'rounded-lg px-1.5 py-1 text-[10px] font-bold leading-tight transition sm:px-2 sm:text-[11px]',
                                    localeArActive ? segOn : segOff,
                                ]"
                                >{{ t.locale?.ar }}</a
                            >
                            <a
                                :href="chrome.locale_en"
                                :class="[
                                    'rounded-lg px-1.5 py-1 text-[10px] font-bold leading-tight transition sm:px-2 sm:text-[11px]',
                                    localeEnActive ? segOn : segOff,
                                ]"
                                >{{ t.locale?.en }}</a
                            >
                        </div>

                        <Link
                            :href="routes.android"
                            class="site-btn-secondary hidden px-3 py-2 text-xs sm:inline-flex"
                            >{{ t.nav?.download_apk }}</Link
                        >

                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200/90 bg-white/90 text-slate-700 shadow-sm ring-1 ring-slate-200/70 transition duration-300 hover:bg-slate-50 hover:shadow-md md:hidden dark:border-slate-600 dark:bg-slate-800/90 dark:text-slate-100 dark:ring-slate-600 dark:hover:bg-slate-800"
                            :aria-expanded="mobileOpen"
                            aria-controls="site-mobile-nav"
                            :aria-label="t.nav?.open_menu"
                            @click="mobileOpen = !mobileOpen"
                        >
                            <svg
                                v-show="!mobileOpen"
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                            </svg>
                            <svg
                                v-show="mobileOpen"
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>

                        <button
                            type="button"
                            data-site-theme-toggle
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200/90 bg-white/90 text-slate-700 shadow-sm ring-1 ring-slate-200/70 transition duration-300 hover:bg-slate-50 hover:shadow-md dark:border-slate-600 dark:bg-slate-800/90 dark:text-amber-200 dark:ring-slate-600 dark:hover:bg-slate-800"
                            :aria-label="t.theme?.toggle"
                            aria-pressed="false"
                        >
                            <span class="dark:hidden" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                                    />
                                </svg>
                            </span>
                            <span class="hidden dark:inline" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                                    />
                                </svg>
                            </span>
                        </button>
                    </div>

                    <div
                        v-show="mobileOpen"
                        id="site-mobile-nav"
                        class="absolute inset-x-0 top-full z-50 pt-2 md:hidden"
                        role="navigation"
                        :aria-label="t.nav?.mobile"
                    >
                        <div
                            class="space-y-1 rounded-2xl border border-slate-200/80 bg-white/95 p-3 shadow-2xl shadow-slate-900/15 ring-1 ring-white/80 backdrop-blur-xl dark:border-slate-700/70 dark:bg-slate-900/95 dark:shadow-black/40 dark:ring-slate-700/50"
                        >
                            <a
                                :href="chrome.nav_courses_href"
                                class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-slate-100 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300"
                                @click="mobileOpen = false"
                                >{{ t.nav?.courses }}</a
                            >
                            <a
                                :href="chrome.nav_universities_href"
                                class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-slate-100 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300"
                                @click="mobileOpen = false"
                                >{{ t.nav?.universities }}</a
                            >
                            <a
                                :href="chrome.nav_why_href"
                                class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-slate-100 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300"
                                @click="mobileOpen = false"
                                >{{ t.nav?.why }}</a
                            >
                            <a
                                v-if="faqNavIsHash"
                                :href="chrome.nav_faq_href"
                                class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-slate-100 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300"
                                @click="mobileOpen = false"
                                >{{ t.nav?.faq }}</a
                            >
                            <Link
                                v-else
                                :href="routes.faq"
                                class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-slate-100 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300"
                                @click="mobileOpen = false"
                                >{{ t.nav?.faq }}</Link
                            >
                            <Link
                                :href="routes.register"
                                class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-slate-100 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300"
                                @click="mobileOpen = false"
                                >{{ t.nav?.subscribe }}</Link
                            >
                            <Link
                                :href="routes.android"
                                class="block rounded-xl px-4 py-3 text-sm font-semibold text-sky-700 dark:text-sky-400"
                                @click="mobileOpen = false"
                                >{{ t.nav?.download_app }}</Link
                            >

                            <div
                                class="border-t border-slate-200/80 pt-2 dark:border-slate-700/60"
                                role="group"
                                :aria-label="t.locale?.switch"
                            >
                                <p class="px-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                    {{ t.locale?.switch }}
                                </p>
                                <div class="flex gap-1 px-2">
                                    <a
                                        :href="chrome.locale_ar"
                                        class="flex-1 rounded-lg py-2 text-center text-xs font-bold transition"
                                        :class="localeArActive ? segOn : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                                        @click="mobileOpen = false"
                                        >{{ t.locale?.ar }}</a
                                    >
                                    <a
                                        :href="chrome.locale_en"
                                        class="flex-1 rounded-lg py-2 text-center text-xs font-bold transition"
                                        :class="localeEnActive ? segOn : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                                        @click="mobileOpen = false"
                                        >{{ t.locale?.en }}</a
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer
            class="site-footer relative mt-auto border-t border-slate-200/80 bg-gradient-to-b from-white/90 via-slate-50/95 to-slate-100/90 text-slate-700 backdrop-blur-md dark:border-slate-800/80 dark:from-slate-950/98 dark:via-slate-950/95 dark:to-slate-950 dark:text-slate-300"
        >
            <div
                class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-l from-transparent via-sky-400/25 to-transparent"
                aria-hidden="true"
            />

            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
                <div class="grid gap-12 sm:grid-cols-2 lg:grid-cols-12 lg:gap-10">
                    <div class="sm:col-span-2 lg:col-span-4">
                        <Link
                            :href="routes.home"
                            class="inline-flex items-center gap-3 rounded-2xl outline-none ring-amber-500/0 transition hover:ring-2 focus-visible:ring-amber-500/35"
                        >
                            <span
                                class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-md shadow-slate-900/5 dark:border-slate-600 dark:bg-slate-800/95 dark:shadow-none"
                            >
                                <img
                                    v-if="site?.site_logo_url"
                                    :src="site.site_logo_url"
                                    :alt="siteName"
                                    class="h-full w-full object-contain p-1.5"
                                    width="44"
                                    height="44"
                                    decoding="async"
                                />
                                <svg
                                    v-else
                                    viewBox="0 0 24 24"
                                    class="h-5 w-5 text-emerald-600 dark:text-emerald-400"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M12 3L2 9l10 6 10-6-10-6Z"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                    />
                                    <path
                                        d="M2 9v8l10 6 10-6V9"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        opacity=".85"
                                    />
                                </svg>
                            </span>
                            <span class="text-start">
                                <span class="block text-base font-bold tracking-tight text-slate-900 dark:text-slate-50">{{
                                    siteName
                                }}</span>
                                <span class="mt-0.5 block text-xs font-medium text-slate-500 dark:text-slate-400">{{
                                    t.footer?.trusted
                                }}</span>
                            </span>
                        </Link>
                        <p class="mt-5 max-w-sm text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                            {{ t.footer?.about }}
                        </p>
                    </div>

                    <div class="lg:col-span-3">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">
                            {{ t.footer?.browse }}
                        </h3>
                        <ul class="mt-5 space-y-3 text-sm">
                            <li>
                                <a
                                    :href="chrome.nav_courses_href"
                                    class="font-medium text-slate-700 transition duration-300 hover:text-emerald-700 dark:text-slate-200 dark:hover:text-emerald-400"
                                    >{{ t.nav?.courses }}</a
                                >
                            </li>
                            <li>
                                <a
                                    :href="chrome.nav_why_href"
                                    class="font-medium text-slate-700 transition duration-300 hover:text-emerald-700 dark:text-slate-200 dark:hover:text-emerald-400"
                                    >{{ t.nav?.why }}</a
                                >
                            </li>
                            <li>
                                <Link
                                    :href="routes.legal_privacy"
                                    class="font-medium text-slate-700 transition duration-300 hover:text-emerald-700 dark:text-slate-200 dark:hover:text-emerald-400"
                                    >{{ t.footer?.privacy_terms }}</Link
                                >
                            </li>
                            <li>
                                <a
                                    v-if="faqNavIsHash"
                                    :href="chrome.nav_faq_href"
                                    class="font-medium text-slate-700 transition duration-300 hover:text-emerald-700 dark:text-slate-200 dark:hover:text-emerald-400"
                                    >{{ t.footer?.faq }}</a
                                >
                                <Link
                                    v-else
                                    :href="routes.faq"
                                    class="font-medium text-slate-700 transition duration-300 hover:text-emerald-700 dark:text-slate-200 dark:hover:text-emerald-400"
                                    >{{ t.footer?.faq }}</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="routes.android"
                                    class="font-medium text-slate-700 transition duration-300 hover:text-emerald-700 dark:text-slate-200 dark:hover:text-emerald-400"
                                    >{{ t.nav?.download_app }}</Link
                                >
                            </li>
                        </ul>
                    </div>

                    <div class="sm:col-span-2 lg:col-span-5">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">
                            {{ t.footer?.contact }}
                        </h3>
                        <ul class="mt-5 space-y-3.5 text-sm">
                            <li v-if="site?.contact_phone" class="flex items-start gap-2.5">
                                <span class="mt-0.5 text-slate-400 dark:text-slate-500" aria-hidden="true">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.6"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                        />
                                    </svg>
                                </span>
                                <a
                                    :href="'tel:' + String(site.contact_phone).replace(/\s+/g, '')"
                                    class="text-slate-800 transition hover:text-emerald-700 dark:text-slate-200 dark:hover:text-emerald-400"
                                    dir="ltr"
                                    >{{ site.contact_phone }}</a
                                >
                            </li>
                            <li v-if="site?.contact_email" class="flex items-start gap-2.5">
                                <span class="mt-0.5 text-slate-400 dark:text-slate-500" aria-hidden="true">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.6"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                        />
                                    </svg>
                                </span>
                                <a
                                    :href="'mailto:' + site.contact_email"
                                    class="break-all text-slate-800 transition hover:text-emerald-700 dark:text-slate-200 dark:hover:text-emerald-400"
                                    dir="ltr"
                                    >{{ site.contact_email }}</a
                                >
                            </li>
                        </ul>

                        <div
                            v-if="site?.facebook_url || site?.telegram_url || site?.whatsapp_url"
                            class="mt-8 flex flex-wrap items-center gap-3"
                        >
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{
                                t.footer?.social
                            }}</span>
                            <div class="flex items-center gap-2">
                                <a
                                    v-if="site.facebook_url"
                                    :href="site.facebook_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200/80 bg-white text-[#1877F2] shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md dark:border-slate-600 dark:bg-slate-800/90"
                                    :title="t.footer?.facebook"
                                    :aria-label="t.footer?.facebook"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path
                                            d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
                                        />
                                    </svg>
                                </a>
                                <a
                                    v-if="site.telegram_url"
                                    :href="site.telegram_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200/80 bg-white text-[#229ED9] shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md dark:border-slate-600 dark:bg-slate-800/90"
                                    :title="t.footer?.telegram"
                                    :aria-label="t.footer?.telegram"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path
                                            d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"
                                        />
                                    </svg>
                                </a>
                                <a
                                    v-if="site.whatsapp_url"
                                    :href="site.whatsapp_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="grid h-10 w-10 place-items-center rounded-xl bg-[#25D366] text-white shadow-md shadow-emerald-900/20 ring-1 ring-white/25 transition duration-300 hover:-translate-y-0.5 hover:shadow-lg"
                                    :title="t.footer?.whatsapp"
                                    :aria-label="t.footer?.whatsapp"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path
                                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.881 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"
                                        />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-14 border-t border-slate-200/80 pt-8 text-center dark:border-slate-800/80">
                    <p class="text-xs text-slate-500 dark:text-slate-500">
                        © {{ new Date().getFullYear() }}
                        <span class="font-semibold text-slate-700 dark:text-slate-300">{{ siteName }}</span>
                        — {{ t.footer?.rights }}
                    </p>
                </div>
            </div>
        </footer>

        <a
            v-if="chrome.whatsapp_show"
            :href="chrome.whatsapp_href"
            target="_blank"
            rel="noopener noreferrer"
            class="site-fab-wa pointer-events-auto fixed z-[9999] grid h-14 w-14 min-h-[3.5rem] min-w-[3.5rem] place-items-center rounded-full bg-[#25D366] text-white ring-2 ring-white/90 transition duration-300 hover:scale-110 focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-300/80 dark:ring-slate-900/90"
            style="bottom: max(1.25rem, env(safe-area-inset-bottom, 0px)); right: max(1.25rem, env(safe-area-inset-right, 0px)); left: auto"
            :aria-label="t.whatsapp?.contact"
            :title="t.whatsapp?.title"
        >
            <svg class="h-8 w-8 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path
                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.881 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"
                />
            </svg>
        </a>
    </div>
</template>
