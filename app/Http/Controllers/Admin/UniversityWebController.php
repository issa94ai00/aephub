<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\University;
use App\Support\AdminInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class UniversityWebController extends Controller
{
    public function index(): Response
    {
        $universities = University::query()->latest('id')->paginate(20)->withQueryString();

        return AdminInertia::frame('admin.academics.universities.index', compact('universities'));
    }

    public function create(): Response
    {
        return AdminInertia::frame('admin.academics.universities.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        University::create($data);

        return redirect()
            ->route('admin.academics.universities.index')
            ->with('status', __('admin.flash.university_created'));
    }

    public function edit(University $university): Response
    {
        return AdminInertia::frame('admin.academics.universities.edit', compact('university'));
    }

    public function update(Request $request, University $university): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $university->update($data);

        return redirect()
            ->route('admin.academics.universities.index')
            ->with('status', __('admin.flash.university_updated'));
    }

    public function destroy(University $university): RedirectResponse
    {
        $university->delete();

        return redirect()
            ->route('admin.academics.universities.index')
            ->with('status', __('admin.flash.university_deleted'));
    }
}

