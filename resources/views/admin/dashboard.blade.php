@extends('admin.spa-inner')

@section('title', __('admin.dashboard.title'))
@section('heading', __('admin.dashboard.heading'))
@section('subheading', __('admin.dashboard.subheading'))

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="admin-card p-4">
            <div class="text-xs text-white/55">{{ __('admin.dashboard.courses_total') }}</div>
            <div class="mt-2 text-2xl font-bold text-white">{{ $stats['courses_total'] }}</div>
            <div class="mt-1 text-[11px] text-emerald-200/80">{{ __('admin.dashboard.published') }}: {{ $stats['courses_published'] }}</div>
        </div>
        <div class="admin-card p-4">
            <div class="text-xs text-white/55">{{ __('admin.dashboard.users') }}</div>
            <div class="mt-2 text-2xl font-bold text-white">{{ $stats['users_total'] }}</div>
            <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-white/55">
                @foreach ($stats['users_by_role'] as $r => $c)
                    <span class="rounded-full bg-white/5 px-2 py-0.5">{{ $r }}: {{ $c }}</span>
                @endforeach
            </div>
        </div>
        <div class="admin-card p-4">
            <div class="text-xs text-white/55">{{ __('admin.dashboard.enrollments_pending') }}</div>
            <div class="mt-2 text-2xl font-bold text-amber-200">{{ $stats['enrollments_pending'] }}</div>
        </div>
        <div class="admin-card p-4">
            <div class="text-xs text-white/55">{{ __('admin.dashboard.payments_pending') }}</div>
            <div class="mt-2 text-2xl font-bold text-amber-200">{{ $stats['payments_pending'] }}</div>
            <a href="{{ route('admin.payments.index', ['status' => 'pending']) }}" class="mt-2 inline-block text-[11px] text-emerald-200 hover:underline">{{ __('admin.dashboard.view_requests') }}</a>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="admin-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-white">{{ __('admin.dashboard.recent_courses') }}</h2>
                <a href="{{ route('admin.courses.index') }}" class="text-xs text-emerald-200 hover:underline">{{ __('admin.dashboard.all_courses') }}</a>
            </div>
            <ul class="mt-4 space-y-3 text-sm">
                @forelse ($recentCourses as $c)
                    <li class="flex items-start justify-between gap-3 border-b border-white/5 pb-3 last:border-0 last:pb-0">
                        <div>
                            <div class="font-medium text-white">{{ $c->title }}</div>
                            <div class="mt-0.5 text-xs text-white/50">{{ $c->teacher->name ?? '—' }} · {{ trans()->has('admin.course_status.'.$c->status) ? __('admin.course_status.'.$c->status) : $c->status }}</div>
                        </div>
                        <a href="{{ route('admin.courses.edit', $c) }}" class="shrink-0 text-xs text-emerald-200 hover:underline">{{ __('admin.dashboard.edit') }}</a>
                    </li>
                @empty
                    <li class="text-white/55">{{ __('admin.dashboard.no_courses') }}</li>
                @endforelse
            </ul>
        </section>

        <section class="admin-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-white">{{ __('admin.dashboard.recent_payments') }}</h2>
                <a href="{{ route('admin.payments.index') }}" class="text-xs text-emerald-200 hover:underline">{{ __('admin.dashboard.all_payments') }}</a>
            </div>
            <ul class="mt-4 space-y-3 text-sm">
                @forelse ($recentPayments as $p)
                    <li class="flex items-start justify-between gap-3 border-b border-white/5 pb-3 last:border-0 last:pb-0">
                        <div>
                            <div class="font-medium text-white">#{{ $p->id }} · {{ $p->course->title ?? __('admin.dashboard.course_fallback') }}</div>
                            <div class="mt-0.5 text-xs text-white/50">{{ $p->user->name ?? '—' }} · <span class="text-white/70">{{ trans()->has('admin.payment_status.'.$p->status) ? __('admin.payment_status.'.$p->status) : $p->status }}</span></div>
                        </div>
                        <a href="{{ route('admin.payments.show', $p) }}" class="shrink-0 text-xs text-emerald-200 hover:underline">{{ __('admin.dashboard.review') }}</a>
                    </li>
                @empty
                    <li class="text-white/55">{{ __('admin.dashboard.no_payments') }}</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection

