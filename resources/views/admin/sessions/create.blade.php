@extends('admin.spa-inner')

@section('title', 'جلسة جديدة')
@section('heading', 'جلسة جديدة')
@section('subheading', $course->title)

@section('content')
    @include('admin.sessions._form', [
        'course' => $course,
        'session' => null,
        'action' => route('admin.courses.sessions.store', $course),
        'method' => 'POST',
    ])
@endsection


