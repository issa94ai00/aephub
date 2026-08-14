<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import SiteFaqSection from '../../Components/SiteFaqSection.vue';
import SiteLayout from '../../Layouts/SiteLayout.vue';

const page = usePage();
const t = computed(() => page.props.translations.site);

const scoreDegree = computed(() => String(page.props.site?.score_degree ?? '0').trim());
const portalPricing = computed(() => scoreDegree.value === '0');
const explicitPricing = computed(() => scoreDegree.value === '1994');

const routes = computed(() => page.props.siteChrome?.routes ?? {});

const props = defineProps({
    carouselSlides: { type: Array, default: () => [] },
    stats: { type: Object, required: true },
    featuredCourse: { type: Object, default: null },
    latestCourses: { type: Array, default: () => [] },
    faqs: { type: Array, default: () => [] },
    universities: { type: Array, default: () => [] },
});

const dotAriaTpl = computed(() => page.props.translations.site?.carousel?.dot_aria_js ?? 'Slide __NUM__');

const tracks = computed(() => [
    { icon: 'school', title: t.value.home?.tracks_1_title, text: t.value.home?.tracks_1_text },
    { icon: 'briefcase', title: t.value.home?.tracks_2_title, text: t.value.home?.tracks_2_text },
    { icon: 'globe', title: t.value.home?.tracks_3_title, text: t.value.home?.tracks_3_text },
    { icon: 'university', title: t.value.home?.tracks_4_title, text: t.value.home?.tracks_4_text },
    { icon: 'clipboard', title: t.value.home?.tracks_5_title, text: t.value.home?.tracks_5_text },
    { icon: 'sparkles', title: t.value.home?.tracks_6_title, text: t.value.home?.tracks_6_text },
]);

function formatPrice(cents, currency) {
    const n = Number(cents ?? 0) / 100;
    return `${n.toFixed(2)} ${currency || 'SYP'}`;
}
</script>

<template>
    <SiteLayout>
        <section class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 sm:pt-10 lg:px-8 pb-10">
            <div
                v-if="carouselSlides.length"
                data-hero-carousel
                class="hero-carousel reveal"
                dir="ltr"
                :data-carousel-dot-template="dotAriaTpl"
            >
                <h1 class="sr-only">{{ t.home?.hero_sr }}</h1>
                <div data-carousel-track class="hero-carousel__track" style="transform: translateX(0%)">
                    <div
                        v-for="(slide, i) in carouselSlides"
                        :key="i"
                        data-carousel-slide
                        class="hero-carousel__slide"
                    >
                        <img
                            class="hero-carousel__img"
                            :src="slide.src"
                            :alt="slide.title"
                            :loading="i > 0 ? 'lazy' : undefined"
                            :fetchpriority="i === 0 ? 'high' : undefined"
                            decoding="async"
                        />
                        <div class="hero-carousel__overlay" aria-hidden="true" />
                        <div class="hero-carousel__caption">
                            <p
                                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-300/30 bg-emerald-500/20 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-emerald-100 backdrop-blur-sm sm:text-sm"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                {{ t.home?.hero_kicker }}
                            </p>
                            <h2 class="mt-3 text-2xl font-extrabold leading-snug tracking-tight drop-shadow-lg sm:text-4xl">
                                {{ slide.title }}
                            </h2>
                            <p
                                v-if="String(slide.subtitle ?? '').trim() !== ''"
                                class="mt-3 max-w-2xl text-sm font-medium leading-relaxed text-white/90 drop-shadow-md sm:text-base"
                            >
                                {{ slide.subtitle }}
                            </p>
                            <div class="mt-5 flex flex-wrap items-center gap-3">
                                <a
                                    href="#courses"
                                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-b from-amber-400 to-orange-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-orange-900/30 ring-2 ring-white/20 transition duration-300 hover:-translate-y-0.5 hover:brightness-110"
                                >
                                    {{ t.home?.hero_cta_courses }}
                                    <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                                <a
                                    :href="routes.android"
                                    class="inline-flex items-center gap-2 rounded-xl border border-white/40 bg-white/10 px-5 py-2.5 text-sm font-bold text-white backdrop-blur-md transition duration-300 hover:bg-white/20"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    {{ t.home?.hero_cta_app }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    data-carousel-prev
                    class="absolute left-3 top-1/2 z-10 -translate-y-1/2 rounded-full border border-slate-200/80 bg-white/95 p-2.5 text-slate-700 shadow-lg shadow-slate-900/10 ring-1 ring-white/80 backdrop-blur-md transition duration-300 hover:scale-105 hover:bg-white dark:border-slate-600 dark:bg-slate-900/90 dark:text-slate-100"
                    :aria-label="t.carousel?.prev"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button
                    type="button"
                    data-carousel-next
                    class="absolute right-3 top-1/2 z-10 -translate-y-1/2 rounded-full border border-slate-200/80 bg-white/95 p-2.5 text-slate-700 shadow-lg shadow-slate-900/10 ring-1 ring-white/80 backdrop-blur-md transition duration-300 hover:scale-105 hover:bg-white dark:border-slate-600 dark:bg-slate-900/90 dark:text-slate-100"
                    :aria-label="t.carousel?.next"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <div class="absolute bottom-3 left-0 right-0 z-10 flex justify-center gap-2" data-carousel-dots />
            </div>
            <div
                v-else
                class="reveal relative overflow-hidden rounded-3xl border border-slate-200/70 bg-gradient-to-br from-emerald-50/90 via-white to-sky-50/90 p-8 shadow-lg shadow-slate-900/5 dark:border-slate-700/60 dark:from-slate-900/80 dark:via-slate-900/60 dark:to-slate-950/80 dark:shadow-black/20 sm:p-12"
            >
                <div class="pointer-events-none absolute -end-16 -top-16 h-56 w-56 rounded-full bg-emerald-400/10 blur-3xl" aria-hidden="true" />
                <div class="pointer-events-none absolute -start-16 -bottom-16 h-56 w-56 rounded-full bg-sky-400/10 blur-3xl" aria-hidden="true" />
                <p class="relative inline-flex items-center gap-2 rounded-full border border-emerald-200/70 bg-emerald-50/90 px-3 py-1.5 text-xs font-bold text-emerald-800 dark:border-emerald-800/45 dark:bg-emerald-950/50 dark:text-emerald-300">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500" />
                    {{ t.home?.hero_kicker }}
                </p>
                <h1 class="relative mt-4 text-2xl font-bold tracking-tight text-slate-950 dark:text-slate-50 sm:text-3xl">
                    {{ t.home?.hero_sr }}
                </h1>
                <p class="relative mt-3 max-w-2xl text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                    {{ t.home?.carousel_empty_hint }}
                </p>
                <a href="#courses" class="site-btn-primary relative mt-6 text-xs">
                    {{ t.home?.hero_cta_courses }}
                </a>
            </div>

            <!-- Education tracks (all qualifications) -->
            <section id="tracks" class="mt-12 scroll-mt-28" aria-labelledby="tracks-heading">
                <div class="reveal flex flex-col items-start gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="max-w-2xl">
                        <p class="site-kicker">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                            {{ t.home?.tracks_kicker }}
                        </p>
                        <h3 id="tracks-heading" class="mt-3 text-2xl font-bold tracking-tight text-slate-950 dark:text-slate-100 sm:text-3xl">
                            {{ t.home?.tracks_title }}
                        </h3>
                        <p class="mt-2 text-slate-700 dark:text-slate-300">{{ t.home?.tracks_sub }}</p>
                    </div>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <a
                        v-for="(track, idx) in tracks"
                        :key="idx"
                        href="#courses"
                        class="reveal track-card group"
                    >
                        <div
                            class="pointer-events-none absolute -end-8 -top-8 h-28 w-28 rounded-full bg-emerald-400/10 blur-2xl transition duration-500 group-hover:bg-emerald-400/20 dark:bg-emerald-500/10 dark:group-hover:bg-emerald-500/20"
                            aria-hidden="true"
                        />
                        <div
                            class="icon-badge border-emerald-200/60 bg-emerald-50 text-emerald-700 group-hover:shadow-md dark:border-emerald-800/40 dark:bg-emerald-950/50 dark:text-emerald-300"
                        >
                            <svg v-if="track.icon === 'school'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.716 50.716 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm6.5 0a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm7.5 0a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" />
                            </svg>
                            <svg v-else-if="track.icon === 'briefcase'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <svg v-else-if="track.icon === 'globe'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                            </svg>
                            <svg v-else-if="track.icon === 'university'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <svg v-else-if="track.icon === 'clipboard'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <h4 class="relative mt-5 text-base font-bold tracking-tight text-slate-950 dark:text-slate-50">
                            {{ track.title }}
                        </h4>
                        <p class="relative mt-2 flex-1 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                            {{ track.text }}
                        </p>
                        <span class="relative mt-4 inline-flex items-center gap-1 text-sm font-semibold text-emerald-700 transition group-hover:gap-2 dark:text-emerald-400">
                            {{ t.home?.browse_tracks }}
                            <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                    </a>
                </div>
            </section>

            <div class="mt-12 grid items-stretch gap-6 lg:grid-cols-2">
                <div class="reveal site-panel !p-5 sm:!p-7">
                    <p class="site-kicker">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500" />
                        {{ t.home?.snapshot }}
                    </p>
                    <p class="mt-4 text-base leading-relaxed text-slate-700 dark:text-slate-300">
                        {{ t.home?.snapshot_text }}
                    </p>
                    <div class="mt-6 grid grid-cols-3 gap-3">
                        <div class="stat-card">
                            <div class="stat-card__value text-gradient-brand">{{ stats.courses }}</div>
                            <div class="stat-card__label">{{ t.home?.stat_courses }}</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card__value text-gradient-brand">{{ stats.videos }}</div>
                            <div class="stat-card__label">{{ t.home?.stat_videos }}</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card__value text-gradient-brand">{{ stats.students }}</div>
                            <div class="stat-card__label">{{ t.home?.stat_students }}</div>
                        </div>
                    </div>
                </div>

                <div class="reveal site-panel !p-6 sm:!p-8">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-slate-600 dark:text-slate-500">
                        <svg class="h-4 w-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                        </svg>
                        {{ t.home?.featured }}
                    </div>
                    <template v-if="featuredCourse">
                        <div
                            v-if="featuredCourse.cover_image_url"
                            class="mb-4 overflow-hidden rounded-2xl border border-slate-200/60 shadow-md dark:border-slate-700/60"
                        >
                            <img
                                :src="featuredCourse.cover_image_url"
                                alt=""
                                class="h-40 w-full object-cover transition duration-500 hover:scale-105"
                                loading="lazy"
                            />
                        </div>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 dark:text-slate-50">
                            {{ featuredCourse.localized_title }}
                        </h2>
                        <p class="mt-3 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                            {{ featuredCourse.localized_description || t.home?.featured_desc_fallback }}
                        </p>
                        <div class="mt-5 flex flex-wrap items-center gap-2 text-xs">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/60 bg-emerald-50/90 px-3 py-1 font-semibold text-emerald-800 dark:border-emerald-800/45 dark:bg-emerald-950/40 dark:text-emerald-300"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ featuredCourse.teacher_name ?? t.home?.not_set }}
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200/70 bg-slate-50/90 px-3 py-1 font-medium text-slate-700 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-200"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                {{ t.home?.videos_n?.replace(':count', String(featuredCourse.videos_count)) }}
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200/70 bg-slate-50/90 px-3 py-1 font-medium text-slate-700 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-200"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                {{ t.home?.students_n?.replace(':count', String(featuredCourse.enrollments_count)) }}
                            </span>
                        </div>
                        <div
                            v-if="explicitPricing"
                            class="mt-6 rounded-2xl border-2 border-emerald-400/60 bg-emerald-50/90 px-4 py-3 dark:border-emerald-600/50 dark:bg-emerald-950/40"
                        >
                            <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                                {{ t.course?.price_explicit }}
                            </div>
                            <div class="mt-1 text-2xl font-extrabold tabular-nums text-emerald-800 dark:text-emerald-300">
                                {{ formatPrice(featuredCourse.price_cents, featuredCourse.currency) }}
                            </div>
                        </div>
                        <div
                            v-else-if="!portalPricing"
                            class="mt-6 text-lg font-bold text-emerald-700 dark:text-emerald-400"
                        >
                            {{ formatPrice(featuredCourse.price_cents, featuredCourse.currency) }}
                        </div>
                        <p
                            v-else
                            class="mt-6 inline-flex items-center gap-2 rounded-full border border-violet-200/70 bg-violet-50/90 px-4 py-2 text-xs font-semibold text-violet-800 dark:border-violet-800/50 dark:bg-violet-950/50 dark:text-violet-200"
                        >
                            {{ t.course?.portal_join_kicker }} â€” {{ t.course?.portal_join_cta }}
                        </p>
                        <Link
                            :href="'/courses/' + featuredCourse.id"
                            class="site-btn-primary mt-4 inline-block px-5 py-2.5 text-xs"
                            >{{ t.home?.view_course }}</Link
                        >
                    </template>
                    <template v-else>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 dark:text-slate-50">
                            {{ t.home?.no_courses }}
                        </h2>
                        <p class="mt-3 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                            {{ t.home?.no_courses_hint }}
                        </p>
                    </template>
                </div>
            </div>
        </section>

        <section id="universities" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-16 scroll-mt-28">
            <div class="reveal flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="site-kicker site-kicker--sky">{{ t.universities?.section_kicker }}</p>
                    <h3 class="mt-3 text-2xl font-bold tracking-tight text-slate-950 dark:text-slate-100 sm:text-3xl">
                        {{ t.universities?.section_title }}
                    </h3>
                    <p class="mt-2 max-w-2xl text-slate-700 dark:text-slate-300">
                        {{ t.universities?.section_sub }}
                    </p>
                </div>
            </div>

            <div v-if="!universities.length" class="reveal site-panel mt-8 py-12 text-center">
                <p class="text-slate-700 dark:text-slate-300">{{ t.universities?.empty_list }}</p>
            </div>
            <div v-else class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="uni in universities"
                    :key="uni.id"
                    :href="'/universities/' + uni.id"
                    class="reveal group relative block overflow-hidden rounded-3xl border border-slate-200/70 bg-gradient-to-br from-white via-white to-sky-50/40 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-white/80 transition duration-500 hover:-translate-y-1 hover:border-sky-200/50 hover:shadow-2xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 dark:border-slate-700/60 dark:from-slate-900/95 dark:via-slate-900/85 dark:to-sky-950/30 dark:ring-slate-700/50 dark:hover:border-sky-800/40"
                >
                    <div
                        class="pointer-events-none absolute -bottom-10 -start-10 h-36 w-36 rounded-full bg-sky-400/10 blur-2xl transition duration-500 group-hover:bg-sky-400/20 dark:bg-sky-500/10 dark:group-hover:bg-sky-500/20"
                        aria-hidden="true"
                    />
                    <div class="relative flex items-start justify-between gap-4">
                        <div
                            class="icon-badge border-sky-200/50 bg-sky-50 text-sky-700 dark:border-sky-800/40 dark:bg-sky-950/50 dark:text-sky-300"
                        >
                            <svg
                                class="h-7 w-7"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.7"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                />
                            </svg>
                        </div>
                        <span
                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200/80 bg-white/90 text-slate-600 shadow-sm transition duration-300 group-hover:border-emerald-200/60 group-hover:bg-emerald-50 group-hover:text-emerald-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400 dark:group-hover:border-emerald-800/50 dark:group-hover:bg-emerald-950/50 dark:group-hover:text-emerald-300"
                            aria-hidden="true"
                        >
                            <svg
                                class="h-4 w-4 transition duration-300 group-hover:translate-x-0.5 rtl:-scale-x-100 rtl:group-hover:-translate-x-0.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                    </div>
                    <h4 class="relative mt-5 text-lg font-bold tracking-tight text-slate-950 dark:text-slate-50">
                        {{ uni.localized_name }}
                    </h4>
                    <p class="relative mt-2 text-sm text-slate-700 dark:text-slate-300">
                        {{ t.universities?.faculty_count?.replace(':count', String(uni.faculties_count)) }}
                    </p>
                    <p
                        class="relative mt-4 inline-flex items-center gap-1 text-sm font-semibold text-emerald-700 dark:text-emerald-400"
                    >
                        {{ t.universities?.view_faculties }}
                        <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3"
                            />
                        </svg>
                    </p>
                </Link>
            </div>
        </section>

        <section id="courses" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-16 scroll-mt-28">
            <div class="reveal flex flex-col items-start gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="site-kicker">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                        {{ t.home?.latest_title }}
                    </p>
                    <p class="mt-2 text-slate-700 dark:text-slate-300">{{ t.home?.latest_sub }}</p>
                </div>
            </div>

            <div v-if="!latestCourses.length" class="reveal site-panel mt-8 py-12 text-center">
                <p class="text-slate-700 dark:text-slate-300">{{ t.home?.no_published }}</p>
            </div>
            <div v-else class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <article v-for="course in latestCourses" :key="course.id" class="reveal course-card group">
                    <div
                        v-if="course.cover_image_url"
                        class="mb-3 -mx-1 -mt-1 overflow-hidden rounded-2xl border border-slate-200/50 dark:border-slate-700/50"
                    >
                        <img
                            :src="course.cover_image_url"
                            alt=""
                            class="h-32 w-full object-cover transition duration-500 group-hover:scale-105"
                            loading="lazy"
                            decoding="async"
                        />
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-xs font-medium text-slate-600 dark:text-slate-500">#{{ course.id }}</div>
                        <div
                            class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/60 bg-emerald-50/90 px-2.5 py-1 text-[11px] font-bold text-emerald-800 dark:border-emerald-800/45 dark:bg-emerald-950/45 dark:text-emerald-300"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                            {{
                                course.status === 'published' ? t.home?.status_available : t.home?.status_draft
                            }}
                        </div>
                    </div>
                    <h4 class="mt-3 text-lg font-bold tracking-tight text-slate-950 dark:text-slate-50">
                        {{ course.localized_title }}
                    </h4>
                    <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                        {{ course.localized_description || t.home?.desc_placeholder }}
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2 text-xs">
                        <span
                            class="inline-flex items-center gap-1 rounded-full border border-slate-200/70 bg-slate-50/90 px-2.5 py-1 font-medium text-slate-700 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-200"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            {{ t.home?.videos_n?.replace(':count', String(course.videos_count)) }}
                        </span>
                        <span
                            class="inline-flex items-center gap-1 rounded-full border border-slate-200/70 bg-slate-50/90 px-2.5 py-1 font-medium text-slate-700 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-200"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ t.home?.students_n?.replace(':count', String(course.enrollments_count)) }}
                        </span>
                        <span
                            class="inline-flex items-center gap-1 rounded-full border border-slate-200/70 bg-slate-50/90 px-2.5 py-1 font-medium text-slate-700 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-200"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ course.teacher_name ?? t.home?.not_set }}
                        </span>
                    </div>
                    <div class="mt-5 flex items-center justify-between gap-3 border-t border-slate-200/60 pt-4 dark:border-slate-700/50">
                        <div v-if="explicitPricing" class="min-w-0 flex-1">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                                {{ t.course?.price_explicit }}
                            </div>
                            <div class="text-lg font-extrabold tabular-nums text-emerald-800 dark:text-emerald-300">
                                {{ formatPrice(course.price_cents, course.currency) }}
                            </div>
                        </div>
                        <div
                            v-else-if="!portalPricing"
                            class="text-base font-bold text-emerald-700 dark:text-emerald-400"
                        >
                            {{ formatPrice(course.price_cents, course.currency) }}
                        </div>
                        <div
                            v-else
                            class="text-xs font-semibold text-violet-700 dark:text-violet-300"
                        >
                            {{ t.course?.portal_join_cta }}
                        </div>
                        <Link
                            :href="'/courses/' + course.id"
                            class="site-btn-primary shrink-0 px-3 py-2 text-xs"
                            >{{ t.home?.view_course_short }}</Link
                        >
                    </div>
                </article>
            </div>
        </section>

        <section id="why" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-16 scroll-mt-28" aria-labelledby="why-heading">
            <div class="reveal mx-auto max-w-2xl text-center">
                <div class="flex justify-center">
                    <p class="site-kicker">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                        {{ t.home?.why_kicker }}
                    </p>
                </div>
                <h3
                    id="why-heading"
                    class="mt-3 text-2xl font-bold tracking-tight text-slate-950 dark:text-slate-100 sm:text-3xl"
                >
                    {{ t.home?.why_title }}
                </h3>
                <p class="mt-3 text-base leading-relaxed text-slate-700 dark:text-slate-300">
                    {{ t.home?.why_lead }}
                </p>
            </div>

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <article
                    v-for="(block, idx) in [
                        { title: t.home?.why_1_title, text: t.home?.why_1_text },
                        { title: t.home?.why_2_title, text: t.home?.why_2_text },
                        { title: t.home?.why_3_title, text: t.home?.why_3_text },
                        { title: t.home?.why_4_title, text: t.home?.why_4_text },
                    ]"
                    :key="idx"
                    class="reveal group rounded-3xl border border-slate-200/70 bg-gradient-to-b from-white/95 to-slate-50/80 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-white/60 transition duration-500 hover:-translate-y-1 hover:border-emerald-200/60 hover:shadow-xl dark:border-slate-700/60 dark:from-slate-900/90 dark:to-slate-950/70 dark:ring-slate-700/40 dark:hover:border-emerald-800/40"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-emerald-200/50 bg-emerald-50 text-emerald-700 shadow-sm transition duration-300 group-hover:scale-105 group-hover:shadow-md dark:border-emerald-800/40 dark:bg-emerald-950/50 dark:text-emerald-400"
                    >
                        <span class="text-lg font-extrabold tabular-nums">{{ idx + 1 }}</span>
                    </div>
                    <h4 class="mt-5 text-base font-bold text-slate-950 dark:text-slate-50">{{ block.title }}</h4>
                    <p class="mt-2 text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ block.text }}</p>
                </article>
            </div>

            <div
                class="reveal mt-12 rounded-3xl border border-dashed border-emerald-200/60 bg-gradient-to-br from-emerald-50/50 via-white/60 to-sky-50/40 px-6 py-6 text-center shadow-inner sm:px-10 dark:border-emerald-800/35 dark:from-emerald-950/25 dark:via-slate-900/40 dark:to-sky-950/20"
            >
                <p class="text-sm font-medium leading-relaxed text-slate-800 dark:text-slate-300" v-html="t.home?.why_goal_html" />
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
            <div class="app-banner reveal">
                <div
                    class="pointer-events-none absolute -end-20 -top-20 h-64 w-64 rounded-full bg-emerald-400/10 blur-3xl dark:bg-emerald-500/10"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute -bottom-20 -start-20 h-64 w-64 rounded-full bg-sky-400/10 blur-3xl dark:bg-sky-500/10"
                    aria-hidden="true"
                />
                <div class="relative flex flex-col items-start gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="site-kicker site-kicker--violet">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            {{ t.home?.app_banner_kicker }}
                        </p>
                        <h3 class="mt-3 text-2xl font-bold tracking-tight text-slate-950 dark:text-slate-50 sm:text-3xl">
                            {{ t.home?.app_banner_title }}
                        </h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate-700 dark:text-slate-300 sm:text-base">
                            {{ t.home?.app_banner_text }}
                        </p>
                    </div>
                    <Link
                        :href="routes.android"
                        class="site-btn-primary shrink-0 px-6 py-3 text-sm"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        {{ t.home?.app_banner_cta }}
                    </Link>
                </div>
            </div>
        </section>

        <SiteFaqSection :faqs="faqs" show-all-link />
    </SiteLayout>
</template>
