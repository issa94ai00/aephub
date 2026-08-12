<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    exam: { type: Object, default: null },
    courses: { type: Array, default: () => [] },
    default_course_id: { type: Number, default: null },
    default_grade_bands: { type: Array, default: () => [] },
});

const page = usePage();
const t = computed(() => page.props.translations?.admin ?? {});
const flash = computed(() => page.props.flash?.status);
const isEdit = computed(() => !!props.exam?.id);

const inputCls =
    'mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white placeholder-white/30 focus:border-emerald-400/40 focus:outline-none';

function blankMcq() {
    return {
        type: 'multiple_choice',
        prompt: '',
        prompt_en: '',
        points: 1,
        explanation: '',
        explanation_en: '',
        case_sensitive: false,
        accepted_answers: [],
        correct_true: true,
        options: [
            { label: '', label_en: '', is_correct: true, sort_order: 0 },
            { label: '', label_en: '', is_correct: false, sort_order: 1 },
        ],
    };
}

function blankTf() {
    return {
        type: 'true_false',
        prompt: '',
        prompt_en: '',
        points: 1,
        explanation: '',
        explanation_en: '',
        case_sensitive: false,
        accepted_answers: [],
        correct_true: true,
        options: [],
    };
}

function blankSa() {
    return {
        type: 'short_answer',
        prompt: '',
        prompt_en: '',
        points: 1,
        explanation: '',
        explanation_en: '',
        case_sensitive: false,
        accepted_answers: [''],
        correct_true: true,
        options: [],
    };
}

const form = useForm({
    course_id: props.exam?.course_id ?? props.default_course_id ?? props.courses[0]?.id ?? '',
    title: props.exam?.title ?? '',
    title_en: props.exam?.title_en ?? '',
    description: props.exam?.description ?? '',
    description_en: props.exam?.description_en ?? '',
    status: props.exam?.status ?? 'draft',
    duration_minutes: props.exam?.duration_minutes ?? null,
    pass_percent: props.exam?.pass_percent ?? 60,
    max_attempts: props.exam?.max_attempts ?? null,
    shuffle_questions: props.exam?.shuffle_questions ?? false,
    shuffle_options: props.exam?.shuffle_options ?? false,
    show_correct_answers: props.exam?.show_correct_answers ?? true,
    available_from: props.exam?.available_from ?? '',
    available_until: props.exam?.available_until ?? '',
    grade_bands: props.exam?.grade_bands?.length
        ? JSON.parse(JSON.stringify(props.exam.grade_bands))
        : JSON.parse(JSON.stringify(props.default_grade_bands)),
    questions: props.exam?.questions?.length
        ? JSON.parse(JSON.stringify(props.exam.questions)).map((q) => ({
              ...q,
              accepted_text: (q.accepted_answers || []).join('\n'),
          }))
        : [],
});

const heading = computed(() => (isEdit.value ? t.value.exams?.edit_heading : t.value.exams?.create_heading));
const subheading = computed(() =>
    isEdit.value
        ? (t.value.exams?.edit_sub ?? '').replace(':name', props.exam?.title ?? '')
        : t.value.exams?.create_sub
);

function addQuestion(kind) {
    const factory = kind === 'true_false' ? blankTf : kind === 'short_answer' ? blankSa : blankMcq;
    const q = factory();
    q.accepted_text = (q.accepted_answers || []).join('\n');
    form.questions.push(q);
}

function removeQuestion(i) {
    form.questions.splice(i, 1);
}

function moveQuestion(i, dir) {
    const j = i + dir;
    if (j < 0 || j >= form.questions.length) return;
    const tmp = form.questions[i];
    form.questions[i] = form.questions[j];
    form.questions[j] = tmp;
}

function addOption(qi) {
    form.questions[qi].options.push({ label: '', label_en: '', is_correct: false, sort_order: form.questions[qi].options.length });
}

function removeOption(qi, oi) {
    form.questions[qi].options.splice(oi, 1);
}

function markCorrect(qi, oi) {
    form.questions[qi].options.forEach((o, idx) => {
        o.is_correct = idx === oi;
    });
}

function addBand() {
    form.grade_bands.push({
        min_percent: 0,
        max_percent: 0,
        label: '',
        label_en: '',
        color: '#94a3b8',
        sort_order: form.grade_bands.length,
    });
}

function resetBands() {
    form.grade_bands = JSON.parse(JSON.stringify(props.default_grade_bands));
}

function typeLabel(type) {
    return t.value.exams?.[`type_${type}`] ?? type;
}

function submit() {
    form
        .transform((data) => ({
            ...data,
            duration_minutes: data.duration_minutes === '' || data.duration_minutes === null ? null : Number(data.duration_minutes),
            max_attempts: data.max_attempts === '' || data.max_attempts === null ? null : Number(data.max_attempts),
            available_from: data.available_from || null,
            available_until: data.available_until || null,
            questions: data.questions.map((q, i) => {
                const row = { ...q, sort_order: i };
                if (q.type === 'short_answer') {
                    row.accepted_answers = String(q.accepted_text || '')
                        .split('\n')
                        .map((s) => s.trim())
                        .filter(Boolean);
                }
                delete row.accepted_text;
                return row;
            }),
        }))
        [isEdit.value ? 'put' : 'post'](isEdit.value ? `/admin/exams/${props.exam.id}` : '/admin/exams');
}
</script>

<template>
    <Head :title="heading" />
    <AdminLayout :heading="heading ?? ''" :subheading="subheading ?? ''">
        <div class="admin-content max-w-5xl space-y-5">
            <div
                v-if="flash"
                class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100"
            >
                {{ flash }}
            </div>

            <Link href="/admin/exams" class="inline-flex items-center gap-1 text-xs text-emerald-200 hover:underline">
                <span aria-hidden="true">&larr;</span>
                {{ t.exams?.back_list }}
            </Link>

            <form class="space-y-5" @submit.prevent="submit">
                <section class="admin-card space-y-4 p-5">
                    <h2 class="text-sm font-semibold text-white">{{ t.exams?.basics }}</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs text-white/70">{{ t.exams?.title_ar }}</label>
                            <input v-model="form.title" required :class="inputCls" />
                            <p v-if="form.errors.title" class="mt-1 text-xs text-rose-300">{{ form.errors.title }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-white/70">{{ t.exams?.title_en }}</label>
                            <input v-model="form.title_en" dir="ltr" :class="inputCls" />
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs text-white/70">{{ t.exams?.description_ar }}</label>
                            <textarea v-model="form.description" rows="3" :class="inputCls" />
                        </div>
                        <div>
                            <label class="block text-xs text-white/70">{{ t.exams?.description_en }}</label>
                            <textarea v-model="form.description_en" rows="3" dir="ltr" :class="inputCls" />
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label class="block text-xs text-white/70">{{ t.exams?.course }}</label>
                            <select v-model="form.course_id" required :class="inputCls">
                                <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.localized_title }}</option>
                            </select>
                            <p v-if="form.errors.course_id" class="mt-1 text-xs text-rose-300">{{ form.errors.course_id }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-white/70">{{ t.exams?.status }}</label>
                            <select v-model="form.status" :class="inputCls">
                                <option value="draft">{{ t.exams?.status_draft }}</option>
                                <option value="published">{{ t.exams?.status_published }}</option>
                                <option value="archived">{{ t.exams?.status_archived }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-white/70">{{ t.exams?.pass_percent }}</label>
                            <input v-model.number="form.pass_percent" type="number" min="0" max="100" step="0.01" dir="ltr" :class="inputCls" />
                        </div>
                        <div>
                            <label class="block text-xs text-white/70">{{ t.exams?.duration }}</label>
                            <input v-model="form.duration_minutes" type="number" min="1" dir="ltr" :class="inputCls" />
                            <p class="mt-1 text-[11px] text-white/40">{{ t.exams?.duration_hint }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-white/70">{{ t.exams?.max_attempts }}</label>
                            <input v-model="form.max_attempts" type="number" min="1" dir="ltr" :class="inputCls" />
                            <p class="mt-1 text-[11px] text-white/40">{{ t.exams?.max_attempts_hint }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-white/70">{{ t.exams?.available_from }}</label>
                            <input v-model="form.available_from" type="datetime-local" dir="ltr" :class="inputCls" />
                        </div>
                        <div>
                            <label class="block text-xs text-white/70">{{ t.exams?.available_until }}</label>
                            <input v-model="form.available_until" type="datetime-local" dir="ltr" :class="inputCls" />
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-4 pt-1 text-sm text-white/80">
                        <label class="inline-flex items-center gap-2"><input v-model="form.shuffle_questions" type="checkbox" class="accent-emerald-500" /> {{ t.exams?.shuffle_questions }}</label>
                        <label class="inline-flex items-center gap-2"><input v-model="form.shuffle_options" type="checkbox" class="accent-emerald-500" /> {{ t.exams?.shuffle_options }}</label>
                        <label class="inline-flex items-center gap-2"><input v-model="form.show_correct_answers" type="checkbox" class="accent-emerald-500" /> {{ t.exams?.show_correct }}</label>
                    </div>
                </section>

                <section class="admin-card space-y-4 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h2 class="text-sm font-semibold text-white">{{ t.exams?.grade_scale }}</h2>
                            <p class="mt-0.5 text-xs text-white/45">{{ t.exams?.grade_scale_hint }}</p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" class="rounded-lg border border-white/15 px-3 py-1.5 text-xs text-white/70 hover:bg-white/5" @click="resetBands">
                                {{ t.exams?.reset_bands }}
                            </button>
                            <button type="button" class="rounded-lg bg-white/10 px-3 py-1.5 text-xs text-white hover:bg-white/15" @click="addBand">
                                {{ t.exams?.add_band }}
                            </button>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="(band, bi) in form.grade_bands"
                            :key="bi"
                            class="grid gap-2 rounded-xl border border-white/10 bg-white/[0.02] p-3 sm:grid-cols-6"
                        >
                            <div>
                                <label class="text-[10px] text-white/45">{{ t.exams?.band_min }}</label>
                                <input v-model.number="band.min_percent" type="number" min="0" max="100" step="0.01" dir="ltr" :class="inputCls" class="!mt-1" />
                            </div>
                            <div>
                                <label class="text-[10px] text-white/45">{{ t.exams?.band_max }}</label>
                                <input v-model.number="band.max_percent" type="number" min="0" max="100" step="0.01" dir="ltr" :class="inputCls" class="!mt-1" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-[10px] text-white/45">{{ t.exams?.band_label }}</label>
                                <input v-model="band.label" :class="inputCls" class="!mt-1" />
                            </div>
                            <div>
                                <label class="text-[10px] text-white/45">{{ t.exams?.band_label_en }}</label>
                                <input v-model="band.label_en" dir="ltr" :class="inputCls" class="!mt-1" />
                            </div>
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <label class="text-[10px] text-white/45">{{ t.exams?.band_color }}</label>
                                    <input v-model="band.color" type="color" class="mt-1 h-10 w-full cursor-pointer rounded-lg border border-white/10 bg-transparent" />
                                </div>
                                <button type="button" class="mb-0.5 text-xs text-rose-300" @click="form.grade_bands.splice(bi, 1)">×</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-white">{{ t.exams?.questions }}</h2>
                            <p class="mt-0.5 text-xs text-white/45">{{ t.exams?.questions_hint }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="rounded-xl bg-sky-500/20 px-3 py-2 text-xs font-medium text-sky-200 ring-1 ring-sky-400/30 hover:bg-sky-500/30" @click="addQuestion('multiple_choice')">
                                + {{ t.exams?.add_mcq }}
                            </button>
                            <button type="button" class="rounded-xl bg-violet-500/20 px-3 py-2 text-xs font-medium text-violet-200 ring-1 ring-violet-400/30 hover:bg-violet-500/30" @click="addQuestion('true_false')">
                                + {{ t.exams?.add_tf }}
                            </button>
                            <button type="button" class="rounded-xl bg-amber-500/20 px-3 py-2 text-xs font-medium text-amber-100 ring-1 ring-amber-400/30 hover:bg-amber-500/30" @click="addQuestion('short_answer')">
                                + {{ t.exams?.add_sa }}
                            </button>
                        </div>
                    </div>

                    <p v-if="!form.questions.length" class="rounded-2xl border border-dashed border-white/15 px-4 py-8 text-center text-sm text-white/45">
                        {{ t.exams?.no_questions }}
                    </p>

                    <article
                        v-for="(q, qi) in form.questions"
                        :key="qi"
                        class="admin-card exam-q-card space-y-4 p-5"
                        :style="{ animationDelay: `${qi * 0.04}s` }"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="grid h-8 w-8 place-items-center rounded-xl bg-emerald-500/15 text-sm font-semibold text-emerald-200">{{ qi + 1 }}</span>
                                <span class="rounded-lg bg-white/10 px-2 py-0.5 text-xs text-white/70">{{ typeLabel(q.type) }}</span>
                            </div>
                            <div class="flex gap-2 text-xs">
                                <button type="button" class="text-white/50 hover:text-white" @click="moveQuestion(qi, -1)">{{ t.exams?.move_up }}</button>
                                <button type="button" class="text-white/50 hover:text-white" @click="moveQuestion(qi, 1)">{{ t.exams?.move_down }}</button>
                                <button type="button" class="text-rose-300 hover:underline" @click="removeQuestion(qi)">{{ t.exams?.remove_question }}</button>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="block text-xs text-white/70">{{ t.exams?.prompt_ar }}</label>
                                <textarea v-model="q.prompt" rows="2" required :class="inputCls" />
                            </div>
                            <div>
                                <label class="block text-xs text-white/70">{{ t.exams?.prompt_en }}</label>
                                <textarea v-model="q.prompt_en" rows="2" dir="ltr" :class="inputCls" />
                            </div>
                            <div>
                                <label class="block text-xs text-white/70">{{ t.exams?.points }}</label>
                                <input v-model.number="q.points" type="number" min="0.25" step="0.25" dir="ltr" :class="inputCls" />
                            </div>
                        </div>

                        <div v-if="q.type === 'multiple_choice'" class="space-y-2">
                            <div class="text-xs font-medium text-white/70">{{ t.exams?.options }}</div>
                            <div v-for="(opt, oi) in q.options" :key="oi" class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="grid h-8 w-8 place-items-center rounded-lg ring-1 transition"
                                    :class="opt.is_correct ? 'bg-emerald-500/20 text-emerald-200 ring-emerald-400/40' : 'bg-white/5 text-white/40 ring-white/10'"
                                    :title="t.exams?.option_correct"
                                    @click="markCorrect(qi, oi)"
                                >
                                    ✓
                                </button>
                                <input v-model="opt.label" :placeholder="t.exams?.band_label" required class="min-w-[10rem] flex-1 rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
                                <input v-model="opt.label_en" dir="ltr" placeholder="EN" class="w-36 rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
                                <button type="button" class="text-rose-300" @click="removeOption(qi, oi)">×</button>
                            </div>
                            <button type="button" class="text-xs text-emerald-300 hover:underline" @click="addOption(qi)">+ {{ t.exams?.add_option }}</button>
                        </div>

                        <div v-else-if="q.type === 'true_false'" class="space-y-2">
                            <div class="text-xs font-medium text-white/70">{{ t.exams?.tf_correct }}</div>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="rounded-xl px-4 py-2 text-sm ring-1 transition"
                                    :class="q.correct_true ? 'bg-emerald-500/20 text-emerald-100 ring-emerald-400/40' : 'bg-white/5 text-white/50 ring-white/10'"
                                    @click="q.correct_true = true"
                                >
                                    {{ t.exams?.tf_true }}
                                </button>
                                <button
                                    type="button"
                                    class="rounded-xl px-4 py-2 text-sm ring-1 transition"
                                    :class="!q.correct_true ? 'bg-rose-500/20 text-rose-100 ring-rose-400/40' : 'bg-white/5 text-white/50 ring-white/10'"
                                    @click="q.correct_true = false"
                                >
                                    {{ t.exams?.tf_false }}
                                </button>
                            </div>
                        </div>

                        <div v-else class="space-y-2">
                            <label class="block text-xs text-white/70">{{ t.exams?.accepted_answers }}</label>
                            <textarea v-model="q.accepted_text" rows="3" :class="inputCls" :placeholder="t.exams?.accepted_hint" />
                            <label class="inline-flex items-center gap-2 text-xs text-white/70">
                                <input v-model="q.case_sensitive" type="checkbox" class="accent-emerald-500" />
                                {{ t.exams?.case_sensitive }}
                            </label>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs text-white/70">{{ t.exams?.explanation_ar }}</label>
                                <textarea v-model="q.explanation" rows="2" :class="inputCls" />
                            </div>
                            <div>
                                <label class="block text-xs text-white/70">{{ t.exams?.explanation_en }}</label>
                                <textarea v-model="q.explanation_en" rows="2" dir="ltr" :class="inputCls" />
                            </div>
                        </div>
                    </article>
                </section>

                <div class="flex flex-wrap gap-3 pb-8">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500 disabled:opacity-50"
                    >
                        {{ isEdit ? t.exams?.save : t.exams?.create_btn }}
                    </button>
                    <Link href="/admin/exams" class="rounded-xl border border-white/15 px-5 py-2.5 text-sm text-white/80 hover:bg-white/5">
                        {{ t.exams?.cancel }}
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
.exam-q-card {
    animation: examRise 0.4s ease both;
}
@keyframes examRise {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: none;
    }
}
</style>
