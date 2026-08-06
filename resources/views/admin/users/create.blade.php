@extends('admin.spa-inner')

@section('title', __('admin.users.create_title'))
@section('heading', __('admin.users.create_heading'))
@section('subheading', __('admin.users.create_sub'))

@section('content')
    <form method="post" action="{{ route('admin.users.store') }}" class="max-w-3xl">
        @csrf
        @include('admin.users._form', ['user' => null])
    </form>
@endsection
