@extends('admin.spa-inner')

@section('title', app()->getLocale() === 'en' ? 'Edit study year' : 'تعديل سنة دراسية')
@section('heading', app()->getLocale() === 'en' ? 'Edit study year' : 'تعديل سنة دراسية')
@section('subheading', $university->name . ' • ' . $faculty->name)

@section('content')
    @include('admin.academics.years._form', [
        'university' => $university,
        'faculty' => $faculty,
        'year' => $year,
        'action' => route('admin.academics.universities.faculties.years.update', [$university, $faculty, $year]),
        'method' => 'PUT',
    ])
@endsection


