@php
    $locale = app()->getLocale();
    $isEn = $locale === 'en';
    $htmlLang = str_replace('_', '-', $locale);
    $htmlDir = $isEn ? 'ltr' : 'rtl';
    $mainPad = $isEn ? 'lg:pl-64' : 'lg:pr-64';
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLang }}" dir="{{ $htmlDir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('admin.layout.default_title')) — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/admin.js'])
    @else
        <style>
            body { font-family: "Instrument Sans", system-ui, sans-serif; background: #0f1412; color: #e7eee9; }
        </style>
    @endif
</head>
<body data-admin-shell data-admin-layout-dir="{{ $htmlDir }}" class="admin-shell min-h-screen text-[#e7eee9] antialiased">
    <div class="admin-ambient" aria-hidden="true">
        <div class="admin-ambient__orb admin-ambient__orb--a"></div>
        <div class="admin-ambient__orb admin-ambient__orb--b"></div>
        <div class="admin-ambient__orb admin-ambient__orb--c"></div>
        <div class="admin-ambient__grid"></div>
    </div>

    <div class="flex min-h-screen">
        @include('admin.partials.sidebar')

        <div class="flex min-h-screen flex-1 flex-col {{ $mainPad }}">
            <header data-admin-header class="admin-header sticky top-0 z-20 border-b border-white/10 bg-[#0c110f]/85 backdrop-blur-md">
                <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                    <div class="flex items-center gap-3">
                        <button type="button" data-admin-nav-toggle class="admin-btn inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 p-2 text-[#e7eee9] lg:hidden" aria-label="{{ __('admin.layout.menu') }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        @php
                            $adminHeaderLogo = trim((string) ($site['site_logo_url'] ?? ''));
                            $adminHeaderTitle = trim((string) ($site['site_name_resolved'] ?? '')) !== '' ? $site['site_name_resolved'] : ($site['site_name'] ?? config('app.name'));
                        @endphp
                        @if ($adminHeaderLogo !== '')
                            <img src="{{ $adminHeaderLogo }}" alt="{{ $adminHeaderTitle }}" class="h-8 w-8 shrink-0 rounded-xl border border-white/10 bg-white/[0.06] object-contain p-0.5 sm:h-9 sm:w-9" width="36" height="36" decoding="async" />
                        @endif
                        <div class="min-w-0">
                            <h1 class="text-sm font-semibold text-white sm:text-base">@yield('heading', __('admin.layout.default_title'))</h1>
                            <p class="mt-0.5 text-xs text-white/55 hidden sm:block">@yield('subheading')</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2 text-xs sm:text-sm sm:gap-3">
                        <div class="flex items-center gap-1 rounded-xl border border-white/10 bg-white/5 px-1 py-0.5" role="group" aria-label="{{ __('admin.layout.language') }}">
                            <a href="{{ route('locale.switch', ['locale' => 'ar']) }}" class="rounded-lg px-2 py-1 font-medium {{ $locale === 'ar' ? 'bg-emerald-500/25 text-emerald-100' : 'text-white/70 hover:bg-white/10' }}">{{ __('admin.layout.lang_ar') }}</a>
                            <a href="{{ route('locale.switch', ['locale' => 'en']) }}" class="rounded-lg px-2 py-1 font-medium {{ $locale === 'en' ? 'bg-emerald-500/25 text-emerald-100' : 'text-white/70 hover:bg-white/10' }}">{{ __('admin.layout.lang_en') }}</a>
                            <a href="{{ route('locale.switch', ['locale' => 'auto']) }}" class="rounded-lg px-2 py-1 text-white/55 hover:bg-white/10" title="{{ __('admin.layout.lang_auto') }}">A</a>
                        </div>
                        <span class="hidden sm:inline text-white/70">{{ auth()->user()->name }}</span>
                        <form method="post" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="admin-btn rounded-xl border border-white/10 bg-white/5 px-3 py-1.5 font-medium text-white/90 hover:bg-white/10">
                                {{ __('admin.layout.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 sm:px-6">
                @if (session('status'))
                    <div class="admin-fade-up is-visible mb-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="admin-fade-up is-visible mb-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="admin-content">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <div data-admin-overlay class="admin-overlay fixed inset-0 z-30 hidden bg-black/60 backdrop-blur-[2px] lg:hidden"></div>
</body>
</html>
