@extends('admin.spa-inner')

@section('title', app()->getLocale() === 'en' ? 'Edit faculty' : 'تعديل كلية')
@section('heading', app()->getLocale() === 'en' ? 'Edit faculty' : 'تعديل كلية')
@section('subheading', $university->name)

@section('content')
    @include('admin.academics.faculties._form', [
        'university' => $university,
        'faculty' => $faculty,
        'action' => route('admin.academics.universities.faculties.update', [$university, $faculty]),
        'method' => 'PUT',
    ])
@endsection


