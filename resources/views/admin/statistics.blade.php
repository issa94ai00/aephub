@extends('admin.spa-inner')

@section('title', __('admin.statistics.title'))
@section('heading', __('admin.statistics.heading'))
@section('subheading', __('admin.statistics.subheading'))

@php
    function humanBytes($bytes) {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' ' . __('admin.statistics.bytes');
    }
    function humanCents($cents) {
        return number_format($cents / 100, 2) . ' SYP';
    }
@endphp

@section('content')
    {{-- Summary cards --}}
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="admin-card p-5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-500/20">
                    <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7zm0 0h16M9 12h6"/></svg>
                </div>
                <div>
                    <div class="text-xs text-white/55">{{ __('admin.statistics.total_file_size') }}</div>
                    <div class="mt-1 text-2xl font-bold text-white">{{ humanBytes($totalFileSize) }}</div>
                </div>
            </div>
        </div>
        <div class="admin-card p-5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/20">
                    <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 2v2m0 4v2m0 2c-1.11 0-2.08-.402-2.599-1"/></svg>
                </div>
                <div>
                    <div class="text-xs text-white/55">{{ __('admin.statistics.total_revenue') }}</div>
                    <div class="mt-1 text-2xl font-bold text-white">{{ humanCents($totalRevenue) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        {{-- File size chart --}}
        <section class="admin-card p-5">
            <h2 class="text-sm font-semibold text-white">{{ __('admin.statistics.file_size_chart') }}</h2>
            <div class="mt-4 space-y-3">
                @forelse ($topFiles as $c)
                    @php $maxBytes = $topFiles->first()['file_size_bytes'] ?: 1; $pct = ($c['file_size_bytes'] / $maxBytes) * 100; @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-white/80 truncate max-w-[70%]">{{ $c['title'] }}</span>
                            <span class="shrink-0 text-white/55">{{ humanBytes($c['file_size_bytes']) }}</span>
                        </div>
                        <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-white/10">
                            <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-blue-400 transition-all" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-white/55">{{ __('admin.statistics.no_data') }}</div>
                @endforelse
            </div>
        </section>

        {{-- Revenue chart --}}
        <section class="admin-card p-5">
            <h2 class="text-sm font-semibold text-white">{{ __('admin.statistics.revenue_chart') }}</h2>
            <div class="mt-4 space-y-3">
                @forelse ($topRevenue as $c)
                    @php $maxCents = $topRevenue->first()['revenue_cents'] ?: 1; $pct = ($c['revenue_cents'] / $maxCents) * 100; @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-white/80 truncate max-w-[70%]">{{ $c['title'] }}</span>
                            <span class="shrink-0 text-white/55">{{ humanCents($c['revenue_cents']) }}</span>
                        </div>
                        <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-white/10">
                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-white/55">{{ __('admin.statistics.no_data') }}</div>
                @endforelse
            </div>
        </section>
    </div>

    {{-- Detailed table --}}
    <section class="admin-card mt-8 overflow-hidden">
        <div class="p-5">
            <h2 class="text-sm font-semibold text-white">{{ __('admin.nav.statistics') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-white/80">
                <thead>
                    <tr class="border-b border-white/10 text-xs text-white/50">
                        <th class="px-5 py-3 text-start font-medium">#</th>
                        <th class="px-5 py-3 text-start font-medium">{{ __('admin.statistics.course') }}</th>
                        <th class="px-5 py-3 text-start font-medium">{{ __('admin.statistics.teacher') }}</th>
                        <th class="px-5 py-3 text-start font-medium">{{ __('admin.statistics.file_size') }}</th>
                        <th class="px-5 py-3 text-start font-medium">{{ __('admin.statistics.revenue') }}</th>
                        <th class="px-5 py-3 text-start font-medium">{{ __('admin.statistics.enrollments') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($courses as $i => $c)
                        <tr class="border-b border-white/5 hover:bg-white/[0.03]">
                            <td class="px-5 py-3 text-white/40">{{ $i + 1 }}</td>
                            <td class="px-5 py-3 font-medium text-white">{{ $c['title'] }}</td>
                            <td class="px-5 py-3 text-white/55">{{ $c['teacher'] ?? '—' }}</td>
                            <td class="px-5 py-3">
                                @if($c['file_size_bytes'] > 0)
                                    <span class="text-blue-300">{{ humanBytes($c['file_size_bytes']) }}</span>
                                @else
                                    <span class="text-white/30">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if($c['revenue_cents'] > 0)
                                    <span class="text-emerald-300">{{ humanCents($c['revenue_cents']) }}</span>
                                @else
                                    <span class="text-white/30">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">{{ $c['enrollments_count'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-white/40">{{ __('admin.statistics.no_data') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
