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
        <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-14">
            <section class="reveal site-panel">
                <div
                    v-if="course.cover_image_url"
                    class="mb-6 overflow-hidden rounded-2xl border border-slate-200/60 shadow-lg dark:border-slate-700/60"
                >
                    <img
                        :src="course.cover_image_url"
                        alt=""
                        class="h-48 w-full object-cover transition duration-500 hover:scale-105 sm:h-56"
                        loading="lazy"
                    />
                </div>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="max-w-3xl">
                        <p class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400/90">
                            #{{ course.id }} •
                            {{ course.status === 'published' ? t.course?.available_now : t.course?.unavailable }}
                        </p>
                        <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 sm:text-3xl">
                            {{ course.localized_title }}
                        </h1>
                        <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                            {{ course.localized_description || t.course?.desc_pending }}
                        </p>
                    </div>

                    <!-- Portal mode (score_degree = 0): join card, no price -->
                    <div
                        v-if="portalPricing"
                        class="relative w-full max-w-sm overflow-hidden rounded-2xl border border-violet-300/50 bg-gradient-to-br from-violet-600 via-fuchsia-600 to-indigo-700 p-1 shadow-xl shadow-violet-900/25 dark:border-violet-500/30 dark:from-violet-950 dark:via-fuchsia-950 dark:to-indigo-950 dark:shadow-black/40 sm:w-auto"
                    >
                        <div
                            class="rounded-[0.9rem] bg-white/95 px-5 py-5 text-start dark:bg-slate-950/90 dark:ring-1 dark:ring-white/10"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.2em] text-violet-600 dark:text-violet-300"
                            >
                                {{ t.course?.portal_join_kicker }}
                            </p>
                            <h2 class="mt-2 text-lg font-bold leading-snug text-slate-900 dark:text-slate-50">
                                {{ t.course?.portal_join_title }}
                            </h2>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                {{ t.course?.portal_join_lead }}
                            </p>
                            <button
                                type="button"
                                class="mt-4 w-full rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-violet-600/30 transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="joinBusy"
                                @click="portalExpressJoin"
                            >
                                {{ joinBusy ? t.course?.portal_join_busy : t.course?.portal_join_cta }}
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
                        class="w-full max-w-xs rounded-2xl border-2 border-emerald-400/70 bg-gradient-to-b from-emerald-50 to-white px-5 py-5 shadow-lg dark:border-emerald-500/50 dark:from-emerald-950/50 dark:to-slate-900 sm:w-auto"
                    >
                        <div class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">
                            {{ t.course?.price_explicit }}
                        </div>
                        <div class="mt-2 text-3xl font-extrabold tabular-nums text-emerald-800 dark:text-emerald-300">
                            {{ formatPrice(course.price_cents, course.currency) }}
                        </div>
                    </div>

                    <!-- Default: compact price -->
                    <div
                        v-else
                        class="rounded-2xl border border-emerald-200/60 bg-gradient-to-b from-emerald-50/95 to-emerald-100/40 px-5 py-4 text-center shadow-inner dark:border-emerald-800/45 dark:from-emerald-950/40 dark:to-emerald-950/20"
                    >
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t.course?.price }}</div>
                        <div class="mt-1 text-xl font-bold text-emerald-700 dark:text-emerald-400">
                            {{ formatPrice(course.price_cents, course.currency) }}
                        </div>
                    </div>
                </div>

                <div class="mt-8 grid gap-3 sm:grid-cols-3">
                    <div
                        class="rounded-2xl border border-slate-200/70 bg-slate-50/90 p-4 shadow-inner dark:border-slate-700/60 dark:bg-slate-800/80"
                    >
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t.course?.instructor }}</div>
                        <div class="mt-1 text-sm font-bold text-slate-900 dark:text-slate-50">
                            {{ course.teacher_name ?? t.home?.not_set }}
                        </div>
                    </div>
                    <div
                        class="rounded-2xl border border-slate-200/70 bg-slate-50/90 p-4 shadow-inner dark:border-slate-700/60 dark:bg-slate-800/80"
                    >
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                            {{ t.course?.video_count }}
                        </div>
                        <div class="mt-1 text-sm font-bold text-slate-900 dark:text-slate-50">{{ course.videos_count }}</div>
                    </div>
                    <div
                        class="rounded-2xl border border-slate-200/70 bg-slate-50/90 p-4 shadow-inner dark:border-slate-700/60 dark:bg-slate-800/80"
                    >
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                            {{ t.course?.student_count }}
                        </div>
                        <div class="mt-1 text-sm font-bold text-slate-900 dark:text-slate-50">
                            {{ course.enrollments_count }}
                        </div>
                    </div>
                </div>

                <div
                    class="mt-8 rounded-2xl border border-dashed border-emerald-200/60 bg-emerald-50/40 p-5 dark:border-emerald-800/40 dark:bg-emerald-950/25"
                >
                    <p class="text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ t.course?.app_hint }}</p>
                    <Link :href="page.props.siteChrome?.routes?.android" class="site-btn-primary mt-4 inline-block text-xs">{{
                        t.course?.go_download
                    }}</Link>
                </div>
            </section>

            <section v-if="relatedCourses.length" class="mt-10">
                <h2 class="mb-5 text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
                    {{ t.course?.related }}
                </h2>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <article v-for="item in relatedCourses" :key="item.id" class="reveal course-card">
                        <h3 class="text-base font-bold text-slate-900 dark:text-slate-50">{{ item.localized_title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
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
                        <div class="mt-4 flex items-center justify-between gap-3">
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{
                                t.home?.videos_n?.replace(':count', String(item.videos_count))
                            }}</span>
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
