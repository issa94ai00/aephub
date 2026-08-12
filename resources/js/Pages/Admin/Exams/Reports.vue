<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    exams: { type: Object, required: true },
    courses: { type: Array, default: () => [] },
    course_id: { type: Number, default: null },
    exam_id: { type: Number, default: null },
    selected: { type: Object, default: null },
    summary: { type: Object, default: null },
    attempts: { type: Object, default: null },
});

const page = usePage();
const t = computed(() => page.props.translations?.admin ?? {});
const locale = computed(() => page.props.locale ?? 'ar');
const rows = computed(() => props.exams?.data ?? []);
const attemptRows = computed(() => props.attempts?.data ?? []);

const filters = reactive({
    course_id: props.course_id ?? '',
    exam_id: props.exam_id ?? '',
});

function apply() {
    const query = {};
    if (filters.course_id) query.course_id = filters.course_id;
    if (filters.exam_id) query.exam_id = filters.exam_id;
    router.get('/admin/exams/reports', query, { preserveState: true, replace: true });
}

function pickExam(id) {
    filters.exam_id = id;
    apply();
}

function gradeLabel(a) {
    return locale.value === 'en' && a.grade_label_en ? a.grade_label_en : a.grade_label;
}

function fmtWhen(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;
    const p = (x) => String(x).padStart(2, '0');
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`;
}
</script>

<template>
    <Head :title="t.exams?.reports_title" />
    <AdminLayout :heading="t.exams?.reports_heading ?? ''" :subheading="t.exams?.reports_sub ?? ''">
        <div class="admin-content space-y-5">
            <form class="flex flex-wrap items-end gap-2" @submit.prevent="apply">
                <div>
                    <label class="mb-1 block text-[11px] text-white/50">{{ t.exams?.filter_course }}</label>
                    <select v-model="filters.course_id" class="rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white">
                        <option value="">{{ t.exams?.all }}</option>
                        <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.localized_title }}</option>
                    </select>
                </div>
                <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm text-white">{{ t.exams?.apply }}</button>
                <Link href="/admin/exams" class="rounded-xl border border-white/15 px-4 py-2 text-sm text-white/70 hover:bg-white/5">
                    {{ t.exams?.back_list }}
                </Link>
            </form>

            <div class="grid gap-5 lg:grid-cols-5">
                <div class="admin-card lg:col-span-2 overflow-hidden">
                    <div class="border-b border-white/10 px-4 py-3 text-xs font-medium uppercase tracking-wide text-white/40">
                        {{ t.exams?.pick_exam }}
                    </div>
                    <ul class="max-h-[28rem] overflow-auto">
                        <li v-for="exam in rows" :key="exam.id">
                            <button
                                type="button"
                                class="flex w-full items-start justify-between gap-2 border-b border-white/5 px-4 py-3 text-left transition hover:bg-white/[0.04]"
                                :class="exam_id === exam.id ? 'bg-emerald-500/10' : ''"
                                @click="pickExam(exam.id)"
                            >
                                <div>
                                    <div class="text-sm font-medium text-white">{{ exam.localized_title || exam.title }}</div>
                                    <div class="mt-0.5 text-xs text-white/45">{{ exam.course?.localized_title || exam.course?.title }}</div>
                                </div>
                                <span class="rounded-lg bg-white/10 px-2 py-0.5 text-[11px] tabular-nums text-white/60">
                                    {{ exam.submitted_attempts_count ?? 0 }}
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="space-y-5 lg:col-span-3">
                    <template v-if="selected && summary">
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="admin-card p-4">
                                <div class="text-xs text-white/45">{{ t.exams?.summary_attempts }}</div>
                                <div class="mt-1 text-2xl font-semibold tabular-nums text-white">{{ summary.attempts_count }}</div>
                            </div>
                            <div class="admin-card p-4">
                                <div class="text-xs text-white/45">{{ t.exams?.summary_students }}</div>
                                <div class="mt-1 text-2xl font-semibold tabular-nums text-sky-300">{{ summary.unique_students }}</div>
                            </div>
                            <div class="admin-card p-4">
                                <div class="text-xs text-white/45">{{ t.exams?.summary_pass_rate }}</div>
                                <div class="mt-1 text-2xl font-semibold tabular-nums text-emerald-300">
                                    {{ summary.pass_rate != null ? summary.pass_rate + '%' : '—' }}
                                </div>
                            </div>
                            <div class="admin-card p-4">
                                <div class="text-xs text-white/45">{{ t.exams?.summary_avg }}</div>
                                <div class="mt-1 text-xl font-semibold tabular-nums text-white">{{ summary.average_percent ?? '—' }}</div>
                            </div>
                            <div class="admin-card p-4">
                                <div class="text-xs text-white/45">{{ t.exams?.summary_best }}</div>
                                <div class="mt-1 text-xl font-semibold tabular-nums text-emerald-200">{{ summary.best_percent ?? '—' }}</div>
                            </div>
                            <div class="admin-card p-4">
                                <div class="text-xs text-white/45">{{ t.exams?.summary_worst }}</div>
                                <div class="mt-1 text-xl font-semibold tabular-nums text-rose-200">{{ summary.worst_percent ?? '—' }}</div>
                            </div>
                        </div>

                        <div class="admin-card p-5">
                            <h3 class="mb-4 text-sm font-semibold text-white">{{ t.exams?.distribution }}</h3>
                            <div class="space-y-3">
                                <div v-for="(d, i) in summary.distribution" :key="i">
                                    <div class="mb-1 flex justify-between text-xs text-white/70">
                                        <span>{{ locale === 'en' && d.label_en ? d.label_en : d.label }}</span>
                                        <span class="tabular-nums">{{ d.count }} · {{ d.percent }}%</span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-white/10">
                                        <div
                                            class="h-full rounded-full transition-all duration-700"
                                            :style="{ width: Math.max(d.percent, d.count ? 4 : 0) + '%', background: d.color || '#34d399' }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="admin-card overflow-hidden">
                            <div class="border-b border-white/10 px-4 py-3 text-sm font-semibold text-white">{{ t.exams?.attempts_list }}</div>
                            <div v-if="!attemptRows.length" class="px-4 py-10 text-center text-sm text-white/45">{{ t.exams?.no_attempts }}</div>
                            <table v-else class="min-w-full text-sm">
                                <thead class="border-b border-white/10 text-xs text-white/40">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-medium">{{ t.exams?.col_student }}</th>
                                        <th class="px-4 py-2 text-left font-medium">{{ t.exams?.col_score }}</th>
                                        <th class="px-4 py-2 text-left font-medium">{{ t.exams?.col_grade }}</th>
                                        <th class="px-4 py-2 text-left font-medium">{{ t.exams?.col_passed }}</th>
                                        <th class="px-4 py-2 text-left font-medium">{{ t.exams?.col_submitted }}</th>
                                        <th class="px-4 py-2" />
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="a in attemptRows" :key="a.id" class="border-b border-white/5 hover:bg-white/[0.03]">
                                        <td class="px-4 py-2.5">
                                            <div class="text-white">{{ a.user?.name }}</div>
                                            <div class="text-xs text-white/40">{{ a.user?.email }}</div>
                                        </td>
                                        <td class="px-4 py-2.5 tabular-nums text-white/80">{{ a.score_percent }}%</td>
                                        <td class="px-4 py-2.5">
                                            <span
                                                class="inline-flex rounded-lg px-2 py-0.5 text-xs"
                                                :style="{ background: (a.grade_color || '#34d399') + '22', color: a.grade_color || '#34d399' }"
                                            >
                                                {{ gradeLabel(a) || '—' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <span :class="a.passed ? 'text-emerald-300' : 'text-rose-300'">
                                                {{ a.passed ? t.exams?.passed : t.exams?.failed }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 text-xs text-white/50">{{ fmtWhen(a.submitted_at) }}</td>
                                        <td class="px-4 py-2.5">
                                            <Link :href="`/admin/exams/${selected.id}/attempts/${a.id}`" class="text-xs text-sky-300 hover:underline">
                                                {{ t.exams?.view_attempt }}
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <div v-else class="admin-card px-6 py-16 text-center text-sm text-white/45">
                        {{ t.exams?.pick_exam }}
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
