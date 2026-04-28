<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Faculty;
use App\Models\StudyTerm;
use App\Models\StudyYear;
use App\Models\University;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds Damascus University structure: faculties, 5 years × 2 terms each,
 * 2 published courses per term (assigned to teacher user), 8–12 sessions per course.
 * Assumes empty tables (use `php artisan migrate:fresh --seed`).
 */
class DamascusUniversityStructureSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = User::query()->updateOrCreate(
                ['email' => 'admin@admin.com'],
                [
                    'name' => 'admin',
                    'password' => 'admin',
                    'role' => 'admin',
                    'teacher_approval_status' => User::TEACHER_APPROVAL_APPROVED,
                    'device_lock_enabled' => false,
                    'status' => 'active',
                ]
            );

            $teacher = User::query()->updateOrCreate(
                ['email' => 'teacher@teacher.com'],
                [
                    'name' => 'teacher',
                    'password' => 'teacher',
                    'role' => 'teacher',
                    'teacher_approval_status' => User::TEACHER_APPROVAL_APPROVED,
                    'device_lock_enabled' => false,
                    'status' => 'active',
                ]
            );

            $student = User::query()->updateOrCreate(
                ['email' => 'student@student.com'],
                [
                    'name' => 'student',
                    'password' => 'student',
                    'role' => 'student',
                    'teacher_approval_status' => User::TEACHER_APPROVAL_APPROVED,
                    'device_lock_enabled' => false,
                    'status' => 'active',
                ]
            );

            $university = University::query()->create([
                'name' => 'جامعة دمشق',
                'name_en' => 'University of Damascus',
            ]);

            $admin->forceFill(['university_id' => $university->id])->save();
            $teacher->forceFill(['university_id' => $university->id])->save();
            $student->forceFill(['university_id' => $university->id])->save();

            $facultiesData = [
                ['name' => 'كلية الهندسة الكهربائية', 'name_en' => 'Faculty of Electrical Engineering'],
                ['name' => 'كلية الهندسة الميكانيكية', 'name_en' => 'Faculty of Mechanical Engineering'],
                ['name' => 'كلية هندسة تكنولوجيا المعلومات', 'name_en' => 'Faculty of Information Technology Engineering'],
                ['name' => 'الكلية التطبيقية', 'name_en' => 'Applied Technical College'],
                ['name' => 'كلية هندسة الاتصالات', 'name_en' => 'Faculty of Communications Engineering'],
            ];

            $courseCounter = 0;

            foreach ($facultiesData as $facultyInfo) {
                $faculty = Faculty::query()->create([
                    'university_id' => $university->id,
                    'name' => $facultyInfo['name'],
                    'name_en' => $facultyInfo['name_en'],
                ]);

                for ($yearNum = 1; $yearNum <= 5; $yearNum++) {
                    $studyYear = StudyYear::query()->create([
                        'faculty_id' => $faculty->id,
                        'year_number' => $yearNum,
                        'name' => 'السنة الدراسية '.$yearNum,
                        'name_en' => 'Study year '.$yearNum,
                    ]);

                    foreach ([1 => 'الفصل الأول', 2 => 'الفصل الثاني'] as $termNum => $termNameAr) {
                        $studyTerm = StudyTerm::query()->create([
                            'study_year_id' => $studyYear->id,
                            'term_number' => $termNum,
                            'name' => $termNameAr,
                            'name_en' => $termNum === 1 ? 'First semester' : 'Second semester',
                        ]);

                        foreach ([1, 2] as $courseSlot) {
                            $courseCounter++;
                            $sessionCount = 8 + (($courseCounter - 1) % 5);

                            $titleAr = sprintf(
                                '%s — سنة %d — %s — مقرر %d',
                                $faculty->name,
                                $yearNum,
                                $studyTerm->name,
                                $courseSlot
                            );
                            $titleEn = sprintf(
                                '%s — Year %d — %s — Course %d',
                                (string) $faculty->name_en,
                                $yearNum,
                                $studyTerm->term_number === 1 ? 'Semester 1' : 'Semester 2',
                                $courseSlot
                            );

                            $course = Course::query()->create([
                                'teacher_id' => $teacher->id,
                                'title' => $titleAr,
                                'title_en' => $titleEn,
                                'description' => 'مقرر ضمن الهيكل الأكاديمي التجريبي لجامعة دمشق.',
                                'description_en' => 'Course within the Damascus University demo academic structure.',
                                'price_cents' => 100000 + ($courseCounter % 50) * 1000,
                                'currency' => 'SYP',
                                'status' => 'published',
                            ]);

                            $course->studyTerms()->syncWithoutDetaching([$studyTerm->id]);

                            for ($s = 1; $s <= $sessionCount; $s++) {
                                CourseSession::query()->create([
                                    'course_id' => $course->id,
                                    'title' => 'جلسة '.$s,
                                    'title_en' => 'Session '.$s,
                                    'sort_order' => $s - 1,
                                ]);
                            }
                        }
                    }
                }
            }
        });
    }
}
