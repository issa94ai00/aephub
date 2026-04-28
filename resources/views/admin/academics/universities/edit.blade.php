@extends('admin.spa-inner')

@section('title', app()->getLocale() === 'en' ? 'Edit university' : 'تعديل جامعة')
@section('heading', app()->getLocale() === 'en' ? 'Edit university' : 'تعديل جامعة')
@section('subheading', __('admin.nav.academics'))

@section('content')
    @include('admin.academics.universities._form', [
        'university' => $university,
        'action' => route('admin.academics.universities.update', $university),
        'method' => 'PUT',
    ])
@endsection


