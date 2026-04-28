<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const props = defineProps({
    heading: { type: String, required: true },
    subheading: { type: String, default: null },
    routeName: { type: String, default: null },
    nav: { type: Array, default: () => [] },
});

const chrome = computed(() => page.props.adminChrome);

const locale = computed(() => chrome.value?.locale ?? 'ar');
const isEn = computed(() => locale.value === 'en');
const mainPad = computed(() => (isEn.value ? 'lg:pl-64' : 'lg:pr-64'));
const sidebarDir = computed(() => (isEn.value ? 'ltr' : 'rtl'));
const sidebarPos = computed(() =>
    isEn.value ? 'left-0 border-r -translate-x-full lg:translate-x-0' : 'right-0 border-l translate-x-full lg:translate-x-0'
);

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

function navItemActive(item) {
    const name = props.routeName;
    const patterns = Array.isArray(item.match) ? item.match : [item.match];

    return patterns.some((p) => routeNameMatches(name, p));
}

function logout() {
    if (!chrome.value?.logoutAction) {
        return;
    }
    router.post(chrome.value.logoutAction);
}
</script>

<template>
    <div class="admin-ambient" aria-hidden="true">
        <div class="admin-ambient__orb admin-ambient__orb--a" />
        <div class="admin-ambient__orb admin-ambient__orb--b" />
        <div class="admin-ambient__orb admin-ambient__orb--c" />
        <div class="admin-ambient__grid" />
    </div>

    <div class="flex min-h-screen">
        <aside
            data-admin-sidebar
            :data-sidebar-dir="sidebarDir"
            class="admin-sidebar fixed inset-y-0 z-40 w-64 transform border-white/10 transition-transform duration-200"
            :class="sidebarPos"
        >
            <div class="flex h-full flex-col">
                <div class="border-b border-white/10 px-4 py-5">
                    <Link :href="chrome?.routes?.dashboard ?? '/admin'" class="flex items-center gap-3">
                        <span
                            v-if="chrome?.siteLogoUrl"
                            class="admin-brand-icon grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-2xl border border-white/10 bg-white/[0.06] ring-1 ring-white/10 transition-shadow duration-300 hover:shadow-[0_0_24px_rgba(52,211,153,0.12)]"
                        >
                            <img
                                :src="chrome.siteLogoUrl"
                                alt=""
                                class="h-full w-full object-contain p-1"
                                width="40"
                                height="40"
                                decoding="async"
                            />
                        </span>
                        <span
                            v-else
                            class="admin-brand-icon grid h-10 w-10 place-items-center rounded-2xl bg-emerald-500/15 ring-1 ring-emerald-400/30 transition-shadow duration-300 hover:shadow-[0_0_24px_rgba(52,211,153,0.18)]"
                        >
                            <svg viewBox="0 0 24 24" class="h-5 w-5 text-emerald-300" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 3L2 9l10 6 10-6-10-6Z" stroke="currentColor" stroke-width="1.7" />
                                <path d="M2 9v8l10 6 10-6V9" stroke="currentColor" stroke-width="1.7" opacity=".85" />
                            </svg>
                        </span>
                        <div>
                            <div class="text-sm font-semibold text-white">{{ chrome?.appName }}</div>
                            <div class="text-[11px] text-white/50">{{ page.props.translations?.admin?.nav?.system_admin }}</div>
                        </div>
                    </Link>
                </div>

                <nav class="flex-1 space-y-1 px-2 py-4">
                    <Link
                        v-for="(item, idx) in nav"
                        :key="idx"
                        :href="item.href"
                        class="admin-nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium"
                        :class="
                            navItemActive(item)
                                ? 'bg-emerald-500/15 text-emerald-100 ring-1 ring-emerald-400/25'
                                : 'text-white/90 hover:bg-white/5 hover:text-white'
                        "
                    >
                        <svg
                            v-if="item.icon === 'grid'"
                            class="h-5 w-5 shrink-0 opacity-90"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                            />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'book'"
                            class="h-5 w-5 shrink-0 opacity-90"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                            />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'book-open'"
                            class="h-5 w-5 shrink-0 opacity-90"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v15.674A8.967 8.967 0 006 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v15.674A8.967 8.967 0 0118 18c-2.305 0-4.408.867-6 2.292m0-14.25v15.674"
                            />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'device'"
                            class="h-5 w-5 shrink-0 opacity-90"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"
                            />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'academic'"
                            class="h-5 w-5 shrink-0 opacity-90"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.716 50.716 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm6.5 0a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm7.5 0a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
                            />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'teacher'"
                            class="h-5 w-5 shrink-0 opacity-90"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0Zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0Z"
                            />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'shield'"
                            class="h-5 w-5 shrink-0 opacity-90"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
                            />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'carousel'"
                            class="h-5 w-5 shrink-0 opacity-90"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'faq'"
                            class="h-5 w-5 shrink-0 opacity-90"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M12 21a9 9 0 100-18 9 9 0 000 18z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M9.75 9.75h.008v.008H9.75V9.75zm.375 0a3.375 3.375 0 116.75 0c0 1.657-1.007 2.55-1.688 3.135-.34.292-.562.478-.562.99V15m0 3h.01"
                            />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'users'"
                            class="h-5 w-5 shrink-0 opacity-90"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'card'"
                            class="h-5 w-5 shrink-0 opacity-90"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                            />
                        </svg>
                        <svg v-else class="h-5 w-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="border-t border-white/10 p-4">
                    <a :href="chrome?.routes?.home ?? '/'" class="text-xs text-white/50 transition hover:text-emerald-200">
                        {{ page.props.translations?.admin?.nav?.back_to_site }}
                    </a>
                </div>
            </div>
        </aside>

        <div class="flex min-h-screen flex-1 flex-col" :class="mainPad">
            <header
                data-admin-header
                class="admin-header sticky top-0 z-20 border-b border-white/10 bg-[#0c110f]/85 backdrop-blur-md"
            >
                <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            data-admin-nav-toggle
                            class="admin-btn inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 p-2 text-[#e7eee9] lg:hidden"
                            :aria-label="page.props.translations?.admin?.layout?.menu"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <img
                            v-if="chrome?.headerLogoUrl"
                            :src="chrome.headerLogoUrl"
                            :alt="chrome.headerTitle"
                            class="h-8 w-8 shrink-0 rounded-xl border border-white/10 bg-white/[0.06] object-contain p-0.5 sm:h-9 sm:w-9"
                            width="36"
                            height="36"
                            decoding="async"
                        />
                        <div class="min-w-0">
                            <h1 class="text-sm font-semibold text-white sm:text-base">{{ heading }}</h1>
                            <p v-if="subheading" class="mt-0.5 hidden text-xs text-white/55 sm:block">{{ subheading }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2 text-xs sm:gap-3 sm:text-sm">
                        <div
                            class="flex items-center gap-1 rounded-xl border border-white/10 bg-white/5 px-1 py-0.5"
                            role="group"
                            :aria-label="page.props.translations?.admin?.layout?.language"
                        >
                            <a
                                :href="chrome?.localeUrls?.ar ?? '#'"
                                class="rounded-lg px-2 py-1 font-medium"
                                :class="locale === 'ar' ? 'bg-emerald-500/25 text-emerald-100' : 'text-white/70 hover:bg-white/10'"
                            >
                                {{ page.props.translations?.admin?.layout?.lang_ar }}
                            </a>
                            <a
                                :href="chrome?.localeUrls?.en ?? '#'"
                                class="rounded-lg px-2 py-1 font-medium"
                                :class="locale === 'en' ? 'bg-emerald-500/25 text-emerald-100' : 'text-white/70 hover:bg-white/10'"
                            >
                                {{ page.props.translations?.admin?.layout?.lang_en }}
                            </a>
                            <a
                                :href="chrome?.localeUrls?.auto ?? '#'"
                                class="rounded-lg px-2 py-1 text-white/55 hover:bg-white/10"
                                :title="page.props.translations?.admin?.layout?.lang_auto"
                            >
                                A
                            </a>
                        </div>
                        <span class="hidden text-white/70 sm:inline">{{ chrome?.userName }}</span>
                        <button
                            type="button"
                            class="admin-btn rounded-xl border border-white/10 bg-white/5 px-3 py-1.5 font-medium text-white/90 hover:bg-white/10"
                            @click="logout"
                        >
                            {{ page.props.translations?.admin?.layout?.logout }}
                        </button>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 sm:px-6">
                <slot />
            </main>
        </div>
    </div>

    <div data-admin-overlay class="admin-overlay fixed inset-0 z-30 hidden bg-black/60 backdrop-blur-[2px] lg:hidden" />
</template>
