@extends('admin.spa-inner')

@section('title', __('admin.users.edit_title'))
@section('heading', __('admin.users.edit_heading'))
@section('subheading', __('admin.users.edit_sub'))

@section('content')
    <form method="post" action="{{ route('admin.users.update', $user) }}" class="max-w-3xl">
        @csrf
        @method('PUT')
        @include('admin.users._form', ['user' => $user])
    </form>
@endsection
