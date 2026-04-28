<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import SiteLayout from '../../../Layouts/SiteLayout.vue';
import { useRegistrationAcademicPicker } from '../../../composables/useRegistrationAcademicPicker';

/** Matches server: `regex:/^[0-9+\s().-]+$/u` and max:32 */
const PHONE_REGEX = /^[0-9+\s().-]+$/u;

function emailLooksValid(s) {
    if (!s || s.length > 255) {
        return false;
    }
    return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(s.trim());
}

const page = usePage();
const t = computed(() => page.props.translations.site);
const tr = computed(() => page.props.translations.registration);
const flash = computed(() => page.props.flash);
const routes = computed(() => page.props.siteChrome?.routes ?? {});

const props = defineProps({
    universities: { type: Array, default: () => [] },
    pickerConfig: { type: Object, required: true },
});

const tab = ref((page.props.old?.account_type === 'teacher' ? 'teacher' : 'student'));

const studentForm = useForm({
    account_type: 'student',
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    terms_accepted: false,
    study_term_id: '',
});

const teacherForm = useForm({
    account_type: 'teacher',
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    terms_accepted: false,
});

function applyOldInput() {
    const old = page.props.old;
    if (!old || typeof old !== 'object') {
        return;
    }
    const acc = old.account_type === 'teacher' ? 'teacher' : 'student';
    tab.value = acc;
    const target = acc === 'teacher' ? teacherForm : studentForm;
    target.name = old.name ?? '';
    target.email = old.email ?? '';
    target.phone = old.phone ?? '';
    target.password = '';
    target.password_confirmation = '';
    target.terms_accepted = Boolean(old.terms_accepted);
    if (acc === 'student') {
        studentForm.study_term_id = old.study_term_id != null ? String(old.study_term_id) : '';
    }
}

applyOldInput();

watch(
    () => page.props.old,
    () => applyOldInput(),
    { deep: true },
);

const {
    hasUniversities,
    labels: pickerLabels,
    universityId,
    facultyId,
    studyYearId,
    studyTermId,
    facultyOptions,
    yearOptions,
    termOptions,
    loadingFaculties,
    loadingYears,
    loadingTerms,
    fetchError,
    loadFaculties,
    loadYears,
    loadTerms,
    academicSubmitBlocked,
} = useRegistrationAcademicPicker(props.pickerConfig);

function scrollToFirstFormError() {
    nextTick(() => {
        document.querySelector('[data-registration-errors]')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
}

function clientValidateStudent() {
    studentForm.clearErrors();
    const r = tr.value;
    const err = {};

    const name = studentForm.name.trim();
    if (!name) {
        err.name = r.client_name_required;
    } else if (name.length > 255) {
        err.name = r.client_name_max;
    }

    const email = studentForm.email.trim();
    if (!email) {
        err.email = r.client_email_required;
    } else if (email.length > 255) {
        err.email = r.client_email_max;
    } else if (!emailLooksValid(email)) {
        err.email = r.client_email_invalid;
    }

    const phone = studentForm.phone.trim();
    if (!phone) {
        err.phone = r.client_phone_required;
    } else if (phone.length > 32) {
        err.phone = r.client_phone_max;
    } else if (!PHONE_REGEX.test(phone)) {
        err.phone = r.client_phone_format;
    }

    if (!studentForm.password) {
        err.password = r.client_password_required;
    } else if (studentForm.password.length < 8) {
        err.password = r.client_password_min;
    }
    if (studentForm.password !== studentForm.password_confirmation) {
        err.password_confirmation = r.client_password_confirmed;
    }

    if (!studentForm.terms_accepted) {
        err.terms_accepted = r.terms_validation;
    }

    if (props.universities.length > 0) {
        const tid = studyTermId.value;
        if (tid === '' || tid === null || tid === undefined) {
            err.study_term_id = r.client_study_term_required;
        }
    }

    if (Object.keys(err).length) {
        studentForm.setError(err);
        scrollToFirstFormError();
        return false;
    }
    return true;
}

function clientValidateTeacher() {
    teacherForm.clearErrors();
    const r = tr.value;
    const err = {};

    const name = teacherForm.name.trim();
    if (!name) {
        err.name = r.client_name_required;
    } else if (name.length > 255) {
        err.name = r.client_name_max;
    }

    const email = teacherForm.email.trim();
    if (!email) {
        err.email = r.client_email_required;
    } else if (email.length > 255) {
        err.email = r.client_email_max;
    } else if (!emailLooksValid(email)) {
        err.email = r.client_email_invalid;
    }

    const phone = teacherForm.phone.trim();
    if (!phone) {
        err.phone = r.client_phone_required;
    } else if (phone.length > 32) {
        err.phone = r.client_phone_max;
    } else if (!PHONE_REGEX.test(phone)) {
        err.phone = r.client_phone_format;
    }

    if (!teacherForm.password) {
        err.password = r.client_password_required;
    } else if (teacherForm.password.length < 8) {
        err.password = r.client_password_min;
    }
    if (teacherForm.password !== teacherForm.password_confirmation) {
        err.password_confirmation = r.client_password_confirmed;
    }

    if (!teacherForm.terms_accepted) {
        err.terms_accepted = r.terms_validation;
    }

    if (Object.keys(err).length) {
        teacherForm.setError(err);
        scrollToFirstFormError();
        return false;
    }
    return true;
}

function submitStudent() {
    if (!clientValidateStudent()) {
        return;
    }
    studentForm.account_type = 'student';
    studentForm.study_term_id = studyTermId.value;
    studentForm.post(routes.value.register_store ?? '', { preserveScroll: true });
}

function submitTeacher() {
    if (!clientValidateTeacher()) {
        return;
    }
    teacherForm.post(routes.value.register_store ?? '', { preserveScroll: true });
}

const studentErrors = computed(() => studentForm.errors || {});

const teacherErrors = computed(() => teacherForm.errors || {});

function errorList(formErr) {
    if (!formErr || typeof formErr !== 'object') {
        return [];
    }
    return Object.values(formErr).flat();
}
</script>

<template>
    <SiteLayout>
        <Head :title="t.registration_page?.document_title ?? t.registration_page?.title">
            <meta
                v-if="t.registration_page?.meta_description"
                name="description"
                :content="t.registration_page.meta_description"
                head-key="description"
            />
        </Head>

        <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-14">
            <section class="reveal site-panel">
                <div class="max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400/90">
                        {{ t.registration_page?.kicker }}
                    </p>
                    <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 sm:text-3xl">
                        {{ t.registration_page?.title }}
                    </h1>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        {{ t.registration_page?.lead }}
                    </p>
                </div>

                <div
                    v-if="flash?.status"
                    class="mt-6 rounded-2xl border border-emerald-200/50 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-900 shadow-sm dark:border-emerald-800/40 dark:bg-emerald-950/35 dark:text-emerald-200"
                >
                    {{ flash.status }}
                </div>

                <div class="mt-8">
                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50/80 p-1.5 dark:border-slate-700/60 dark:bg-slate-800/50">
                        <div class="grid grid-cols-2 gap-1.5">
                            <button
                                type="button"
                                class="rounded-xl px-3 py-2.5 text-sm font-bold transition duration-300"
                                :class="
                                    tab === 'student'
                                        ? 'bg-gradient-to-b from-amber-500 to-orange-600 text-white shadow-md shadow-orange-900/25'
                                        : 'bg-white/90 text-slate-700 hover:bg-white dark:bg-slate-800/90 dark:text-slate-200 dark:hover:bg-slate-800'
                                "
                                @click="tab = 'student'"
                            >
                                {{ t.registration_page?.tab_student }}
                            </button>
                            <button
                                type="button"
                                class="rounded-xl px-3 py-2.5 text-sm font-bold transition duration-300"
                                :class="
                                    tab === 'teacher'
                                        ? 'bg-gradient-to-b from-amber-500 to-orange-600 text-white shadow-md shadow-orange-900/25'
                                        : 'bg-white/90 text-slate-700 hover:bg-white dark:bg-slate-800/90 dark:text-slate-200 dark:hover:bg-slate-800'
                                "
                                @click="tab = 'teacher'"
                            >
                                {{ t.registration_page?.tab_teacher }}
                            </button>
                        </div>
                    </div>

                    <div
                        v-show="tab === 'student'"
                        class="mt-5 rounded-2xl border border-slate-200/70 bg-slate-50/70 p-5 dark:border-slate-700/60 dark:bg-slate-800/50"
                    >
                        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-50">
                            {{ t.registration_page?.panel_student_title }}
                        </h2>
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                            {{ t.registration_page?.panel_student_hint }}
                        </p>

                        <div
                            v-if="errorList(studentErrors).length"
                            data-registration-errors
                            class="mt-6 rounded-2xl border border-rose-200/50 bg-rose-50/80 px-4 py-3 text-sm text-rose-900 shadow-sm dark:border-rose-800/40 dark:bg-rose-950/35 dark:text-rose-200"
                        >
                            <ul class="list-disc space-y-1 ps-5">
                                <li v-for="(err, i) in errorList(studentErrors)" :key="i">{{ err }}</li>
                            </ul>
                        </div>

                        <form class="mt-4 space-y-4" @submit.prevent="submitStudent">
                            <div>
                                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{
                                    t.form?.full_name
                                }}</label>
                                <input
                                    v-model="studentForm.name"
                                    autocomplete="name"
                                    maxlength="255"
                                    class="site-input"
                                    :class="{ 'ring-2 ring-rose-400/80': studentForm.errors.name }"
                                />
                                <p v-if="studentForm.errors.name" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                    {{ studentForm.errors.name }}
                                </p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{
                                    t.form?.email
                                }}</label>
                                <input
                                    v-model="studentForm.email"
                                    type="email"
                                    autocomplete="email"
                                    dir="ltr"
                                    maxlength="255"
                                    class="site-input"
                                    :class="{ 'ring-2 ring-rose-400/80': studentForm.errors.email }"
                                />
                                <p v-if="studentForm.errors.email" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                    {{ studentForm.errors.email }}
                                </p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{
                                    t.form?.phone
                                }}</label>
                                <input
                                    v-model="studentForm.phone"
                                    type="tel"
                                    autocomplete="tel"
                                    dir="ltr"
                                    maxlength="32"
                                    :placeholder="t.form?.phone_placeholder"
                                    class="site-input"
                                    :class="{ 'ring-2 ring-rose-400/80': studentForm.errors.phone }"
                                />
                                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-500">{{ t.form?.phone_hint }}</p>
                                <p v-if="studentForm.errors.phone" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                    {{ studentForm.errors.phone }}
                                </p>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{
                                        t.form?.password
                                    }}</label>
                                    <input
                                        v-model="studentForm.password"
                                        type="password"
                                        autocomplete="new-password"
                                        minlength="8"
                                        class="site-input"
                                        :class="{ 'ring-2 ring-rose-400/80': studentForm.errors.password }"
                                    />
                                    <p
                                        v-if="studentForm.errors.password"
                                        class="mt-1 text-xs text-rose-600 dark:text-rose-400"
                                    >
                                        {{ studentForm.errors.password }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{
                                        t.form?.password_confirmation
                                    }}</label>
                                    <input
                                        v-model="studentForm.password_confirmation"
                                        type="password"
                                        autocomplete="new-password"
                                        minlength="8"
                                        class="site-input"
                                        :class="{
                                            'ring-2 ring-rose-400/80': studentForm.errors.password_confirmation,
                                        }"
                                    />
                                    <p
                                        v-if="studentForm.errors.password_confirmation"
                                        class="mt-1 text-xs text-rose-600 dark:text-rose-400"
                                    >
                                        {{ studentForm.errors.password_confirmation }}
                                    </p>
                                </div>
                            </div>

                            <p class="text-[11px] leading-relaxed text-slate-600 dark:text-slate-400">
                                {{ t.registration_page?.academic_edit_hint }}
                            </p>
                            <div
                                v-if="!universities.length"
                                class="rounded-xl border border-amber-200/80 bg-amber-50/80 px-3 py-2 text-xs text-amber-950 dark:border-amber-800/50 dark:bg-amber-950/30 dark:text-amber-100"
                            >
                                {{ t.registration_page?.universities_empty }}
                            </div>

                            <p
                                v-if="fetchError"
                                class="rounded-lg border border-rose-200/80 bg-rose-50/90 px-3 py-2 text-xs text-rose-900 dark:border-rose-800/50 dark:bg-rose-950/40 dark:text-rose-100"
                            >
                                {{ fetchError }}
                            </p>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{
                                        t.form?.university
                                    }}</label>
                                    <select
                                        v-model="universityId"
                                        class="site-input"
                                        :disabled="!hasUniversities"
                                        :required="hasUniversities"
                                        @change="loadFaculties()"
                                    >
                                        <option value="">{{ t.form?.select_university }}</option>
                                        <option v-for="uni in universities" :key="uni.id" :value="String(uni.id)">
                                            {{ uni.localized_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{
                                        t.form?.faculty
                                    }}</label>
                                    <select
                                        v-model="facultyId"
                                        class="site-input"
                                        :disabled="!universityId || loadingFaculties"
                                        :required="hasUniversities"
                                        @change="loadYears()"
                                    >
                                        <option value="">{{ pickerLabels.select_faculty }}</option>
                                        <option v-for="o in facultyOptions" :key="o.id" :value="String(o.id)">
                                            {{ o.localized_name }}
                                        </option>
                                    </select>
                                    <p
                                        v-show="loadingFaculties"
                                        class="mt-1 text-[11px] text-slate-500 dark:text-slate-500"
                                    >
                                        {{ t.form?.loading }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{
                                        t.form?.study_year
                                    }}</label>
                                    <select
                                        v-model="studyYearId"
                                        class="site-input"
                                        :disabled="!facultyId || loadingYears"
                                        :required="hasUniversities"
                                        @change="loadTerms()"
                                    >
                                        <option value="">{{ pickerLabels.select_year }}</option>
                                        <option v-for="o in yearOptions" :key="o.id" :value="String(o.id)">
                                            {{ o.localized_name }}
                                        </option>
                                    </select>
                                    <p v-show="loadingYears" class="mt-1 text-[11px] text-slate-500 dark:text-slate-500">
                                        {{ t.form?.loading }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{
                                        t.form?.study_term
                                    }}</label>
                                    <select
                                        v-model="studyTermId"
                                        class="site-input"
                                        :disabled="!studyYearId || loadingTerms"
                                        :required="hasUniversities"
                                    >
                                        <option value="">{{ pickerLabels.select_term }}</option>
                                        <option v-for="o in termOptions" :key="o.id" :value="String(o.id)">
                                            {{ o.localized_name }}
                                        </option>
                                    </select>
                                    <p v-show="loadingTerms" class="mt-1 text-[11px] text-slate-500 dark:text-slate-500">
                                        {{ t.form?.loading }}
                                    </p>
                                    <p
                                        v-if="studentForm.errors.study_term_id"
                                        class="mt-1 text-xs text-rose-600 dark:text-rose-400"
                                    >
                                        {{ studentForm.errors.study_term_id }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="rounded-xl border border-slate-200/80 bg-white/60 p-4 dark:border-slate-600/60 dark:bg-slate-900/30"
                                :class="{ 'ring-2 ring-rose-400/70': studentForm.errors.terms_accepted }"
                            >
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input
                                        v-model="studentForm.terms_accepted"
                                        type="checkbox"
                                        value="1"
                                        class="mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800"
                                    />
                                    <span class="text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                                        {{ tr.terms_agree_prefix }}
                                        <Link
                                            :href="routes.legal_privacy"
                                            class="font-semibold text-emerald-700 underline decoration-emerald-700/30 underline-offset-2 hover:text-emerald-800 dark:text-emerald-400 dark:decoration-emerald-400/30 dark:hover:text-emerald-300"
                                            >{{ tr.terms_link_label }}</Link
                                        >{{ tr.terms_agree_suffix }}
                                    </span>
                                </label>
                                <p
                                    v-if="studentForm.errors.terms_accepted"
                                    class="mt-2 text-xs text-rose-600 dark:text-rose-400"
                                >
                                    {{ studentForm.errors.terms_accepted }}
                                </p>
                            </div>

                            <button
                                type="submit"
                                class="site-btn-primary text-xs"
                                :disabled="studentForm.processing || academicSubmitBlocked"
                            >
                                {{ tr.submit_student }}
                            </button>
                        </form>
                    </div>

                    <div
                        v-show="tab === 'teacher'"
                        class="mt-5 rounded-2xl border border-emerald-200/50 bg-emerald-50/50 p-5 dark:border-emerald-800/40 dark:bg-emerald-950/25"
                    >
                        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-50">
                            {{ t.registration_page?.panel_teacher_title }}
                        </h2>
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                            {{ t.registration_page?.panel_teacher_hint }}
                        </p>

                        <div
                            v-if="errorList(teacherErrors).length"
                            data-registration-errors
                            class="mt-6 rounded-2xl border border-rose-200/50 bg-rose-50/80 px-4 py-3 text-sm text-rose-900 shadow-sm dark:border-rose-800/40 dark:bg-rose-950/35 dark:text-rose-200"
                        >
                            <ul class="list-disc space-y-1 ps-5">
                                <li v-for="(err, i) in errorList(teacherErrors)" :key="i">{{ err }}</li>
                            </ul>
                        </div>

                        <form class="mt-4 space-y-4" @submit.prevent="submitTeacher">
                            <div>
                                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{
                                    t.form?.full_name
                                }}</label>
                                <input
                                    v-model="teacherForm.name"
                                    autocomplete="name"
                                    maxlength="255"
                                    class="site-input"
                                    :class="{ 'ring-2 ring-rose-400/80': teacherForm.errors.name }"
                                />
                                <p v-if="teacherForm.errors.name" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                    {{ teacherForm.errors.name }}
                                </p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{
                                    t.form?.email
                                }}</label>
                                <input
                                    v-model="teacherForm.email"
                                    type="email"
                                    autocomplete="email"
                                    dir="ltr"
                                    maxlength="255"
                                    class="site-input"
                                    :class="{ 'ring-2 ring-rose-400/80': teacherForm.errors.email }"
                                />
                                <p v-if="teacherForm.errors.email" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                    {{ teacherForm.errors.email }}
                                </p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{
                                    t.form?.phone
                                }}</label>
                                <input
                                    v-model="teacherForm.phone"
                                    type="tel"
                                    autocomplete="tel"
                                    dir="ltr"
                                    maxlength="32"
                                    :placeholder="t.form?.phone_placeholder"
                                    class="site-input"
                                    :class="{ 'ring-2 ring-rose-400/80': teacherForm.errors.phone }"
                                />
                                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-500">{{ t.form?.phone_hint }}</p>
                                <p v-if="teacherForm.errors.phone" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                    {{ teacherForm.errors.phone }}
                                </p>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{
                                        t.form?.password
                                    }}</label>
                                    <input
                                        v-model="teacherForm.password"
                                        type="password"
                                        autocomplete="new-password"
                                        minlength="8"
                                        class="site-input"
                                        :class="{ 'ring-2 ring-rose-400/80': teacherForm.errors.password }"
                                    />
                                    <p
                                        v-if="teacherForm.errors.password"
                                        class="mt-1 text-xs text-rose-600 dark:text-rose-400"
                                    >
                                        {{ teacherForm.errors.password }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{
                                        t.form?.password_confirmation
                                    }}</label>
                                    <input
                                        v-model="teacherForm.password_confirmation"
                                        type="password"
                                        autocomplete="new-password"
                                        minlength="8"
                                        class="site-input"
                                        :class="{
                                            'ring-2 ring-rose-400/80': teacherForm.errors.password_confirmation,
                                        }"
                                    />
                                    <p
                                        v-if="teacherForm.errors.password_confirmation"
                                        class="mt-1 text-xs text-rose-600 dark:text-rose-400"
                                    >
                                        {{ teacherForm.errors.password_confirmation }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="rounded-xl border border-slate-200/80 bg-white/60 p-4 dark:border-slate-600/60 dark:bg-slate-900/30"
                                :class="{ 'ring-2 ring-rose-400/70': teacherForm.errors.terms_accepted }"
                            >
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input
                                        v-model="teacherForm.terms_accepted"
                                        type="checkbox"
                                        value="1"
                                        class="mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800"
                                    />
                                    <span class="text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                                        {{ tr.terms_agree_prefix }}
                                        <Link
                                            :href="routes.legal_privacy"
                                            class="font-semibold text-emerald-700 underline decoration-emerald-700/30 underline-offset-2 hover:text-emerald-800 dark:text-emerald-400 dark:decoration-emerald-400/30 dark:hover:text-emerald-300"
                                            >{{ tr.terms_link_label }}</Link
                                        >{{ tr.terms_agree_suffix }}
                                    </span>
                                </label>
                                <p
                                    v-if="teacherForm.errors.terms_accepted"
                                    class="mt-2 text-xs text-rose-600 dark:text-rose-400"
                                >
                                    {{ teacherForm.errors.terms_accepted }}
                                </p>
                            </div>

                            <button type="submit" class="site-btn-primary text-xs" :disabled="teacherForm.processing">
                                {{ tr.submit_teacher }}
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </SiteLayout>
</template>
