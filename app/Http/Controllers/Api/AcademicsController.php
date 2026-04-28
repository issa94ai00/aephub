<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\StudyTerm;
use App\Models\StudyYear;
use App\Models\University;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicsController extends Controller
{
    public function universities(): JsonResponse
    {
        $items = University::query()
            ->orderBy('id')
            ->get(['id', 'name', 'name_en'])
            ->map(fn (University $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'name_en' => $u->name_en,
                'localized_name' => $u->localized_name,
            ]);

        return response()->json(['universities' => $items]);
    }

    public function faculties(Request $request): JsonResponse
    {
        $data = $request->validate([
            'university_id' => ['required', 'integer', 'exists:universities,id'],
        ]);

        $items = Faculty::query()
            ->where('university_id', $data['university_id'])
            ->orderBy('id')
            ->get(['id', 'university_id', 'name', 'name_en'])
            ->map(fn (Faculty $f) => [
                'id' => $f->id,
                'university_id' => $f->university_id,
                'name' => $f->name,
                'name_en' => $f->name_en,
                'localized_name' => $f->localized_name,
            ]);

        return response()->json(['faculties' => $items]);
    }

    public function studyYears(Request $request): JsonResponse
    {
        $data = $request->validate([
            'faculty_id' => ['required', 'integer', 'exists:faculties,id'],
        ]);

        $items = StudyYear::query()
            ->where('faculty_id', $data['faculty_id'])
            ->orderBy('year_number')
            ->orderBy('id')
            ->get(['id', 'faculty_id', 'year_number', 'name', 'name_en'])
            ->map(fn (StudyYear $y) => [
                'id' => $y->id,
                'faculty_id' => $y->faculty_id,
                'year_number' => $y->year_number,
                'name' => $y->name,
                'name_en' => $y->name_en,
                'localized_name' => $y->localized_name,
            ]);

        return response()->json(['study_years' => $items]);
    }

    public function studyTerms(Request $request): JsonResponse
    {
        $data = $request->validate([
            'study_year_id' => ['required', 'integer', 'exists:study_years,id'],
        ]);

        $items = StudyTerm::query()
            ->where('study_year_id', $data['study_year_id'])
            ->orderBy('term_number')
            ->orderBy('id')
            ->get(['id', 'study_year_id', 'term_number', 'name', 'name_en'])
            ->map(fn (StudyTerm $t) => [
                'id' => $t->id,
                'study_year_id' => $t->study_year_id,
                'term_number' => $t->term_number,
                'name' => $t->name,
                'name_en' => $t->name_en,
                'localized_name' => $t->localized_name,
            ]);

        return response()->json(['study_terms' => $items]);
    }

    /**
     * Public helper for registration UI: resolve hierarchy + labels for a study term.
     */
    public function studyTermContext(Request $request): JsonResponse
    {
        $data = $request->validate([
            'study_term_id' => ['required', 'integer', 'exists:study_terms,id'],
        ]);

        $term = StudyTerm::query()
            ->with(['studyYear.faculty.university'])
            ->findOrFail($data['study_term_id']);

        $year = $term->studyYear;
        $faculty = $year->faculty;
        $uni = $faculty->university;

        return response()->json([
            'university_id' => $uni->id,
            'faculty_id' => $faculty->id,
            'study_year_id' => $year->id,
            'study_term_id' => $term->id,
            'university_name' => $uni->localized_name,
            'faculty_name' => $faculty->localized_name,
            'study_year_label' => $year->localized_name,
            'study_term_label' => $term->localized_name,
        ]);
    }

    /**
     * Published courses linked to this study term (catalog). No JWT required.
     */
    public function studyTermPublishedCourses(Request $request, StudyTerm $studyTerm): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);

        $q = Course::query()
            ->where('status', 'published')
            ->whereHas('studyTerms', function ($w) use ($studyTerm): void {
                $w->where('study_terms.id', $studyTerm->id);
            })
            ->with(['teacher:id,name'])
            ->latest('id');

        if (! empty($data['q'])) {
            $needle = trim((string) $data['q']);
            $like = '%'.$needle.'%';
            $q->where(function ($w) use ($like): void {
                $w->where('title', 'like', $like)
                    ->orWhere('title_en', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('description_en', 'like', $like);
            });
        }

        return response()->json($q->paginate($perPage));
    }
}
