<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}">
@php
    $fixedBgUrl = $site['site_background_fixed_resolved'] ?? '';
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    @include('partials.seo')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|tajawal:400,500,600,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @else
        <style>
            body { font-family: "Tajawal", "Inter", system-ui, -apple-system, "Segoe UI", Arial, sans-serif; background: #fafaf9; }
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
    <style>
        /* Bootloader: site logo + white comet orbiting until window load */
        body.site-bootloader-active { overflow: hidden; }
        .site-bootloader {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
            transition: opacity 0.45s ease, visibility 0.45s ease;
            visibility: visible;
        }
        .site-bootloader--out {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        .site-bootloader__veil {
            position: absolute;
            inset: 0;
            background: color-mix(in oklab, #0f172a 92%, transparent);
        }
        html:not(.dark) .site-bootloader__veil {
            background: color-mix(in oklab, #fafaf9 94%, #0c1222 6%);
        }
        .site-bootloader__stage {
            position: relative;
            width: 10rem;
            height: 10rem;
            z-index: 1;
        }
        .site-bootloader__logo {
            position: absolute;
            left: 50%;
            top: 50%;
            z-index: 2;
            width: 4.5rem;
            height: 4.5rem;
            transform: translate(-50%, -50%);
            display: grid;
            place-items: center;
            border-radius: 1.15rem;
            border: 1px solid color-mix(in oklab, #fff 18%, transparent);
            background: color-mix(in oklab, #fff 8%, transparent);
            box-shadow:
                0 0 0 1px color-mix(in oklab, #fff 6%, transparent),
                0 12px 40px color-mix(in oklab, #000 35%, transparent);
        }
        html:not(.dark) .site-bootloader__logo {
            border-color: color-mix(in oklab, #0f172a 12%, transparent);
            background: color-mix(in oklab, #fff 85%, transparent);
            box-shadow:
                0 0 0 1px color-mix(in oklab, #0f172a 6%, transparent),
                0 12px 32px color-mix(in oklab, #0f172a 12%, transparent);
        }
        .site-bootloader__logo img {
            width: 3.25rem;
            height: 3.25rem;
            object-fit: contain;
        }
        .site-bootloader__logo-fallback {
            width: 2rem;
            height: 2rem;
            color: #38bdf8;
        }
        html:not(.dark) .site-bootloader__logo-fallback {
            color: #059669;
        }
        .site-bootloader__orbit {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 9.25rem;
            height: 9.25rem;
            margin: -4.625rem 0 0 -4.625rem;
            animation: site-bootloader-spin 1.15s linear infinite;
        }
        @keyframes site-bootloader-spin {
            to { transform: rotate(360deg); }
        }
        .site-bootloader__comet {
            position: absolute;
            top: 0;
            left: 50%;
            display: flex;
            flex-direction: row;
            align-items: center;
            transform: translateX(-50%);
        }
        .site-bootloader__comet-head {
            flex-shrink: 0;
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background: #fff;
            box-shadow:
                0 0 10px 3px rgba(255, 255, 255, 0.75),
                0 0 28px 8px rgba(255, 255, 255, 0.35);
        }
        .site-bootloader__comet-trail {
            width: 2.75rem;
            height: 3px;
            margin-inline-end: -1px;
            border-radius: 2px;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0),
                rgba(255, 255, 255, 0.55) 40%,
                rgba(255, 255, 255, 0.98)
            );
        }
        @media (prefers-reduced-motion: reduce) {
            .site-bootloader__orbit { animation-duration: 2.4s; }
        }
    </style>
    @stack('head')
</head>
<body class="site-bootloader-active soft-bg font-sans text-stone-800 antialiased transition-colors duration-500 dark:text-slate-200 {{ $fixedBgUrl !== '' ? 'site-bg-fixed-layer' : '' }}"
      @if($fixedBgUrl !== '') style="--site-fixed-bg: url('{{ e($fixedBgUrl) }}');" @endif>
    @include('partials.site-bootloader')
    <div class="flex min-h-screen flex-col">
        @include('partials.site-navbar')
        <main class="@yield('main_class', 'flex-1')">
            @yield('content')
        </main>
        @include('partials.site-footer')
    </div>

    @include('partials.floating-whatsapp')
    @stack('scripts')
</body>
</html>
