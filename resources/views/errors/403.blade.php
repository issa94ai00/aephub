<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>غير مصرح — {{ config('app.name') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif
</head>
<body class="min-h-screen bg-[#0c110f] text-[#e7eee9] flex items-center justify-center px-4">
    <div class="max-w-md text-center">
        <p class="text-sm text-white/50">403</p>
        <h1 class="mt-2 text-xl font-bold text-white">غير مصرح لك بالوصول</h1>
        <p class="mt-3 text-sm text-white/60">هذه الصفحة مخصصة لمدير النظام فقط.</p>
        <div class="mt-6 flex flex-wrap justify-center gap-3 text-sm">
            <a href="{{ url('/') }}" class="rounded-xl border border-white/15 px-4 py-2 text-white/85 hover:bg-white/5">الصفحة الرئيسية</a>
            <a href="{{ route('admin.login') }}" class="rounded-xl bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-500">دخول المدير</a>
        </div>
    </div>

    @include('partials.floating-whatsapp')
</body>
</html>
