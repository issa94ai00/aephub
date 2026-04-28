@extends('admin.spa-inner')

@section('title', __('admin.courses.edit_title'))
@section('heading', __('admin.courses.edit_heading'))
@section('subheading', $course->title)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.courses.sessions.index', $course) }}" class="admin-btn rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/90 hover:bg-white/10">
            إدارة الجلسات
        </a>
    </div>
    @include('admin.courses._form', ['course' => $course, 'teachers' => $teachers, 'terms' => $terms, 'selectedTermIds' => $selectedTermIds ?? []])
@endsection

