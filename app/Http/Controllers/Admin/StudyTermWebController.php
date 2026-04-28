<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\StudyTerm;
use App\Models\StudyYear;
use App\Models\University;
use App\Support\AdminInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class StudyTermWebController extends Controller
{
    public function index(University $university, Faculty $faculty, StudyYear $year): Response
    {
        abort_unless((int) $faculty->university_id === (int) $university->id, 404);
        abort_unless((int) $year->faculty_id === (int) $faculty->id, 404);

        $terms = StudyTerm::query()
            ->where('study_year_id', $year->id)
            ->orderBy('term_number')
            ->orderBy('id')
            ->paginate(30)
            ->withQueryString();

        return AdminInertia::frame('admin.academics.terms.index', compact('university', 'faculty', 'year', 'terms'));
    }

    public function create(University $university, Faculty $faculty, StudyYear $year): Response
    {
        abort_unless((int) $faculty->university_id === (int) $university->id, 404);
        abort_unless((int) $year->faculty_id === (int) $faculty->id, 404);

        return AdminInertia::frame('admin.academics.terms.create', compact('university', 'faculty', 'year'));
    }

    public function store(Request $request, University $university, Faculty $faculty, StudyYear $year): RedirectResponse
    {
        abort_unless((int) $faculty->university_id === (int) $university->id, 404);
        abort_unless((int) $year->faculty_id === (int) $faculty->id, 404);

        $data = $request->validate([
            'term_number' => ['required', 'integer', 'min:1', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $year->terms()->create($data);

        return redirect()
            ->route('admin.academics.universities.faculties.years.terms.index', [$university, $faculty, $year])
            ->with('status', __('admin.flash.study_term_created'));
    }

    public function edit(University $university, Faculty $faculty, StudyYear $year, StudyTerm $term): Response
    {
        abort_unless((int) $faculty->university_id === (int) $university->id, 404);
        abort_unless((int) $year->faculty_id === (int) $faculty->id, 404);
        abort_unless((int) $term->study_year_id === (int) $year->id, 404);

        return AdminInertia::frame('admin.academics.terms.edit', compact('university', 'faculty', 'year', 'term'));
    }

    public function update(Request $request, University $university, Faculty $faculty, StudyYear $year, StudyTerm $term): RedirectResponse
    {
        abort_unless((int) $faculty->university_id === (int) $university->id, 404);
        abort_unless((int) $year->faculty_id === (int) $faculty->id, 404);
        abort_unless((int) $term->study_year_id === (int) $year->id, 404);

        $data = $request->validate([
            'term_number' => ['required', 'integer', 'min:1', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $term->update($data);

        return redirect()
            ->route('admin.academics.universities.faculties.years.terms.index', [$university, $faculty, $year])
            ->with('status', __('admin.flash.study_term_updated'));
    }

    public function destroy(University $university, Faculty $faculty, StudyYear $year, StudyTerm $term): RedirectResponse
    {
        abort_unless((int) $faculty->university_id === (int) $university->id, 404);
        abort_unless((int) $year->faculty_id === (int) $faculty->id, 404);
        abort_unless((int) $term->study_year_id === (int) $year->id, 404);

        $term->delete();

        return redirect()
            ->route('admin.academics.universities.faculties.years.terms.index', [$university, $faculty, $year])
            ->with('status', __('admin.flash.study_term_deleted'));
    }
}

