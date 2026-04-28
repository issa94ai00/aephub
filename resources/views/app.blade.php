<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}">
@php
    $site = app(\App\Services\SiteSettingsService::class)->all();
    $fixedBgUrl = $site['site_background_fixed_resolved'] ?? '';
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fafaf9" id="meta-theme-color">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#fafaf9" id="meta-theme-color">
    <script>
        (function () {
            try {
                var k = 'lms-theme';
                var v = localStorage.getItem(k);
                var dark;
                if (v === 'dark') {
                    dark = true;
                } else if (v === 'light') {
                    dark = false;
                } else {
                    dark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                }
                document.documentElement.classList.toggle('dark', dark);
                var meta = document.getElementById('meta-theme-color');
                if (meta) {
                    meta.setAttribute('content', dark ? '#0c1222' : '#fafaf9');
                }
            } catch (e) {}
        })();
    </script>
    @php
        $siteName = trim((string) ($site['site_name_resolved'] ?? '')) !== ''
            ? trim((string) $site['site_name_resolved'])
            : ($site['site_name'] ?? config('app.name'));
        $pageTitle = $siteName;
        $description = trim((string) ($site['seo_meta_description_resolved'] ?? ''));
        if ($description === '') {
            $description = __('site.seo.default_description');
        }
    @endphp
    <title inertia>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|tajawal:400,500,600,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/site.js'])
    @else
        <style>
            body { font-family: "Tajawal", "Inter", system-ui, sans-serif; background: #fafaf9; }
        </style>
    @endif
    @if($fixedBgUrl !== '')
        <style>
            body.site-bg-fixed-layer.soft-bg {
                background-image:
                    radial-gradient(52rem 40rem at 92% -8%, color-mix(in oklab, #6ee7b7 9%, transparent), transparent 58%),
                    radial-gradient(46rem 34rem at 4% 102%, color-mix(in oklab, #7dd3fc 10%, transparent), transparent 55%),
                    var(--site-fixed-bg) !important;
                background-color: #fafaf9;
                background-attachment: scroll, scroll, fixed;
                background-size: auto, auto, cover;
                background-position: center center, center center, center center;
                background-repeat: no-repeat, no-repeat, no-repeat;
            }
            html.dark body.site-bg-fixed-layer.soft-bg {
                background-image:
                    radial-gradient(52rem 40rem at 92% -8%, color-mix(in oklab, #34d399 16%, transparent), transparent 58%),
                    radial-gradient(46rem 34rem at 4% 102%, color-mix(in oklab, #38bdf8 12%, transparent), transparent 55%),
                    var(--site-fixed-bg) !important;
                background-color: #0c1222;
            }
        </style>
    @endif
    @inertiaHead
</head>
<body class="soft-bg font-sans text-stone-800 antialiased transition-colors duration-500 dark:text-slate-200 {{ $fixedBgUrl !== '' ? 'site-bg-fixed-layer' : '' }}"
      @if($fixedBgUrl !== '') style="--site-fixed-bg: url('{{ e($fixedBgUrl) }}');" @endif>
    @inertia
</body>
</html>
