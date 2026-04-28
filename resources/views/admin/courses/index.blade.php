@extends('admin.spa-inner')

@php($catalogMode = $catalogMode ?? false)

@section('title', $catalogMode ? __('admin.courses.title_student') : __('admin.courses.title'))
@section('heading', $catalogMode ? __('admin.courses.heading_student') : __('admin.courses.heading'))
@section('subheading', $catalogMode ? __('admin.courses.subheading_student') : __('admin.courses.subheading'))

@section('content')
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        @if ($catalogMode)
            <p class="text-xs text-white/55">{{ __('admin.courses.student_catalog_hint') }}</p>
            <a href="{{ route('admin.courses.index') }}" class="text-xs text-emerald-200 hover:underline">{{ __('admin.courses.link_full_management') }}</a>
        @else
            <form method="get" class="flex flex-wrap items-center gap-2 text-xs">
                <label class="text-white/60">{{ __('admin.courses.filter_status') }}</label>
                <select name="status" class="rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-white" onchange="this.form.submit()">
                    <option value="">{{ __('admin.courses.all') }}</option>
                    <option value="draft" @selected($status === 'draft')>{{ __('admin.course_status.draft') }}</option>
                    <option value="published" @selected($status === 'published')>{{ __('admin.course_status.published') }}</option>
                    <option value="archived" @selected($status === 'archived')>{{ __('admin.course_status.archived') }}</option>
                </select>
            </form>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.courses.student-catalog') }}" class="rounded-xl border border-white/15 px-4 py-2 text-xs font-medium text-white/85 hover:bg-white/5">{{ __('admin.nav.student_courses') }}</a>
                <a href="{{ route('admin.courses.create') }}" class="admin-btn inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-900/25 hover:bg-emerald-500">
                    + {{ __('admin.courses.new') }}
                </a>
            </div>
        @endif
    </div>

    <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-4 py-3 text-start">{{ __('admin.courses.col_id') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.courses.col_title') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.courses.col_teacher') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.courses.col_status') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.courses.col_price') }}</th>
                        <th class="px-4 py-3 text-end">{{ __('admin.courses.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse ($courses as $course)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-white/60">{{ $course->id }}</td>
                            <td class="px-4 py-3 font-medium text-white">{{ $course->title }}</td>
                            <td class="px-4 py-3 text-white/70">{{ $course->teacher->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-white/5 px-2 py-0.5 text-xs">{{ trans()->has('admin.course_status.'.$course->status) ? __('admin.course_status.'.$course->status) : $course->status }}</span>
                            </td>
                            <td class="px-4 py-3 text-white/80">
                                {{ number_format(($course->price_cents ?? 0) / 100, 2) }} {{ $course->currency ?? 'SYP' }}
                            </td>
                            <td class="px-4 py-3 text-end whitespace-nowrap">
                                <a href="{{ route('admin.courses.edit', $course) }}" class="text-emerald-200 hover:underline">{{ __('admin.courses.edit') }}</a>
                                @if (!$catalogMode)
                                    <form action="{{ route('admin.courses.destroy', $course) }}" method="post" class="inline ms-2" onsubmit="return confirm(@json(__('admin.courses.confirm_delete')));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-300 hover:underline">{{ __('admin.courses.delete') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-white/55">{{ __('admin.courses.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($courses->hasPages())
            <div class="border-t border-white/10 px-4 py-3 text-xs text-white/50">
                {{ $courses->links() }}
            </div>
        @endif
    </div>
@endsection

