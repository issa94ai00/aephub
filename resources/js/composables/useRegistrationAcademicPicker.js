import { computed, onMounted, ref } from 'vue';

/**
 * @param {object} config
 */
export function useRegistrationAcademicPicker(config) {
    const apiBase = config.apiBase || '/api/v1';
    const hasUniversities = Boolean(config.hasUniversities);
    const fetchErrorLabel = config.fetchErrorLabel || '';
    const labels = config.labels || {};

    const universityId = ref('');
    const facultyId = ref('');
    const studyYearId = ref('');
    const studyTermId = ref('');
    const facultyOptions = ref([]);
    const yearOptions = ref([]);
    const termOptions = ref([]);
    const loadingFaculties = ref(false);
    const loadingYears = ref(false);
    const loadingTerms = ref(false);
    const fetchError = ref('');
    const initialStudyTermId = config.initialStudyTermId || null;

    function apiFullUrl(pathWithLeadingSlash) {
        const suffix = pathWithLeadingSlash.startsWith('/')
            ? pathWithLeadingSlash
            : `/${pathWithLeadingSlash}`;
        const base = String(apiBase || '/api/v1')
            .trim()
            .replace(/\/$/, '');
        if (base.startsWith('http://') || base.startsWith('https://')) {
            try {
                const u = new URL(base);
                const pathPrefix = (u.pathname || '').replace(/\/$/, '');
                if (u.origin === window.location.origin) {
                    return `${u.origin}${pathPrefix}${suffix}`;
                }
                return `${window.location.origin}${pathPrefix}${suffix}`;
            } catch {
                return `${window.location.origin}/api/v1${suffix}`;
            }
        }
        const rel = base.startsWith('/') ? base : `/${base}`;
        return `${window.location.origin}${rel}${suffix}`;
    }

    const academicSubmitBlocked = computed(
        () =>
            !hasUniversities ||
            studyTermId.value === '' ||
            studyTermId.value === null ||
            loadingFaculties.value ||
            loadingYears.value ||
            loadingTerms.value,
    );

    async function restoreSelection(termId) {
        try {
            const ctxRes = await fetch(
                `${apiFullUrl('/academics/study-term-context')}?study_term_id=${encodeURIComponent(termId)}`,
                {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                },
            );
            if (!ctxRes.ok) {
                return;
            }
            const ctx = await ctxRes.json();
            universityId.value = String(ctx.university_id);
            await loadFaculties(false);
            facultyId.value = String(ctx.faculty_id);
            await loadYears(false);
            studyYearId.value = String(ctx.study_year_id);
            await loadTerms(false);
            studyTermId.value = String(ctx.study_term_id);
        } catch {
            fetchError.value = fetchErrorLabel;
        }
    }

    async function loadFaculties(resetIds = true) {
        fetchError.value = '';
        if (resetIds) {
            facultyId.value = '';
            studyYearId.value = '';
            studyTermId.value = '';
        }
        yearOptions.value = [];
        termOptions.value = [];
        if (!universityId.value) {
            facultyOptions.value = [];
            return;
        }
        loadingFaculties.value = true;
        try {
            const r = await fetch(
                `${apiFullUrl('/academics/faculties')}?university_id=${encodeURIComponent(universityId.value)}`,
                {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                },
            );
            if (!r.ok) {
                fetchError.value = fetchErrorLabel;
                facultyOptions.value = [];
                return;
            }
            const d = await r.json();
            facultyOptions.value = Array.isArray(d.faculties) ? d.faculties : [];
        } catch {
            fetchError.value = fetchErrorLabel;
        } finally {
            loadingFaculties.value = false;
        }
    }

    async function loadYears(resetIds = true) {
        fetchError.value = '';
        if (resetIds) {
            studyYearId.value = '';
            studyTermId.value = '';
        }
        termOptions.value = [];
        if (!facultyId.value) {
            yearOptions.value = [];
            return;
        }
        loadingYears.value = true;
        try {
            const r = await fetch(
                `${apiFullUrl('/academics/study-years')}?faculty_id=${encodeURIComponent(facultyId.value)}`,
                {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                },
            );
            if (!r.ok) {
                fetchError.value = fetchErrorLabel;
                yearOptions.value = [];
                return;
            }
            const d = await r.json();
            yearOptions.value = Array.isArray(d.study_years) ? d.study_years : [];
        } catch {
            fetchError.value = fetchErrorLabel;
        } finally {
            loadingYears.value = false;
        }
    }

    async function loadTerms(resetIds = true) {
        fetchError.value = '';
        if (resetIds) {
            studyTermId.value = '';
        }
        if (!studyYearId.value) {
            termOptions.value = [];
            return;
        }
        loadingTerms.value = true;
        try {
            const r = await fetch(
                `${apiFullUrl('/academics/study-terms')}?study_year_id=${encodeURIComponent(studyYearId.value)}`,
                {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                },
            );
            if (!r.ok) {
                fetchError.value = fetchErrorLabel;
                termOptions.value = [];
                return;
            }
            const d = await r.json();
            termOptions.value = Array.isArray(d.study_terms) ? d.study_terms : [];
        } catch {
            fetchError.value = fetchErrorLabel;
        } finally {
            loadingTerms.value = false;
        }
    }

    onMounted(async () => {
        if (initialStudyTermId) {
            await restoreSelection(String(initialStudyTermId));
        }
    });

    return {
        hasUniversities,
        labels,
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
    };
}
