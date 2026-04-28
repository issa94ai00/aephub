@php
    $isHome = request()->is('/');
    $coursesHref = $isHome ? '#courses' : url('/#courses');
    $universitiesHref = $isHome ? '#universities' : url('/#universities');
    $whyHref = $isHome ? '#why' : url('/#why');
    $faqHref = $isHome ? '#faq' : route('faq');
    $localeCookie = request()->cookie(config('locale.cookie', 'site_locale'));
    $localeArActive = $localeCookie === 'ar';
    $localeEnActive = $localeCookie === 'en';

    $segOn = 'bg-gradient-to-b from-amber-500 to-orange-600 text-white shadow-md shadow-orange-900/25 dark:from-amber-500 dark:to-orange-600';
    $segOff = 'text-slate-600 hover:bg-slate-100/90 dark:text-slate-300 dark:hover:bg-slate-800/80';
@endphp

<header
    class="sticky top-0 z-40"
    x-data="{ mobileOpen: false }"
    @keydown.escape.window="mobileOpen = false"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div data-nav class="top-nav relative mt-3 flex flex-wrap items-center justify-between gap-3 py-3 sm:py-4">
            <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-3 rounded-2xl outline-none ring-amber-500/0 transition hover:ring-2 focus-visible:ring-amber-500/35">
                <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-2xl border border-slate-200/80 bg-white/95 shadow-md shadow-slate-900/5 ring-1 ring-white/80 backdrop-blur-md dark:border-slate-600 dark:bg-slate-800/95 dark:shadow-black/20 dark:ring-slate-700/60">
                    @if(!empty($site['site_logo_url'] ?? ''))
                        <img src="{{ $site['site_logo_url'] }}" alt="{{ trim($site['site_name_resolved'] ?? '') !== '' ? $site['site_name_resolved'] : ($site['site_name'] ?? config('app.name')) }}" class="h-full w-full object-contain p-1" width="44" height="44" decoding="async" />
                    @else
                        <svg viewBox="0 0 24 24" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M12 3L2 9l10 6 10-6-10-6Z" stroke="currentColor" stroke-width="1.7" />
                            <path d="M2 9v8l10 6 10-6V9" stroke="currentColor" stroke-width="1.7" opacity=".85"/>
                        </svg>
                    @endif
                </span>
                <div class="leading-tight">
                    <div class="text-sm font-bold tracking-tight text-slate-900 dark:text-slate-50">{{ trim($site['site_name_resolved'] ?? '') !== '' ? $site['site_name_resolved'] : ($site['site_name'] ?? config('app.name')) }}</div>
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('site.tagline') }}</div>
                </div>
            </a>

            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 sm:gap-3">
                <nav class="hidden items-center gap-1 md:flex lg:gap-2" aria-label="{{ __('site.nav.main') }}">
                    <a href="{{ $coursesHref }}" class="site-nav-link rounded-lg px-3 py-2">{{ __('site.nav.courses') }}</a>
                    <a href="{{ $universitiesHref }}" class="site-nav-link rounded-lg px-3 py-2">{{ __('site.nav.universities') }}</a>
                    <a href="{{ $whyHref }}" class="site-nav-link rounded-lg px-3 py-2">{{ __('site.nav.why') }}</a>
                    <a href="{{ $faqHref }}" class="site-nav-link rounded-lg px-3 py-2">{{ __('site.nav.faq') }}</a>
                    <a href="{{ route('subscription.register') }}" class="site-nav-link rounded-lg px-3 py-2">{{ __('site.nav.subscribe') }}</a>
                    <a href="{{ route('android.download') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-sky-700 transition duration-300 hover:text-sky-900 dark:text-sky-400 dark:hover:text-sky-300">
                        {{ __('site.nav.download_app') }}
                    </a>
                </nav>

                <div
                    class="hidden items-center rounded-xl border border-slate-200/80 bg-white/80 p-0.5 shadow-sm ring-1 ring-slate-200/60 dark:border-slate-600 dark:bg-slate-800/80 dark:ring-slate-600/60 md:flex"
                    role="group"
                    aria-label="{{ __('site.locale.switch') }}"
                >
                    <a
                        href="{{ route('locale.switch', ['locale' => 'ar']) }}"
                        class="rounded-lg px-2 py-1.5 text-[11px] font-bold transition {{ $localeArActive ? $segOn : $segOff }}"
                    >{{ __('site.locale.ar') }}</a>
                    <a
                        href="{{ route('locale.switch', ['locale' => 'en']) }}"
                        class="rounded-lg px-2 py-1.5 text-[11px] font-bold transition {{ $localeEnActive ? $segOn : $segOff }}"
                    >{{ __('site.locale.en') }}</a>
                </div>

                <div
                    class="flex items-center rounded-xl border border-slate-200/80 bg-white/80 p-0.5 shadow-sm ring-1 ring-slate-200/60 dark:border-slate-600 dark:bg-slate-800/80 dark:ring-slate-600/60 md:hidden"
                    role="group"
                    aria-label="{{ __('site.locale.switch') }}"
                >
                    <a
                        href="{{ route('locale.switch', ['locale' => 'ar']) }}"
                        class="rounded-lg px-1.5 py-1 text-[10px] font-bold leading-tight transition sm:px-2 sm:text-[11px] {{ $localeArActive ? $segOn : $segOff }}"
                    >{{ __('site.locale.ar') }}</a>
                    <a
                        href="{{ route('locale.switch', ['locale' => 'en']) }}"
                        class="rounded-lg px-1.5 py-1 text-[10px] font-bold leading-tight transition sm:px-2 sm:text-[11px] {{ $localeEnActive ? $segOn : $segOff }}"
                    >{{ __('site.locale.en') }}</a>
                </div>

                <a href="{{ route('android.download') }}" class="site-btn-secondary hidden px-3 py-2 text-xs sm:inline-flex">
                    {{ __('site.nav.download_apk') }}
                </a>

                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200/90 bg-white/90 text-slate-700 shadow-sm ring-1 ring-slate-200/70 transition duration-300 hover:bg-slate-50 hover:shadow-md md:hidden dark:border-slate-600 dark:bg-slate-800/90 dark:text-slate-100 dark:ring-slate-600 dark:hover:bg-slate-800"
                    @click="mobileOpen = !mobileOpen"
                    :aria-expanded="mobileOpen"
                    aria-controls="site-mobile-nav"
                    aria-label="{{ __('site.nav.open_menu') }}"
                >
                    <svg x-show="!mobileOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-cloak x-show="mobileOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <button
                    type="button"
                    data-site-theme-toggle
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200/90 bg-white/90 text-slate-700 shadow-sm ring-1 ring-slate-200/70 transition duration-300 hover:bg-slate-50 hover:shadow-md dark:border-slate-600 dark:bg-slate-800/90 dark:text-amber-200 dark:ring-slate-600 dark:hover:bg-slate-800"
                    aria-label="{{ __('site.theme.toggle') }}"
                    aria-pressed="false"
                >
                    <span class="dark:hidden" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </span>
                    <span class="hidden dark:inline" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </span>
                </button>
            </div>

            <div
                id="site-mobile-nav"
                x-cloak
                x-show="mobileOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                @click.outside="mobileOpen = false"
                class="absolute inset-x-0 top-full z-50 pt-2 md:hidden"
                role="navigation"
                aria-label="{{ __('site.nav.mobile') }}"
            >
                <div class="space-y-1 rounded-2xl border border-slate-200/80 bg-white/95 p-3 shadow-2xl shadow-slate-900/15 ring-1 ring-white/80 backdrop-blur-xl dark:border-slate-700/70 dark:bg-slate-900/95 dark:shadow-black/40 dark:ring-slate-700/50">
                    <a href="{{ $coursesHref }}" @click="mobileOpen = false" class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-slate-100 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300">{{ __('site.nav.courses') }}</a>
                    <a href="{{ $universitiesHref }}" @click="mobileOpen = false" class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-slate-100 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300">{{ __('site.nav.universities') }}</a>
                    <a href="{{ $whyHref }}" @click="mobileOpen = false" class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-slate-100 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300">{{ __('site.nav.why') }}</a>
                    <a href="{{ $faqHref }}" @click="mobileOpen = false" class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-slate-100 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300">{{ __('site.nav.faq') }}</a>
                    <a href="{{ route('subscription.register') }}" @click="mobileOpen = false" class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-slate-100 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300">{{ __('site.nav.subscribe') }}</a>
                    <a href="{{ route('android.download') }}" @click="mobileOpen = false" class="block rounded-xl px-4 py-3 text-sm font-semibold text-sky-700 dark:text-sky-400">{{ __('site.nav.download_app') }}</a>

                    <div class="border-t border-slate-200/80 pt-2 dark:border-slate-700/60" role="group" aria-label="{{ __('site.locale.switch') }}">
                        <p class="px-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ __('site.locale.switch') }}</p>
                        <div class="flex gap-1 px-2">
                            <a href="{{ route('locale.switch', ['locale' => 'ar']) }}" @click="mobileOpen = false" class="flex-1 rounded-lg py-2 text-center text-xs font-bold transition {{ $localeArActive ? $segOn : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">{{ __('site.locale.ar') }}</a>
                            <a href="{{ route('locale.switch', ['locale' => 'en']) }}" @click="mobileOpen = false" class="flex-1 rounded-lg py-2 text-center text-xs font-bold transition {{ $localeEnActive ? $segOn : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">{{ __('site.locale.en') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
