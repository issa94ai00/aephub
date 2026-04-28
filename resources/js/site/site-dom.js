const THEME_STORAGE_KEY = 'lms-theme';

export const applySiteTheme = (mode) => {
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
        isDark ? (isEn ? 'Light mode' : 'الوضع الفاتح') : isEn ? 'Dark mode' : 'الوضع الداكن',
    );
    btn.setAttribute('aria-label', isEn ? 'Toggle dark mode' : 'تبديل الوضع الداكن');
};

const onSiteThemeClick = () => {
    const next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
    applySiteTheme(next);
    syncSiteThemeToggle();
};

export const setupSiteTheme = () => {
    const btn = document.querySelector('[data-site-theme-toggle]');
    if (!btn || btn.dataset.themeToggleBound === '1') {
        syncSiteThemeToggle();
        return;
    }
    btn.dataset.themeToggleBound = '1';
    btn.addEventListener('click', onSiteThemeClick);
    syncSiteThemeToggle();
};

export const setupReveal = () => {
    const elements = Array.from(document.querySelectorAll('.reveal'));
    if (!elements.length) {
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            }
        },
        { threshold: 0.12 },
    );

    elements.forEach((el, index) => {
        if (el.classList.contains('is-visible')) {
            return;
        }
        el.style.transitionDelay = `${Math.min(index * 40, 240)}ms`;
        observer.observe(el);
    });
};

const onWindowScrollNav = () => {
    const nav = document.querySelector('[data-nav]');
    if (!nav) {
        return;
    }
    if (window.scrollY > 12) {
        nav.classList.add('is-sticky');
    } else {
        nav.classList.remove('is-sticky');
    }
};

let navScrollListenerAttached = false;

export const setupNavbarBlur = () => {
    onWindowScrollNav();
    if (!navScrollListenerAttached) {
        window.addEventListener('scroll', onWindowScrollNav, { passive: true });
        navScrollListenerAttached = true;
    }
};

export const setupHeroCarousel = () => {
    const root = document.querySelector('[data-hero-carousel]');
    const track = root?.querySelector('[data-carousel-track]');
    const slides = root ? Array.from(root.querySelectorAll('[data-carousel-slide]')) : [];
    const prevBtn = root?.querySelector('[data-carousel-prev]');
    const nextBtn = root?.querySelector('[data-carousel-next]');
    const dotsWrap = root?.querySelector('[data-carousel-dots]');

    if (!root || !track || slides.length === 0) {
        return;
    }

    if (root.dataset.carouselBound === '1') {
        return;
    }
    root.dataset.carouselBound = '1';

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
    dots.forEach((d, i) => d.addEventListener('click', () => go(i)));

    let timer = null;
    const start = () => {
        if (reduceMotion || total < 2) {
            return;
        }
        stop();
        timer = window.setInterval(next, 5500);
    };
    const stop = () => {
        if (timer) {
            window.clearInterval(timer);
        }
        timer = null;
    };

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', start);

    apply();
    start();
};

export const scrollHashSection = () => {
    const raw = window.location.hash?.replace(/^#/, '') || '';
    if (!raw) {
        return;
    }
    window.requestAnimationFrame(() => {
        document.getElementById(raw)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
};

export const bootSiteDom = () => {
    window.requestAnimationFrame(() => {
        setupReveal();
        setupNavbarBlur();
        setupHeroCarousel();
        setupSiteTheme();
        scrollHashSection();
    });
};
