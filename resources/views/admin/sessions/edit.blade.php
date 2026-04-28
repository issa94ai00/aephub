@extends('admin.spa-inner')

@section('title', 'تعديل جلسة')
@section('heading', 'تعديل جلسة')
@section('subheading', $course->title)

@section('content')
    @include('admin.sessions._form', [
        'course' => $course,
        'session' => $session,
        'action' => route('admin.courses.sessions.update', [$course, $session]),
        'method' => 'PUT',
    ])
@endsection


