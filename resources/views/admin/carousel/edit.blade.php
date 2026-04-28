@extends('admin.spa-inner')

@section('title', __('admin.carousel.edit_title'))
@section('heading', __('admin.carousel.edit_heading'))
@section('subheading', __('admin.carousel.edit_sub', ['id' => $slide->id]))

@section('content')
    <form method="post" action="{{ route('admin.carousel.update', $slide) }}" enctype="multipart/form-data" class="max-w-3xl">
        @csrf
        @method('PUT')
        @include('admin.carousel._form', ['slide' => $slide])
    </form>
@endsection

