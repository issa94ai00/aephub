@extends('admin.spa-inner')

@section('title', __('admin.teachers.title'))
@section('heading', __('admin.teachers.heading'))
@section('subheading', __('admin.teachers.subheading'))

@section('content')
    <section class="admin-card p-5">
        <h2 class="text-sm font-semibold text-white">{{ __('admin.teachers.pending_title') }}</h2>
        <p class="mt-1 text-xs text-white/55">{{ __('admin.teachers.pending_hint') }}</p>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-3 py-2 text-start">{{ __('admin.teachers.col_id') }}</th>
                        <th class="px-3 py-2 text-start">{{ __('admin.teachers.col_name') }}</th>
                        <th class="px-3 py-2 text-start">{{ __('admin.teachers.col_email') }}</th>
                        <th class="px-3 py-2 text-start">{{ __('admin.teachers.col_university') }}</th>
                        <th class="px-3 py-2 text-start">{{ __('admin.teachers.col_year_term') }}</th>
                        <th class="px-3 py-2 text-end">{{ __('admin.teachers.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse ($pendingTeachers as $teacher)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-3 py-2 text-white/60">{{ $teacher->id }}</td>
                            <td class="px-3 py-2 text-white">{{ $teacher->name }}</td>
                            <td class="px-3 py-2 text-white/75">{{ $teacher->email }}</td>
                            <td class="px-3 py-2 text-white/70">{{ $teacher->university ?: '—' }}</td>
                            <td class="px-3 py-2 text-white/70">{{ trim(($teacher->study_year ?: '') . ' ' . ($teacher->study_term ?: '')) ?: '—' }}</td>
                            <td class="px-3 py-2 text-end">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <form method="post" action="{{ route('admin.teachers.approve', $teacher) }}">
                                        @csrf
                                        <button type="submit" class="admin-btn rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500">
                                            {{ __('admin.teachers.approve') }}
                                        </button>
                                    </form>
                                    <form method="post" action="{{ route('admin.teachers.reject', $teacher) }}" onsubmit="return confirm(@json(__('admin.teachers.confirm_reject')));">
                                        @csrf
                                        <button type="submit" class="admin-btn rounded-lg border border-rose-400/40 bg-rose-500/10 px-3 py-1.5 text-xs font-semibold text-rose-100 hover:bg-rose-500/20">
                                            {{ __('admin.teachers.reject') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-white/55">{{ __('admin.teachers.no_pending') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-card mt-6 p-5">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-white">{{ __('admin.teachers.courses_title') }}</h2>
                <p class="mt-1 text-xs text-white/55">{{ __('admin.teachers.courses_hint') }}</p>
            </div>
            <form method="get" class="flex items-center gap-2">
                <label class="text-xs text-white/60">{{ __('admin.teachers.filter_teacher') }}</label>
                <select name="teacher_id" class="rounded-lg border border-white/10 bg-[#0a0f0d] px-2 py-1.5 text-xs text-white" onchange="this.form.submit()">
                    <option value="">{{ __('admin.courses.all') }}</option>
                    @foreach ($teacherOptions as $teacher)
                        <option value="{{ $teacher->id }}" @selected($selectedTeacherId === (int) $teacher->id)>
                            {{ $teacher->name }} ({{ $teacher->role === 'admin' ? __('admin.teachers.label_admin') : __('admin.teachers.label_teacher') }})
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-3 py-2 text-start">{{ __('admin.teachers.col_course') }}</th>
                        <th class="px-3 py-2 text-start">{{ __('admin.teachers.col_current_teacher') }}</th>
                        <th class="px-3 py-2 text-start">{{ __('admin.teachers.col_status') }}</th>
                        <th class="px-3 py-2 text-end">{{ __('admin.teachers.col_change_teacher') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse ($courses as $course)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-3 py-2 text-white">
                                <div class="font-medium">{{ $course->title }}</div>
                                <div class="text-[11px] text-white/45">#{{ $course->id }}</div>
                            </td>
                            <td class="px-3 py-2 text-white/75">
                                {{ $course->teacher->name ?? '—' }}
                                @if (($course->teacher->role ?? null) === 'teacher' && ($course->teacher->teacher_approval_status ?? null) !== 'approved')
                                    <span class="ms-1 rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] text-amber-200">{{ __('admin.teachers.not_approved_badge') }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-white/70">{{ trans()->has('admin.course_status.'.$course->status) ? __('admin.course_status.'.$course->status) : $course->status }}</td>
                            <td class="px-3 py-2 text-end">
                                <form method="post" action="{{ route('admin.teachers.reassign-course', $course) }}" class="flex flex-wrap items-center justify-end gap-2">
                                    @csrf
                                    <select name="teacher_id" class="rounded-lg border border-white/10 bg-[#0a0f0d] px-2 py-1.5 text-xs text-white">
                                        @foreach ($teacherOptions as $teacher)
                                            <option value="{{ $teacher->id }}" @selected((int) $course->teacher_id === (int) $teacher->id)>
                                                {{ $teacher->name }} ({{ $teacher->role === 'admin' ? __('admin.teachers.label_admin') : __('admin.teachers.label_teacher') }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="admin-btn rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white hover:bg-white/15">
                                        {{ __('admin.teachers.save') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-white/55">{{ __('admin.teachers.no_courses') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($courses->hasPages())
            <div class="mt-4 border-t border-white/10 pt-3 text-xs text-white/60">
                {{ $courses->links() }}
            </div>
        @endif
    </section>
@endsection

