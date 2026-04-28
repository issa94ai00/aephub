@extends('admin.spa-inner')

@section('title', __('admin.faq.edit_title'))
@section('heading', __('admin.faq.edit_heading'))
@section('subheading', __('admin.faq.edit_sub', ['id' => $faq->id]))

@section('content')
    <form method="post" action="{{ route('admin.faqs.update', $faq) }}" class="max-w-3xl">
        @csrf
        @method('PUT')
        @include('admin.faq._form', ['faq' => $faq])
    </form>
@endsection
