@extends('admin.spa-inner')

@section('title', app()->getLocale() === 'en' ? 'New university' : 'جامعة جديدة')
@section('heading', app()->getLocale() === 'en' ? 'New university' : 'جامعة جديدة')
@section('subheading', __('admin.nav.academics'))

@section('content')
    @include('admin.academics.universities._form', [
        'university' => null,
        'action' => route('admin.academics.universities.store'),
        'method' => 'POST',
    ])
@endsection


