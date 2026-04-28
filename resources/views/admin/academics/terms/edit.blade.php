@extends('admin.spa-inner')

@section('title', app()->getLocale() === 'en' ? 'Edit study term' : 'تعديل فصل دراسي')
@section('heading', app()->getLocale() === 'en' ? 'Edit study term' : 'تعديل فصل دراسي')
@section('subheading', $university->name . ' • ' . $faculty->name . ' • ' . ($year->year_number))

@section('content')
    @include('admin.academics.terms._form', [
        'university' => $university,
        'faculty' => $faculty,
        'year' => $year,
        'term' => $term,
        'action' => route('admin.academics.universities.faculties.years.terms.update', [$university, $faculty, $year, $term]),
        'method' => 'PUT',
    ])
@endsection


