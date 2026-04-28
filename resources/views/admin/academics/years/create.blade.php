@extends('admin.spa-inner')

@section('title', app()->getLocale() === 'en' ? 'New study year' : 'سنة دراسية جديدة')
@section('heading', app()->getLocale() === 'en' ? 'New study year' : 'سنة دراسية جديدة')
@section('subheading', $university->name . ' • ' . $faculty->name)

@section('content')
    @include('admin.academics.years._form', [
        'university' => $university,
        'faculty' => $faculty,
        'year' => null,
        'action' => route('admin.academics.universities.faculties.years.store', [$university, $faculty]),
        'method' => 'POST',
    ])
@endsection


