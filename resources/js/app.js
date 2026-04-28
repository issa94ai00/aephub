import './bootstrap';
import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('registrationAcademicPicker', (config) => ({
        apiBase: config.apiBase || '/api/v1',
        hasUniversities: Boolean(config.hasUniversities),
        fetchErrorLabel: config.fetchErrorLabel || '',
        labels: config.labels || {},
        universityId: '',
        facultyId: '',
        studyYearId: '',
        studyTermId: '',
        facultyOptions: [],
        yearOptions: [],
        termOptions: [],
        loadingFaculties: false,
        loadingYears: false,
        loadingTerms: false,
        fetchError: '',
        initialStudyTermId: config.initialStudyTermId || null,

        apiFullUrl(pathWithLeadingSlash) {
            const suffix = pathWithLeadingSlash.startsWith('/') ? pathWithLeadingSlash : `/${pathWithLeadingSlash}`;
            const base = String(this.apiBase || '/api/v1').trim().replace(/\/$/, '');
            if (base.startsWith('http://') || base.startsWith('https://')) {
                try {
                    const u = new URL(base);
                    const pathPrefix = (u.pathname || '').replace(/\/$/, '');
                    if (u.origin === window.location.origin) {
                        return `${u.origin}${pathPrefix}${suffix}`;
                    }
                    return `${window.location.origin}${pathPrefix}${suffix}`;
                } catch (e) {
                    return `${window.location.origin}/api/v1${suffix}`;
                }
            }
            const rel = base.startsWith('/') ? base : `/${base}`;
            return `${window.location.origin}${rel}${suffix}`;
        },

        /**
         * HTML forbids <template> inside <select>; rebuild <option> nodes when lists change.
         */
        patchSelectFromModel(el, items, placeholderLabel, modelKey) {
            if (!el) {
                return;
            }
            const list = Array.isArray(items) ? items : [];
            const current = String(this[modelKey] ?? '');
            const label = placeholderLabel || '';
            el.replaceChildren();
            const ph = document.createElement('option');
            ph.value = '';
            ph.textContent = label;
            el.appendChild(ph);
            for (const item of list) {
                const o = document.createElement('option');
                o.value = String(item.id);
                o.textContent = item.localized_name ?? String(item.id);
                el.appendChild(o);
            }
            const ok = current !== '' && [...el.options].some((o) => o.value === current);
            this.$nextTick(() => {
                if (ok) {
                    el.value = current;
                } else {
                    el.value = '';
                    if (current !== '') {
                        this[modelKey] = '';
                    }
                }
            });
        },

        academicSubmitBlocked() {
            return (
                !this.hasUniversities ||
                this.studyTermId === '' ||
                this.studyTermId === null ||
                this.loadingFaculties ||
                this.loadingYears ||
                this.loadingTerms
            );
        },

        async init() {
            if (this.initialStudyTermId) {
                await this.restoreSelection(String(this.initialStudyTermId));
            }
        },

        async restoreSelection(termId) {
            try {
                const ctxRes = await fetch(
                    `${this.apiFullUrl('/academics/study-term-context')}?study_term_id=${encodeURIComponent(termId)}`,
                    {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    },
                );
                if (!ctxRes.ok) {
                    return;
                }
                const ctx = await ctxRes.json();
                this.universityId = String(ctx.university_id);
                await this.loadFaculties(false);
                this.facultyId = String(ctx.faculty_id);
                await this.loadYears(false);
                this.studyYearId = String(ctx.study_year_id);
                await this.loadTerms(false);
                this.studyTermId = String(ctx.study_term_id);
            } catch (e) {
                this.fetchError = this.fetchErrorLabel;
            }
        },

        async loadFaculties(resetIds = true) {
            this.fetchError = '';
            if (resetIds) {
                this.facultyId = '';
                this.studyYearId = '';
                this.studyTermId = '';
            }
            this.yearOptions = [];
            this.termOptions = [];
            if (!this.universityId) {
                this.facultyOptions = [];
                return;
            }
            this.loadingFaculties = true;
            try {
                const r = await fetch(
                    `${this.apiFullUrl('/academics/faculties')}?university_id=${encodeURIComponent(this.universityId)}`,
                    {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    },
                );
                if (!r.ok) {
                    this.fetchError = this.fetchErrorLabel;
                    this.facultyOptions = [];
                    return;
                }
                const d = await r.json();
                this.facultyOptions = Array.isArray(d.faculties) ? d.faculties : [];
            } catch (e) {
                this.fetchError = this.fetchErrorLabel;
            } finally {
                this.loadingFaculties = false;
            }
        },

        async loadYears(resetIds = true) {
            this.fetchError = '';
            if (resetIds) {
                this.studyYearId = '';
                this.studyTermId = '';
            }
            this.termOptions = [];
            if (!this.facultyId) {
                this.yearOptions = [];
                return;
            }
            this.loadingYears = true;
            try {
                const r = await fetch(
                    `${this.apiFullUrl('/academics/study-years')}?faculty_id=${encodeURIComponent(this.facultyId)}`,
                    {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    },
                );
                if (!r.ok) {
                    this.fetchError = this.fetchErrorLabel;
                    this.yearOptions = [];
                    return;
                }
                const d = await r.json();
                this.yearOptions = Array.isArray(d.study_years) ? d.study_years : [];
            } catch (e) {
                this.fetchError = this.fetchErrorLabel;
            } finally {
                this.loadingYears = false;
            }
        },

        async loadTerms(resetIds = true) {
            this.fetchError = '';
            if (resetIds) {
                this.studyTermId = '';
            }
            if (!this.studyYearId) {
                this.termOptions = [];
                return;
            }
            this.loadingTerms = true;
            try {
                const r = await fetch(
                    `${this.apiFullUrl('/academics/study-terms')}?study_year_id=${encodeURIComponent(this.studyYearId)}`,
                    {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    },
                );
                if (!r.ok) {
                    this.fetchError = this.fetchErrorLabel;
                    this.termOptions = [];
                    return;
                }
                const d = await r.json();
                this.termOptions = Array.isArray(d.study_terms) ? d.study_terms : [];
            } catch (e) {
                this.fetchError = this.fetchErrorLabel;
            } finally {
                this.loadingTerms = false;
            }
        },
    }));
});

window.Alpine = Alpine;
Alpine.start();

const setupReveal = () => {
    const elements = Array.from(document.querySelectorAll('.reveal'));
    if (!elements.length) return;

    const observer = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        }
    }, { threshold: 0.12 });

    elements.forEach((el, index) => {
        el.style.transitionDelay = `${Math.min(index * 40, 240)}ms`;
        observer.observe(el);
    });
};

const setupNavbarBlur = () => {
    const nav = document.querySelector('[data-nav]');
    if (!nav) return;

    const onScroll = () => {
        if (window.scrollY > 12) {
            nav.classList.add('is-sticky');
        } else {
            nav.classList.remove('is-sticky');
        }
    };

    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
};

const setupHeroCarousel = () => {
    const root = document.querySelector('[data-hero-carousel]');
    const track = root?.querySelector('[data-carousel-track]');
    const slides = root ? Array.from(root.querySelectorAll('[data-carousel-slide]')) : [];
    const prevBtn = root?.querySelector('[data-carousel-prev]');
    const nextBtn = root?.querySelector('[data-carousel-next]');
    const dotsWrap = root?.querySelector('[data-carousel-dots]');

    if (!root || !track || slides.length === 0) return;

    let index = 0;
    const total = slides.length;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const dotLabelTpl = root.getAttribute('data-carousel-dot-template') || 'Slide __NUM__';

    const dots = dotsWrap
        ? slides.map((_, i) => {
              const b = document.createElement('button');
              b.type = 'button';
              b.className = 'hero-carousel__dot' + (i === 0 ? ' is-active' : '');
              b.setAttribute('aria-label', dotLabelTpl.replace(/__NUM__/g, String(i + 1)));
              b.addEventListener('click', () => go(i));
              dotsWrap.appendChild(b);
              return b;
          })
        : [];

    const apply = () => {
        track.style.transform = `translateX(-${index * 100}%)`;
        dots.forEach((d, i) => d.classList.toggle('is-active', i === index));
    };

    const go = (i) => {
        index = ((i % total) + total) % total;
        apply();
    };

    const next = () => go(index + 1);
    const prev = () => go(index - 1);

    prevBtn?.addEventListener('click', prev);
    nextBtn?.addEventListener('click', next);

    let timer = null;
    const start = () => {
        if (reduceMotion || total < 2) return;
        stop();
        timer = window.setInterval(next, 5500);
    };
    const stop = () => {
        if (timer) window.clearInterval(timer);
        timer = null;
    };

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', start);

    start();
};

const setupAdminMobileNav = () => {
    const btn = document.querySelector('[data-admin-nav-toggle]');
    const sidebar = document.querySelector('[data-admin-sidebar]');
    const overlay = document.querySelector('[data-admin-overlay]');
    if (!btn || !sidebar || !overlay) {
        return;
    }

    const dir = sidebar.dataset.sidebarDir === 'ltr' ? 'ltr' : 'rtl';
    const hiddenClass = dir === 'ltr' ? '-translate-x-full' : 'translate-x-full';
    const visibleClass = 'translate-x-0';

    const open = () => {
        sidebar.classList.remove(hiddenClass);
        sidebar.classList.add(visibleClass);
        overlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const close = () => {
        sidebar.classList.add(hiddenClass);
        sidebar.classList.remove(visibleClass);
        overlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    btn.addEventListener('click', () => {
        if (sidebar.classList.contains(hiddenClass)) {
            open();
        } else {
            close();
        }
    });

    overlay.addEventListener('click', close);

    window.addEventListener(
        'keydown',
        (e) => {
            if (e.key === 'Escape') {
                close();
            }
        },
        { passive: true }
    );
};

const setupAdminScrollHeader = () => {
    const header = document.querySelector('[data-admin-header]');
    if (!header) {
        return;
    }

    const onScroll = () => {
        if (window.scrollY > 10) {
            header.classList.add('is-scrolled');
        } else {
            header.classList.remove('is-scrolled');
        }
    };

    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
};

const setupAdminContentReveal = () => {
    const shell = document.querySelector('[data-admin-shell]');
    const container = shell?.querySelector('.admin-content');
    if (!container) {
        return;
    }

    const children = Array.from(container.children);
    if (!children.length) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduceMotion) {
        children.forEach((el) => {
            el.classList.add('admin-fade-up', 'is-visible');
        });
        return;
    }

    children.forEach((el) => {
        el.classList.add('admin-fade-up');
    });

    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting || entry.target !== container) {
                    return;
                }
                io.disconnect();
                children.forEach((el, i) => {
                    window.setTimeout(() => {
                        el.classList.add('is-visible');
                    }, i * 48);
                });
            });
        },
        { threshold: 0.04, rootMargin: '0px 0px -12px 0px' }
    );

    io.observe(container);
};

const THEME_STORAGE_KEY = 'lms-theme';

const applySiteTheme = (mode) => {
    const dark = mode === 'dark';
    document.documentElement.classList.toggle('dark', dark);
    try {
        localStorage.setItem(THEME_STORAGE_KEY, mode);
    } catch {
        //
    }
    const meta = document.getElementById('meta-theme-color');
    if (meta) {
        meta.setAttribute('content', dark ? '#0c1222' : '#fafaf9');
    }
};

const syncSiteThemeToggle = () => {
    const btn = document.querySelector('[data-site-theme-toggle]');
    if (!btn) {
        return;
    }
    const isDark = document.documentElement.classList.contains('dark');
    const lang = (document.documentElement.getAttribute('lang') || '').toLowerCase();
    const isEn = lang.startsWith('en');
    btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
    btn.setAttribute(
        'title',
        isDark ? (isEn ? 'Light mode' : 'الوضع الفاتح') : isEn ? 'Dark mode' : 'الوضع الداكن'
    );
    btn.setAttribute('aria-label', isEn ? 'Toggle dark mode' : 'تبديل الوضع الداكن');
};

const setupSiteTheme = () => {
    const btn = document.querySelector('[data-site-theme-toggle]');
    if (!btn) {
        return;
    }

    btn.addEventListener('click', () => {
        const next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
        applySiteTheme(next);
        syncSiteThemeToggle();
    });

    syncSiteThemeToggle();
};

const setupAdminLoginReady = () => {
    const root = document.querySelector('[data-admin-login]');
    if (!root) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion) {
        root.classList.add('is-ready');
        return;
    }

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            root.classList.add('is-ready');
        });
    });
};

document.addEventListener('DOMContentLoaded', () => {
    setupSiteTheme();
    setupReveal();
    setupNavbarBlur();
    setupHeroCarousel();
    setupAdminMobileNav();
    setupAdminScrollHeader();
    setupAdminContentReveal();
    setupAdminLoginReady();
});
