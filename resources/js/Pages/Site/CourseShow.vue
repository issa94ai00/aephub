<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SiteLayout from '../../Layouts/SiteLayout.vue';

const page = usePage();
const t = computed(() => page.props.translations.site);

const props = defineProps({
    course: { type: Object, required: true },
    relatedCourses: { type: Array, default: () => [] },
});

const scoreDegree = computed(() => String(page.props.site?.score_degree ?? '0').trim());
const portalPricing = computed(() => scoreDegree.value === '0');
const explicitPricing = computed(() => scoreDegree.value === '1994');

const joinBusy = ref(false);
const joinFlash = ref('');

function formatPrice(cents, currency) {
    const n = Number(cents ?? 0) / 100;
    return `${n.toFixed(2)} ${currency || 'SYP'}`;
}

function resolveApiToken() {
    const keys = ['access_token', 'token', 'jwt', 'lms_token'];
    for (const k of keys) {
        try {
            const v = localStorage.getItem(k);
            if (v && String(v).trim() !== '') {
                return String(v).trim();
            }
        } catch {
            /* ignore */
        }
    }
    return '';
}

async function portalExpressJoin() {
    joinFlash.value = '';
    const base = String(page.props.siteChrome?.api_base || '/api/v1').replace(/\/$/, '');
    const token = resolveApiToken();
    if (!token) {
        joinFlash.value = t.value.course?.portal_join_needs_auth ?? '';
        const reg = page.props.siteChrome?.routes?.register;
        if (reg) {
            window.location.href = reg;
        }
        return;
    }

    joinBusy.value = true;
    try {
        const res = await fetch(`${base}/courses/${props.course.id}/enroll/express`, {
            method: 'POST',
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: '{}',
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok) {
            joinFlash.value = t.value.course?.portal_join_success ?? '';
        } else {
            joinFlash.value =
                (typeof data.message === 'string' && data.message) || t.value.course?.portal_join_error || '';
        }
    } catch {
        joinFlash.value = t.value.course?.portal_join_error ?? '';
    } finally {
        joinBusy.value = false;
    }
}
</script>

<template>
    <SiteLayout>
        <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <nav class="reveal flex flex-wrap items-center gap-2 text-sm font-medium" :aria-label="t.nav?.main">
                <a
                    :href="page.props.siteChrome?.nav_courses_href ?? '/'"
                    class="breadcrumb-link"
                >
                    <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    {{ t.nav?.courses }}
                </a>
                <span class="text-slate-600 dark:text-slate-500" aria-hidden="true">/</span>
                <span class="truncate text-slate-700 dark:text-slate-400">{{ course.localized_title }}</span>
            </nav>

            <div class="mt-8 grid items-start gap-8 lg:grid-cols-3">
                <section class="reveal site-panel lg:col-span-2">
                    <div
                        v-if="course.cover_image_url"
                        class="relative mb-6 overflow-hidden rounded-2xl border border-slate-200/60 shadow-lg dark:border-slate-700/60"
                    >
                        <img
                            :src="course.cover_image_url"
                            :alt="course.localized_title"
                            class="h-52 w-full object-cover transition duration-500 hover:scale-105 sm:h-64"
                            loading="lazy"
                            decoding="async"
                        />
                        <div
                            class="absolute bottom-3 start-3 rounded-full border border-emerald-200/60 bg-emerald-50/95 px-3 py-1 text-xs font-bold text-emerald-800 shadow-md backdrop-blur-sm dark:border-emerald-800/45 dark:bg-emerald-950/80 dark:text-emerald-300"
                        >
                            {{
                                course.status === 'published' ? t.course?.available_now : t.course?.unavailable
                            }}
                        </div>
                    </div>
                    <div v-else>
                        <p class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400/90">
                            #{{ course.id }} â€¢
                            {{
                                course.status === 'published' ? t.course?.available_now : t.course?.unavailable
                            }}
                        </p>
                    </div>

                    <div>
                        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 dark:text-slate-50 sm:text-3xl">
                            {{ course.localized_title }}
                        </h1>
                        <p class="mt-4 text-sm leading-relaxed text-slate-700 dark:text-slate-300 sm:text-base">
                            {{ course.localized_description || t.course?.desc_pending }}
                        </p>
                    </div>

                    <div class="mt-8 grid gap-3 sm:grid-cols-3">
                        <div
                            class="stat-card"
                        >
                            <div class="flex items-center gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200/70 bg-white text-slate-600 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-bold text-slate-950 dark:text-slate-50">
                                        {{ course.teacher_name ?? t.home?.not_set }}
                                    </div>
                                    <div class="mt-0.5 text-xs font-medium text-slate-700 dark:text-slate-400">
                                        {{ t.course?.instructor }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-center gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200/70 bg-white text-slate-600 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-slate-950 dark:text-slate-50">{{ course.videos_count }}</div>
                                    <div class="mt-0.5 text-xs font-medium text-slate-700 dark:text-slate-400">
                                        {{ t.course?.video_count }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="flex items-center gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200/70 bg-white text-slate-600 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-slate-950 dark:text-slate-50">{{ course.enrollments_count }}</div>
                                    <div class="mt-0.5 text-xs font-medium text-slate-700 dark:text-slate-400">
                                        {{ t.course?.student_count }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-8 flex flex-col gap-4 rounded-2xl border border-dashed border-emerald-200/60 bg-emerald-50/40 p-5 sm:flex-row sm:items-center dark:border-emerald-800/40 dark:bg-emerald-950/25"
                    >
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-emerald-200/60 bg-white text-emerald-700 shadow-sm dark:border-emerald-800/50 dark:bg-slate-900 dark:text-emerald-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold leading-relaxed text-slate-800 dark:text-slate-200">
                                    {{ t.course?.app_hint }}
                                </p>
                                <a
                                    :href="page.props.siteChrome?.routes?.android"
                                    class="mt-1 inline-flex items-center gap-1.5 text-sm font-bold text-emerald-700 transition hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300"
                                >
                                    {{ t.course?.go_download }}
                                    <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

                <aside class="reveal lg:sticky lg:top-28">
                    <!-- Portal mode (score_degree = 0): join card, no price -->
                    <div
                        v-if="portalPricing"
                        class="relative overflow-hidden rounded-3xl border border-violet-300/50 bg-gradient-to-br from-violet-600 via-fuchsia-600 to-indigo-700 p-1 shadow-xl shadow-violet-900/25 dark:border-violet-500/30 dark:from-violet-950 dark:via-fuchsia-950 dark:to-indigo-950 dark:shadow-black/40"
                    >
                        <div
                            class="rounded-[1.35rem] bg-white/95 px-6 py-6 dark:bg-slate-950/90 dark:ring-1 dark:ring-white/10"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.2em] text-violet-600 dark:text-violet-300"
                            >
                                {{ t.course?.portal_join_kicker }}
                            </p>
                            <h2 class="mt-2 text-lg font-bold leading-snug text-slate-950 dark:text-slate-50">
                                {{ t.course?.portal_join_title }}
                            </h2>
                            <p class="mt-2 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                                {{ t.course?.portal_join_lead }}
                            </p>
                            <button
                                type="button"
                                class="mt-5 w-full rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-violet-600/30 transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="joinBusy"
                                @click="portalExpressJoin"
                            >
                                <span v-if="joinBusy" class="inline-flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8v0a8 8 0 018 8M4 12a8 8 0 008 8v0a8 8 0 008-8" />
                                    </svg>
                                    {{ t.course?.portal_join_busy }}
                                </span>
                                <span v-else>{{ t.course?.portal_join_cta }}</span>
                            </button>
                            <Link
                                :href="page.props.siteChrome?.routes?.android"
                                class="mt-3 block w-full rounded-xl border border-violet-200/80 bg-violet-50/80 py-2.5 text-center text-xs font-semibold text-violet-800 transition hover:bg-violet-100 dark:border-violet-800/50 dark:bg-violet-950/40 dark:text-violet-200 dark:hover:bg-violet-900/50"
                            >
                                {{ t.course?.portal_join_app }}
                            </Link>
                            <p
                                v-if="joinFlash"
                                class="mt-3 rounded-lg border border-emerald-200/70 bg-emerald-50/90 px-3 py-2 text-xs font-medium text-emerald-900 dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-200"
                                role="status"
                            >
                                {{ joinFlash }}
                            </p>
                        </div>
                    </div>

                    <!-- Explicit pricing (score_degree = 1994) -->
                    <div
                        v-else-if="explicitPricing"
                        class="relative overflow-hidden rounded-3xl border border-emerald-200/70 bg-gradient-to-b from-emerald-50 to-white p-6 shadow-xl shadow-slate-900/5 ring-1 ring-white/70 dark:border-emerald-800/50 dark:from-emerald-950/50 dark:to-slate-900 dark:ring-slate-700/40"
                    >
                        <div
                            class="pointer-events-none absolute -end-10 -top-10 h-32 w-32 rounded-full bg-emerald-400/15 blur-3xl dark:bg-emerald-500/10"
                            aria-hidden="true"
                        />
                        <div class="relative">
                            <p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">
                                {{ t.course?.price_explicit }}
                            </p>
                            <div class="mt-2 text-3xl font-extrabold tabular-nums text-emerald-800 dark:text-emerald-300">
                                {{ formatPrice(course.price_cents, course.currency) }}
                            </div>
                            <Link
                                :href="page.props.siteChrome?.routes?.android"
                                class="site-btn-primary mt-5 w-full text-sm"
                            >
                                {{ t.course?.go_download }}
                            </Link>
                        </div>
                    </div>

                    <!-- Default: compact price -->
                    <div
                        v-else
                        class="relative overflow-hidden rounded-3xl border border-emerald-200/60 bg-gradient-to-b from-emerald-50/95 to-emerald-100/40 px-6 py-5 shadow-inner dark:border-emerald-800/45 dark:from-emerald-950/40 dark:to-emerald-950/20"
                    >
                        <div class="text-xs font-semibold text-slate-700 dark:text-slate-400">{{ t.course?.price }}</div>
                        <div class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-400">
                            {{ formatPrice(course.price_cents, course.currency) }}
                        </div>
                        <Link
                            :href="page.props.siteChrome?.routes?.android"
                            class="site-btn-primary mt-4 w-full text-xs"
                        >
                            {{ t.course?.go_download }}
                        </Link>
                    </div>
                </aside>
            </div>

            <section v-if="relatedCourses.length" class="mt-12">
                <div class="reveal mb-6 flex items-end justify-between gap-4">
                    <div>
                        <p class="site-kicker">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                            {{ t.course?.related }}
                        </p>
                    </div>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <article v-for="item in relatedCourses" :key="item.id" class="reveal course-card group">
                        <div
                            v-if="item.cover_image_url"
                            class="mb-3 -mx-1 -mt-1 overflow-hidden rounded-2xl border border-slate-200/50 dark:border-slate-700/50"
                        >
                            <img
                                :src="item.cover_image_url"
                                alt=""
                                class="h-28 w-full object-cover transition duration-500 group-hover:scale-105"
                                loading="lazy"
                                decoding="async"
                            />
                        </div>
                        <h3 class="text-base font-bold text-slate-950 dark:text-slate-50">{{ item.localized_title }}</h3>
                        <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                            {{ item.localized_description || t.home?.desc_placeholder }}
                        </p>
                        <div
                            v-if="explicitPricing"
                            class="mt-3 rounded-xl border border-emerald-200/70 bg-emerald-50/80 px-3 py-2 dark:border-emerald-800/50 dark:bg-emerald-950/30"
                        >
                            <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                                {{ t.course?.price_explicit }}
                            </div>
                            <div class="text-lg font-extrabold tabular-nums text-emerald-800 dark:text-emerald-300">
                                {{ formatPrice(item.price_cents, item.currency) }}
                            </div>
                        </div>
                        <p
                            v-else-if="portalPricing"
                            class="mt-3 text-xs font-medium leading-relaxed text-violet-700 dark:text-violet-300"
                        >
                            {{ t.course?.related_join_hint }}
                        </p>
                        <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-200/60 pt-4 dark:border-slate-700/50">
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-slate-700 dark:text-slate-400">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                {{ t.home?.videos_n?.replace(':count', String(item.videos_count)) }}
                            </span>
                            <Link :href="'/courses/' + item.id" class="site-btn-primary px-3 py-2 text-xs">{{
                                t.course?.view_details
                            }}</Link>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </SiteLayout>
</template>
