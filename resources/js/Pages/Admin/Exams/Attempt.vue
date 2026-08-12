<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    exam: { type: Object, required: true },
    attempt: { type: Object, required: true },
});

const page = usePage();
const t = computed(() => page.props.translations?.admin ?? {});
const locale = computed(() => page.props.locale ?? 'ar');

const answersByQ = computed(() => {
    const map = {};
    for (const a of props.attempt.answers || []) {
        map[a.question_id] = a;
    }
    return map;
});

const questions = computed(() => {
    const seen = new Set();
    const list = [];
    for (const a of props.attempt.answers || []) {
        if (a.question && !seen.has(a.question.id)) {
            seen.add(a.question.id);
            list.push(a.question);
        }
    }
    return list.sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0));
});

const ring = computed(() => {
    const p = Number(props.attempt.score_percent) || 0;
    const c = 2 * Math.PI * 54;
    return { dash: c, offset: c - (p / 100) * c, color: props.attempt.grade_color || (props.attempt.passed ? '#34d399' : '#fb7185') };
});

function studentAnswer(q) {
    const a = answersByQ.value[q.id];
    if (!a) return t.value.exams?.unanswered;
    if (q.type === 'short_answer') return a.text_answer || t.value.exams?.unanswered;
    return a.selected_option?.localized_label || a.selected_option?.label || t.value.exams?.unanswered;
}

function correctAnswer(q) {
    if (q.type === 'short_answer') {
        return (q.accepted_answers || []).join(' · ') || '—';
    }
    const ok = (q.options || []).filter((o) => o.is_correct);
    return ok.map((o) => o.localized_label || o.label).join(' · ') || '—';
}
</script>

<template>
    <Head :title="t.exams?.attempt_heading" />
    <AdminLayout :heading="t.exams?.attempt_heading ?? ''" :subheading="t.exams?.attempt_sub ?? ''">
        <div class="admin-content max-w-4xl space-y-5">
            <Link :href="`/admin/exams/reports?exam_id=${exam.id}`" class="inline-flex text-xs text-emerald-200 hover:underline">
                ← {{ t.exams?.reports_title }}
            </Link>

            <div class="admin-card relative overflow-hidden p-6">
                <div class="pointer-events-none absolute inset-0 opacity-40" aria-hidden="true">
                    <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full blur-3xl" :style="{ background: ring.color }" />
                </div>
                <div class="relative flex flex-wrap items-center gap-8">
                    <div class="relative h-32 w-32 shrink-0">
                        <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                            <circle cx="60" cy="60" r="54" fill="none" stroke="rgba(255,255,255,.08)" stroke-width="10" />
                            <circle
                                cx="60"
                                cy="60"
                                r="54"
                                fill="none"
                                :stroke="ring.color"
                                stroke-width="10"
                                stroke-linecap="round"
                                :stroke-dasharray="ring.dash"
                                :stroke-dashoffset="ring.offset"
                                class="exam-ring"
                            />
                        </svg>
                        <div class="absolute inset-0 grid place-items-center">
                            <div class="text-center">
                                <div class="text-2xl font-bold tabular-nums text-white">{{ attempt.score_percent }}%</div>
                                <div class="text-[10px] text-white/50">{{ attempt.score_points }}/{{ attempt.max_points }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="text-lg font-semibold text-white">{{ exam.localized_title || exam.title }}</div>
                        <div class="text-sm text-white/70">{{ attempt.user?.name }} · {{ attempt.user?.email }}</div>
                        <div class="flex flex-wrap gap-2 pt-1">
                            <span
                                class="rounded-lg px-2.5 py-1 text-xs font-medium"
                                :style="{ background: (attempt.grade_color || '#34d399') + '22', color: attempt.grade_color || '#34d399' }"
                            >
                                {{ locale === 'en' && attempt.grade_label_en ? attempt.grade_label_en : attempt.grade_label }}
                            </span>
                            <span :class="attempt.passed ? 'text-emerald-300' : 'text-rose-300'" class="rounded-lg bg-white/5 px-2.5 py-1 text-xs">
                                {{ attempt.passed ? t.exams?.passed : t.exams?.failed }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <article v-for="(q, i) in questions" :key="q.id" class="admin-card space-y-3 p-5">
                <div class="flex items-start gap-3">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-white/10 text-sm font-semibold text-white/80">{{ i + 1 }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-white">{{ q.localized_prompt || q.prompt }}</div>
                        <div class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-3">
                                <div class="text-[11px] text-white/40">{{ t.exams?.student_answer }}</div>
                                <div class="mt-1 text-white/85">{{ studentAnswer(q) }}</div>
                            </div>
                            <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-3">
                                <div class="text-[11px] text-emerald-200/60">{{ t.exams?.correct_answer }}</div>
                                <div class="mt-1 text-emerald-100">{{ correctAnswer(q) }}</div>
                            </div>
                        </div>
                        <div class="mt-2 flex gap-3 text-xs">
                            <span :class="answersByQ[q.id]?.is_correct ? 'text-emerald-300' : 'text-rose-300'">
                                {{ answersByQ[q.id]?.is_correct ? '✓' : '✗' }}
                            </span>
                            <span class="text-white/50">{{ t.exams?.points_awarded }}: {{ answersByQ[q.id]?.points_awarded ?? 0 }} / {{ q.points }}</span>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </AdminLayout>
</template>

<style scoped>
.exam-ring {
    transition: stroke-dashoffset 1s ease;
}
</style>
