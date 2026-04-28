@extends('admin.spa-inner')

@section('title', __('admin.carousel.create_title'))
@section('heading', __('admin.carousel.create_heading'))
@section('subheading', __('admin.carousel.create_sub'))

@section('content')
    <form method="post" action="{{ route('admin.carousel.store') }}" enctype="multipart/form-data" class="max-w-3xl">
        @csrf
        @include('admin.carousel._form', ['slide' => null])
    </form>
@endsection

