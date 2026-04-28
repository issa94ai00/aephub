<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\StudyYear;
use App\Models\University;
use App\Support\AdminInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class StudyYearWebController extends Controller
{
    public function index(University $university, Faculty $faculty): Response
    {
        abort_unless((int) $faculty->university_id === (int) $university->id, 404);

        $years = StudyYear::query()
            ->where('faculty_id', $faculty->id)
            ->orderBy('year_number')
            ->orderBy('id')
            ->paginate(30)
            ->withQueryString();

        return AdminInertia::frame('admin.academics.years.index', compact('university', 'faculty', 'years'));
    }

    public function create(University $university, Faculty $faculty): Response
    {
        abort_unless((int) $faculty->university_id === (int) $university->id, 404);

        return AdminInertia::frame('admin.academics.years.create', compact('university', 'faculty'));
    }

    public function store(Request $request, University $university, Faculty $faculty): RedirectResponse
    {
        abort_unless((int) $faculty->university_id === (int) $university->id, 404);

        $data = $request->validate([
            'year_number' => ['required', 'integer', 'min:1', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $faculty->studyYears()->create($data);

        return redirect()
            ->route('admin.academics.universities.faculties.years.index', [$university, $faculty])
            ->with('status', __('admin.flash.study_year_created'));
    }

    public function edit(University $university, Faculty $faculty, StudyYear $year): Response
    {
        abort_unless((int) $faculty->university_id === (int) $university->id, 404);
        abort_unless((int) $year->faculty_id === (int) $faculty->id, 404);

        return AdminInertia::frame('admin.academics.years.edit', compact('university', 'faculty', 'year'));
    }

    public function update(Request $request, University $university, Faculty $faculty, StudyYear $year): RedirectResponse
    {
        abort_unless((int) $faculty->university_id === (int) $university->id, 404);
        abort_unless((int) $year->faculty_id === (int) $faculty->id, 404);

        $data = $request->validate([
            'year_number' => ['required', 'integer', 'min:1', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $year->update($data);

        return redirect()
            ->route('admin.academics.universities.faculties.years.index', [$university, $faculty])
            ->with('status', __('admin.flash.study_year_updated'));
    }

    public function destroy(University $university, Faculty $faculty, StudyYear $year): RedirectResponse
    {
        abort_unless((int) $faculty->university_id === (int) $university->id, 404);
        abort_unless((int) $year->faculty_id === (int) $faculty->id, 404);

        $year->delete();

        return redirect()
            ->route('admin.academics.universities.faculties.years.index', [$university, $faculty])
            ->with('status', __('admin.flash.study_year_deleted'));
    }
}

