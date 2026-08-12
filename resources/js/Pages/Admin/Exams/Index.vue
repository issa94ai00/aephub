<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    exams: { type: Object, required: true },
    courses: { type: Array, default: () => [] },
    search: { type: String, default: '' },
    status: { type: String, default: null },
    course_id: { type: Number, default: null },
    stats: { type: Object, default: () => ({}) },
});

const page = usePage();
const t = computed(() => page.props.translations?.admin ?? {});
const flash = computed(() => page.props.flash?.status);
const rows = computed(() => props.exams?.data ?? []);

const filters = reactive({
    search: props.search,
    status: props.status ?? '',
    course_id: props.course_id ?? '',
});

const statusLabel = (s) => t.value.exams?.[`status_${s}`] ?? s;

const statusClass = (s) =>
    ({
        published: 'bg-emerald-500/15 text-emerald-200 ring-emerald-400/25',
        draft: 'bg-amber-500/15 text-amber-100 ring-amber-400/25',
        archived: 'bg-white/10 text-white/60 ring-white/15',
    })[s] || 'bg-white/10 text-white/70';

function apply() {
    const query = {};
    if (filters.search) query.search = filters.search;
    if (filters.status) query.status = filters.status;
    if (filters.course_id) query.course_id = filters.course_id;
    router.get('/admin/exams', query, { preserveState: true, replace: true });
}

function clearFilters() {
    filters.search = '';
    filters.status = '';
    filters.course_id = '';
    router.get('/admin/exams', {}, { replace: true });
}

function destroy(id) {
    if (!window.confirm(t.value.exams?.confirm_delete)) return;
    router.delete(`/admin/exams/${id}`);
}
</script>

<template>
    <Head :title="t.exams?.title" />
    <AdminLayout :heading="t.exams?.heading ?? ''" :subheading="t.exams?.subheading ?? ''">
        <div class="admin-content space-y-5">
            <div
                v-if="flash"
                class="admin-fade-up is-visible rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100"
            >
                {{ flash }}
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="admin-card exam-stat-card p-4">
                    <div class="text-xs text-white/50">{{ t.exams?.stat_total }}</div>
                    <div class="mt-1 text-2xl font-semibold text-white tabular-nums">{{ stats.total ?? 0 }}</div>
                </div>
                <div class="admin-card exam-stat-card p-4" style="animation-delay: .05s">
                    <div class="text-xs text-white/50">{{ t.exams?.stat_published }}</div>
                    <div class="mt-1 text-2xl font-semibold text-emerald-300 tabular-nums">{{ stats.published ?? 0 }}</div>
                </div>
                <div class="admin-card exam-stat-card p-4" style="animation-delay: .1s">
                    <div class="text-xs text-white/50">{{ t.exams?.stat_draft }}</div>
                    <div class="mt-1 text-2xl font-semibold text-amber-200 tabular-nums">{{ stats.draft ?? 0 }}</div>
                </div>
                <div class="admin-card exam-stat-card p-4" style="animation-delay: .15s">
                    <div class="text-xs text-white/50">{{ t.exams?.stat_attempts }}</div>
                    <div class="mt-1 text-2xl font-semibold text-sky-300 tabular-nums">{{ stats.attempts ?? 0 }}</div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <form class="flex flex-wrap items-end gap-2" @submit.prevent="apply">
                    <div>
                        <label class="mb-1 block text-[11px] text-white/50">{{ t.exams?.search }}</label>
                        <input
                            v-model="filters.search"
                            type="search"
                            class="w-52 rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white"
                            :placeholder="t.exams?.search"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] text-white/50">{{ t.exams?.filter_status }}</label>
                        <select v-model="filters.status" class="rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white">
                            <option value="">{{ t.exams?.all }}</option>
                            <option value="draft">{{ t.exams?.status_draft }}</option>
                            <option value="published">{{ t.exams?.status_published }}</option>
                            <option value="archived">{{ t.exams?.status_archived }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] text-white/50">{{ t.exams?.filter_course }}</label>
                        <select v-model="filters.course_id" class="max-w-[14rem] rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white">
                            <option value="">{{ t.exams?.all }}</option>
                            <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.localized_title }}</option>
                        </select>
                    </div>
                    <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">
                        {{ t.exams?.apply }}
                    </button>
                    <button type="button" class="rounded-xl border border-white/15 px-4 py-2 text-sm text-white/70 hover:bg-white/5" @click="clearFilters">
                        {{ t.exams?.clear }}
                    </button>
                </form>

                <Link
                    href="/admin/exams/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500"
                >
                    <span aria-hidden="true">+</span>
                    {{ t.exams?.create }}
                </Link>
            </div>

            <div class="admin-card overflow-hidden">
                <div v-if="!rows.length" class="px-6 py-16 text-center">
                    <div class="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-3xl bg-emerald-500/10 ring-1 ring-emerald-400/20">
                        <svg class="h-8 w-8 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <p class="text-sm text-white/60">{{ t.exams?.empty }}</p>
                    <Link href="/admin/exams/create" class="mt-4 inline-flex text-sm font-medium text-emerald-300 hover:underline">
                        {{ t.exams?.create }}
                    </Link>
                </div>

                <table v-else class="min-w-full text-sm">
                    <thead class="border-b border-white/10 text-left text-xs uppercase tracking-wide text-white/40">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t.exams?.col_title }}</th>
                            <th class="px-4 py-3 font-medium">{{ t.exams?.col_course }}</th>
                            <th class="px-4 py-3 font-medium">{{ t.exams?.col_status }}</th>
                            <th class="px-4 py-3 font-medium">{{ t.exams?.col_questions }}</th>
                            <th class="px-4 py-3 font-medium">{{ t.exams?.col_attempts }}</th>
                            <th class="px-4 py-3 font-medium">{{ t.exams?.col_actions }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="exam in rows" :key="exam.id" class="border-b border-white/5 transition hover:bg-white/[0.03]">
                            <td class="px-4 py-3">
                                <div class="font-medium text-white">{{ exam.localized_title || exam.title }}</div>
                                <div v-if="exam.duration_minutes" class="mt-0.5 text-xs text-white/40">⏱ {{ exam.duration_minutes }}′</div>
                            </td>
                            <td class="px-4 py-3 text-white/70">{{ exam.course?.localized_title || exam.course?.title || '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-lg px-2 py-0.5 text-xs ring-1" :class="statusClass(exam.status)">
                                    {{ statusLabel(exam.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 tabular-nums text-white/80">{{ exam.questions_count }}</td>
                            <td class="px-4 py-3 tabular-nums text-white/80">{{ exam.attempts_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <Link :href="`/admin/exams/${exam.id}/edit`" class="text-xs text-emerald-300 hover:underline">{{ t.exams?.edit }}</Link>
                                    <Link :href="`/admin/exams/reports?exam_id=${exam.id}`" class="text-xs text-sky-300 hover:underline">{{ t.exams?.reports }}</Link>
                                    <button type="button" class="text-xs text-rose-300 hover:underline" @click="destroy(exam.id)">{{ t.exams?.delete }}</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="exams.links?.length > 3" class="flex flex-wrap justify-center gap-1 border-t border-white/10 px-4 py-3">
                    <template v-for="(l, i) in exams.links" :key="i">
                        <span
                            v-if="!l.url || l.active"
                            class="rounded-lg px-2.5 py-1 text-xs"
                            :class="l.active ? 'bg-emerald-500/20 font-semibold text-emerald-100' : 'text-white/30'"
                            v-html="l.label"
                        />
                        <Link v-else :href="l.url" class="rounded-lg px-2.5 py-1 text-xs text-white/70 hover:bg-white/10" v-html="l.label" />
                    </template>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.exam-stat-card {
    animation: examRise 0.45s ease both;
}
@keyframes examRise {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: none;
    }
}
</style>
