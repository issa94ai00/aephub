@php
    $locale = app()->getLocale();
    $isEn = $locale === 'en';
    $htmlLang = str_replace('_', '-', $locale);
    $htmlDir = $isEn ? 'ltr' : 'rtl';
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLang }}" dir="{{ $htmlDir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('admin.login.title') }} — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/admin.js'])
    @endif
</head>
<body data-admin-login data-admin-layout-dir="{{ $htmlDir }}" class="admin-shell admin-login-page min-h-screen text-[#e7eee9] antialiased">
    <div class="admin-ambient" aria-hidden="true">
        <div class="admin-ambient__orb admin-ambient__orb--a"></div>
        <div class="admin-ambient__orb admin-ambient__orb--b"></div>
        <div class="admin-ambient__orb admin-ambient__orb--c"></div>
        <div class="admin-ambient__grid"></div>
    </div>

    <div class="relative z-10 mx-auto flex min-h-screen max-w-lg flex-col justify-center px-4 py-10">
        <div class="mb-6 flex flex-wrap items-center justify-center gap-2 text-xs">
            <a href="{{ route('locale.switch', ['locale' => 'ar']) }}" class="rounded-lg border border-white/10 px-3 py-1 font-medium {{ $locale === 'ar' ? 'bg-emerald-500/20 text-emerald-100' : 'text-white/70 hover:bg-white/5' }}">{{ __('admin.layout.lang_ar') }}</a>
            <a href="{{ route('locale.switch', ['locale' => 'en']) }}" class="rounded-lg border border-white/10 px-3 py-1 font-medium {{ $locale === 'en' ? 'bg-emerald-500/20 text-emerald-100' : 'text-white/70 hover:bg-white/5' }}">{{ __('admin.layout.lang_en') }}</a>
            <a href="{{ route('locale.switch', ['locale' => 'auto']) }}" class="rounded-lg border border-white/10 px-2 py-1 text-white/50 hover:bg-white/5" title="{{ __('admin.layout.lang_auto') }}">A</a>
        </div>

        <div class="admin-login-brand mb-8 text-center">
            <div class="admin-brand-icon mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-emerald-500/15 ring-1 ring-emerald-400/30">
                <svg viewBox="0 0 24 24" class="h-6 w-6 text-emerald-300" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 3L2 9l10 6 10-6-10-6Z" stroke="currentColor" stroke-width="1.7" />
                    <path d="M2 9v8l10 6 10-6V9" stroke="currentColor" stroke-width="1.7" opacity=".85"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-white">{{ __('admin.login.heading') }}</h1>
            <p class="mt-2 text-sm text-white/55">{{ __('admin.login.lead') }}</p>
        </div>

        <div class="admin-login-card rounded-3xl border border-white/10 bg-white/[0.03] p-6 shadow-xl ring-1 ring-white/5 backdrop-blur-md sm:p-8">
            @if ($errors->any())
                <div class="mb-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ route('admin.login.submit') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-medium text-white/70">{{ __('admin.login.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"
                           class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d]/80 px-3 py-2.5 text-sm text-white outline-none transition focus:border-emerald-400/45 focus:ring-2 focus:ring-emerald-400/20" />
                </div>
                <div>
                    <label for="password" class="block text-xs font-medium text-white/70">{{ __('admin.login.password') }}</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d]/80 px-3 py-2.5 text-sm text-white outline-none transition focus:border-emerald-400/45 focus:ring-2 focus:ring-emerald-400/20" />
                </div>
                <label class="flex items-center gap-2 text-xs text-white/60">
                    <input type="checkbox" name="remember" value="1" class="rounded border-white/20 bg-[#0a0f0d] text-emerald-500 focus:ring-emerald-400/40" />
                    {{ __('admin.login.remember') }}
                </label>
                <button type="submit" class="admin-btn w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-900/35 hover:bg-emerald-500">
                    {{ __('admin.login.submit') }}
                </button>
            </form>
        </div>

        <p class="mt-8 text-center text-xs text-white/40">
            <a href="{{ url('/') }}" class="transition hover:text-emerald-200">{{ __('admin.login.back_home') }}</a>
        </p>
    </div>
</body>
</html>
