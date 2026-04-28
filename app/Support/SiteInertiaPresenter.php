<?php

namespace App\Support;

use App\Models\Course;
use App\Models\Faculty;
use App\Models\University;

final class SiteInertiaPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function courseCard(Course $c): array
    {
        return [
            'id' => $c->id,
            'localized_title' => $c->localized_title,
            'localized_description' => $c->localized_description,
            'cover_image_url' => $c->cover_image_url,
            'price_cents' => (int) ($c->price_cents ?? 0),
            'currency' => $c->currency ?? 'SYP',
            'videos_count' => (int) ($c->videos_count ?? 0),
            'enrollments_count' => (int) ($c->enrollments_count ?? 0),
            'teacher_name' => $c->teacher?->name,
            'status' => $c->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function universityListItem(University $u): array
    {
        return [
            'id' => $u->id,
            'localized_name' => $u->localized_name,
            'faculties_count' => (int) ($u->faculties_count ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function facultyCard(Faculty $f): array
    {
        return [
            'id' => $f->id,
            'localized_name' => $f->localized_name,
            'study_years_count' => (int) ($f->study_years_count ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function relatedCourseCard(Course $c): array
    {
        return [
            'id' => $c->id,
            'localized_title' => $c->localized_title,
            'localized_description' => $c->localized_description,
            'videos_count' => (int) ($c->videos_count ?? 0),
            'price_cents' => (int) ($c->price_cents ?? 0),
            'currency' => $c->currency ?? 'SYP',
            'enrollments_count' => (int) ($c->enrollments_count ?? 0),
            'teacher_name' => $c->teacher?->name,
            'status' => $c->status,
        ];
    }
}
