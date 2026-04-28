@extends('admin.spa-inner')

@section('title', app()->getLocale() === 'en' ? 'New faculty' : 'كلية جديدة')
@section('heading', app()->getLocale() === 'en' ? 'New faculty' : 'كلية جديدة')
@section('subheading', $university->name)

@section('content')
    @include('admin.academics.faculties._form', [
        'university' => $university,
        'faculty' => null,
        'action' => route('admin.academics.universities.faculties.store', $university),
        'method' => 'POST',
    ])
@endsection


