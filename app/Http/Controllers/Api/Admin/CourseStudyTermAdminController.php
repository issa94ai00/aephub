<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseStudyTermAdminController extends Controller
{
    public function show(Course $course): JsonResponse
    {
        $terms = $course->studyTerms()
            ->orderBy('study_terms.id')
            ->get([
                'study_terms.id',
                'study_terms.study_year_id',
                'study_terms.term_number',
                'study_terms.name',
                'study_terms.name_en',
            ]);

        return response()->json([
            'course_id' => $course->id,
            'study_term_ids' => $terms->pluck('id')->values()->all(),
            'study_terms' => $terms,
        ]);
    }

    /** Replace all study-term links for the course (same idea as web form study_term_ids). */
    public function sync(Request $request, Course $course): JsonResponse
    {
        $data = $request->validate([
            'study_term_ids' => ['required', 'array'],
            'study_term_ids.*' => ['integer', 'exists:study_terms,id'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['study_term_ids'])));
        $course->studyTerms()->sync($ids);

        return $this->show($course);
    }
}
