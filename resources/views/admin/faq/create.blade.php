@extends('admin.spa-inner')

@section('title', __('admin.faq.create_title'))
@section('heading', __('admin.faq.create_heading'))
@section('subheading', __('admin.faq.create_sub'))

@section('content')
    <form method="post" action="{{ route('admin.faqs.store') }}" class="max-w-3xl">
        @csrf
        @include('admin.faq._form', ['faq' => null])
    </form>
@endsection
