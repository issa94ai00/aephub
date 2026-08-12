<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Services\ExamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamManagementTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(): User
    {
        return User::factory()->create([
            'role' => 'teacher',
            'teacher_approval_status' => User::TEACHER_APPROVAL_APPROVED,
        ]);
    }

    private function course(?User $teacher = null): Course
    {
        $teacher ??= $this->teacher();

        return Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Algorithms',
            'status' => 'published',
        ]);
    }

    private function publishedExam(Course $course, ExamService $svc): Exam
    {
        return $svc->saveExam(new Exam, [
            'course_id' => $course->id,
            'title' => 'Midterm',
            'status' => Exam::STATUS_PUBLISHED,
            'pass_percent' => 60,
            'show_correct_answers' => true,
            'grade_bands' => $svc->defaultGradeBands(),
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'prompt' => '2 + 2 = ?',
                    'points' => 2,
                    'options' => [
                        ['label' => '3', 'is_correct' => false],
                        ['label' => '4', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'prompt' => 'The sky is blue',
                    'points' => 1,
                    'correct_true' => true,
                ],
                [
                    'type' => 'short_answer',
                    'prompt' => 'Capital of France?',
                    'points' => 1,
                    'accepted_answers' => ['Paris', 'باريس'],
                ],
            ],
        ]);
    }

    public function test_admin_can_create_exam_via_web(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->course();

        $payload = [
            'course_id' => $course->id,
            'title' => 'Quiz 1',
            'status' => 'draft',
            'pass_percent' => 50,
            'show_correct_answers' => true,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'grade_bands' => app(ExamService::class)->defaultGradeBands(),
            'questions' => [
                [
                    'type' => 'true_false',
                    'prompt' => 'PHP is a language',
                    'points' => 1,
                    'correct_true' => true,
                ],
            ],
        ];

        $this->actingAs($admin)
            ->post(route('admin.exams.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('exams', [
            'course_id' => $course->id,
            'title' => 'Quiz 1',
            'status' => 'draft',
        ]);

        $exam = Exam::query()->where('title', 'Quiz 1')->first();
        $this->assertNotNull($exam);
        $this->assertSame(1, $exam->questions()->count());
        $this->assertGreaterThanOrEqual(4, $exam->gradeBands()->count());
    }

    public function test_student_receives_instant_score_on_submit(): void
    {
        $svc = app(ExamService::class);
        $course = $this->course();
        $exam = $this->publishedExam($course, $svc)->load(['questions.options', 'gradeBands']);
        $student = User::factory()->create(['role' => 'student']);

        CourseEnrollment::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 'approved',
        ]);

        $attempt = $svc->startAttempt($student, $exam);
        $mcq = $exam->questions->firstWhere('type', 'multiple_choice');
        $tf = $exam->questions->firstWhere('type', 'true_false');
        $sa = $exam->questions->firstWhere('type', 'short_answer');

        $attempt = $svc->submitAttempt($attempt, $exam, [
            [
                'question_id' => $mcq->id,
                'selected_option_id' => $mcq->options->firstWhere('is_correct', true)->id,
            ],
            [
                'question_id' => $tf->id,
                'selected_option_id' => $tf->options->firstWhere('is_correct', true)->id,
            ],
            [
                'question_id' => $sa->id,
                'text_answer' => 'paris',
            ],
        ]);

        $this->assertSame(100.0, (float) $attempt->score_percent);
        $this->assertTrue($attempt->passed);
        $this->assertNotEmpty($attempt->grade_label);
        $this->assertSame(ExamAttempt::STATUS_SUBMITTED, $attempt->status);
    }

    public function test_unenrolled_student_cannot_start_exam(): void
    {
        $svc = app(ExamService::class);
        $course = $this->course();
        $exam = $this->publishedExam($course, $svc);
        $student = User::factory()->create(['role' => 'student']);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $svc->startAttempt($student, $exam);
    }

    public function test_grade_band_resolves_for_score(): void
    {
        $svc = app(ExamService::class);
        $course = $this->course();
        $exam = $this->publishedExam($course, $svc);
        $exam->load('gradeBands');

        $band = $svc->resolveGradeBand($exam, 95);
        $this->assertSame('ممتاز', $band?->label);

        $band = $svc->resolveGradeBand($exam, 55);
        $this->assertSame('راسب', $band?->label);
    }
}
