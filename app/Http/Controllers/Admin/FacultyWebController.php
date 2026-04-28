<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\University;
use App\Support\AdminInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class FacultyWebController extends Controller
{
    public function index(University $university): Response
    {
        $faculties = Faculty::query()
            ->where('university_id', $university->id)
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return AdminInertia::frame('admin.academics.faculties.index', compact('university', 'faculties'));
    }

    public function create(University $university): Response
    {
        return AdminInertia::frame('admin.academics.faculties.create', compact('university'));
    }

    public function store(Request $request, University $university): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $university->faculties()->create($data);

        return redirect()
            ->route('admin.academics.universities.faculties.index', $university)
            ->with('status', __('admin.flash.faculty_created'));
    }

    public function edit(University $university, Faculty $faculty): Response
    {
        abort_unless((int) $faculty->university_id === (int) $university->id, 404);

        return AdminInertia::frame('admin.academics.faculties.edit', compact('university', 'faculty'));
    }

    public function update(Request $request, University $university, Faculty $faculty): RedirectResponse
    {
        abort_unless((int) $faculty->university_id === (int) $university->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $faculty->update($data);

        return redirect()
            ->route('admin.academics.universities.faculties.index', $university)
            ->with('status', __('admin.flash.faculty_updated'));
    }

    public function destroy(University $university, Faculty $faculty): RedirectResponse
    {
        abort_unless((int) $faculty->university_id === (int) $university->id, 404);

        $faculty->delete();

        return redirect()
            ->route('admin.academics.universities.faculties.index', $university)
            ->with('status', __('admin.flash.faculty_deleted'));
    }
}

