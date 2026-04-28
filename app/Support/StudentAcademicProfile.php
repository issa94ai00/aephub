<?php

namespace App\Support;

use App\Models\StudyTerm;

final class StudentAcademicProfile
{
    /**
     * Resolve full academic chain from a study term (university → faculty → year → term).
     *
     * @return array<string, mixed>
     */
    public static function attributesFromStudyTermId(int $studyTermId): array
    {
        $term = StudyTerm::query()
            ->with(['studyYear.faculty.university'])
            ->findOrFail($studyTermId);

        $year = $term->studyYear;
        $faculty = $year->faculty;
        $uni = $faculty->university;

        return [
            'university_id' => $uni->id,
            'faculty_id' => $faculty->id,
            'study_year_id' => $year->id,
            'study_term_id' => $term->id,
            'university' => $uni->localized_name,
            'study_year' => (string) $year->year_number,
            'study_term' => (string) $term->term_number,
        ];
    }
}
