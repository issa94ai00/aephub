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
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ __('admin.layout.default_title') }} — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/admin-spa.js'])
    @else
        <style>
            body { font-family: "Instrument Sans", system-ui, sans-serif; background: #0f1412; color: #e7eee9; }
        </style>
    @endif
    @inertiaHead
</head>
<body data-admin-shell data-admin-layout-dir="{{ $htmlDir }}" class="admin-shell min-h-screen text-[#e7eee9] antialiased">
    @inertia
</body>
</html>
