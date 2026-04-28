<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\StudyTerm;
use App\Models\StudyYear;
use App\Models\University;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicsAdminController extends Controller
{
    public function universitiesIndex(): JsonResponse
    {
        $items = University::query()
            ->orderBy('id')
            ->get(['id', 'name', 'name_en', 'created_at', 'updated_at']);

        return response()->json(['universities' => $items]);
    }

    public function universitiesStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $university = University::query()->create($data);

        return response()->json(['university' => $university], 201);
    }

    public function universitiesUpdate(Request $request, University $university): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'name_en' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $university->update($data);

        return response()->json(['university' => $university->fresh()]);
    }

    public function universitiesDestroy(University $university): JsonResponse
    {
        $university->delete();

        return response()->json(['deleted' => true]);
    }

    public function facultiesIndex(University $university): JsonResponse
    {
        $items = $university->faculties()
            ->orderBy('id')
            ->get(['id', 'university_id', 'name', 'name_en', 'created_at', 'updated_at']);

        return response()->json(['faculties' => $items]);
    }

    public function facultiesStore(Request $request, University $university): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $faculty = $university->faculties()->create($data);

        return response()->json(['faculty' => $faculty], 201);
    }

    public function facultiesUpdate(Request $request, University $university, Faculty $faculty): JsonResponse
    {
        abort_unless((int) $faculty->university_id === (int) $university->id, 404);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'name_en' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $faculty->update($data);

        return response()->json(['faculty' => $faculty->fresh()]);
    }

    public function facultiesDestroy(University $university, Faculty $faculty): JsonResponse
    {
        abort_unless((int) $faculty->university_id === (int) $university->id, 404);
        $faculty->delete();

        return response()->json(['deleted' => true]);
    }

    public function studyYearsIndex(Faculty $faculty): JsonResponse
    {
        $items = $faculty->studyYears()
            ->orderBy('year_number')
            ->orderBy('id')
            ->get(['id', 'faculty_id', 'year_number', 'name', 'name_en', 'created_at', 'updated_at']);

        return response()->json(['study_years' => $items]);
    }

    public function studyYearsStore(Request $request, Faculty $faculty): JsonResponse
    {
        $data = $request->validate([
            'year_number' => ['required', 'integer', 'min:1', 'max:32767'],
            'name' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $year = $faculty->studyYears()->create($data);

        return response()->json(['study_year' => $year], 201);
    }

    public function studyYearsUpdate(Request $request, Faculty $faculty, StudyYear $studyYear): JsonResponse
    {
        abort_unless((int) $studyYear->faculty_id === (int) $faculty->id, 404);

        $data = $request->validate([
            'year_number' => ['sometimes', 'required', 'integer', 'min:1', 'max:32767'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'name_en' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $studyYear->update($data);

        return response()->json(['study_year' => $studyYear->fresh()]);
    }

    public function studyYearsDestroy(Faculty $faculty, StudyYear $studyYear): JsonResponse
    {
        abort_unless((int) $studyYear->faculty_id === (int) $faculty->id, 404);
        $studyYear->delete();

        return response()->json(['deleted' => true]);
    }

    public function studyTermsIndex(StudyYear $studyYear): JsonResponse
    {
        $items = $studyYear->terms()
            ->orderBy('term_number')
            ->orderBy('id')
            ->get(['id', 'study_year_id', 'term_number', 'name', 'name_en', 'created_at', 'updated_at']);

        return response()->json(['study_terms' => $items]);
    }

    public function studyTermsStore(Request $request, StudyYear $studyYear): JsonResponse
    {
        $data = $request->validate([
            'term_number' => ['required', 'integer', 'min:1', 'max:32767'],
            'name' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $term = $studyYear->terms()->create($data);

        return response()->json(['study_term' => $term], 201);
    }

    public function studyTermsUpdate(Request $request, StudyYear $studyYear, StudyTerm $studyTerm): JsonResponse
    {
        abort_unless((int) $studyTerm->study_year_id === (int) $studyYear->id, 404);

        $data = $request->validate([
            'term_number' => ['sometimes', 'required', 'integer', 'min:1', 'max:32767'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'name_en' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $studyTerm->update($data);

        return response()->json(['study_term' => $studyTerm->fresh()]);
    }

    public function studyTermsDestroy(StudyYear $studyYear, StudyTerm $studyTerm): JsonResponse
    {
        abort_unless((int) $studyTerm->study_year_id === (int) $studyYear->id, 404);
        $studyTerm->delete();

        return response()->json(['deleted' => true]);
    }

    /** Attach a course to a term without removing the course’s links to other terms. */
    public function studyTermAttachCourse(Request $request, StudyTerm $studyTerm): JsonResponse
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ]);

        $course = Course::query()->findOrFail($data['course_id']);
        $studyTerm->courses()->syncWithoutDetaching([$course->id]);

        return response()->json([
            'study_term_id' => $studyTerm->id,
            'course_id' => $course->id,
            'attached' => true,
        ]);
    }

    public function studyTermDetachCourse(StudyTerm $studyTerm, Course $course): JsonResponse
    {
        $studyTerm->courses()->detach($course->id);

        return response()->json([
            'study_term_id' => $studyTerm->id,
            'course_id' => $course->id,
            'detached' => true,
        ]);
    }

    /** Courses linked to a study term (academics tab). */
    public function studyTermCourses(StudyTerm $studyTerm): JsonResponse
    {
        $courses = $studyTerm->courses()
            ->orderBy('courses.id')
            ->get(['courses.id', 'courses.title', 'courses.title_en', 'courses.teacher_id']);

        return response()->json([
            'study_term_id' => $studyTerm->id,
            'courses' => $courses,
        ]);
    }
}
