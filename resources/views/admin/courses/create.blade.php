@extends('admin.spa-inner')

@section('title', __('admin.courses.create_title'))
@section('heading', __('admin.courses.create_heading'))
@section('subheading', __('admin.courses.create_sub'))

@section('content')
    @include('admin.courses._form', ['course' => null, 'teachers' => $teachers, 'terms' => $terms, 'selectedTermIds' => []])
@endsection

