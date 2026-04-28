@extends('admin.spa-inner')

@section('title', app()->getLocale() === 'en' ? 'New study term' : 'فصل دراسي جديد')
@section('heading', app()->getLocale() === 'en' ? 'New study term' : 'فصل دراسي جديد')
@section('subheading', $university->name . ' • ' . $faculty->name . ' • ' . ($year->year_number))

@section('content')
    @include('admin.academics.terms._form', [
        'university' => $university,
        'faculty' => $faculty,
        'year' => $year,
        'term' => null,
        'action' => route('admin.academics.universities.faculties.years.terms.store', [$university, $faculty, $year]),
        'method' => 'POST',
    ])
@endsection


