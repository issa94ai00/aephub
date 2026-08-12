<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExamController extends Controller
{
    public function __construct(private ExamService $exams) {}

    public function index(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();
        $this->assertEnrollment($user->id, $course->id);

        $exams = Exam::query()
            ->where('course_id', $course->id)
            ->where('status', Exam::STATUS_PUBLISHED)
            ->withCount('questions')
            ->orderByDesc('id')
            ->get();

        $attemptStats = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->get()
            ->groupBy('exam_id');

        $items = $exams->map(function (Exam $exam) use ($attemptStats) {
            $mine = $attemptStats->get($exam->id, collect());
            $best = $mine
                ->whereIn('status', [ExamAttempt::STATUS_SUBMITTED, ExamAttempt::STATUS_EXPIRED])
                ->sortByDesc('score_percent')
                ->first();
            $open = $mine->firstWhere('status', ExamAttempt::STATUS_IN_PROGRESS);

            return [
                'id' => $exam->id,
                'title' => $exam->localized_title,
                'description' => $exam->localized_description !== '' ? $exam->localized_description : null,
                'duration_minutes' => $exam->duration_minutes,
                'pass_percent' => (float) $exam->pass_percent,
                'max_attempts' => $exam->max_attempts,
                'questions_count' => $exam->questions_count,
                'available_now' => $exam->isAvailableNow(),
                'available_from' => $exam->available_from?->toIso8601String(),
                'available_until' => $exam->available_until?->toIso8601String(),
                'my_attempts_count' => $mine->whereIn('status', [ExamAttempt::STATUS_SUBMITTED, ExamAttempt::STATUS_EXPIRED])->count(),
                'open_attempt_id' => $open?->id,
                'best_score_percent' => $best?->score_percent,
                'best_passed' => $best?->passed,
                'best_grade' => $best?->grade_label,
            ];
        });

        return response()->json(['data' => $items]);
    }

    public function show(Request $request, Course $course, Exam $exam): JsonResponse
    {
        $this->assertExamBelongs($course, $exam);
        $user = $request->user();
        $this->exams->assertStudentCanAccess($user, $exam);

        if (! $exam->isPublished()) {
            throw new HttpException(404, 'Exam not found');
        }

        $exam->loadCount('questions');

        return response()->json([
            'data' => [
                'id' => $exam->id,
                'title' => $exam->localized_title,
                'description' => $exam->localized_description !== '' ? $exam->localized_description : null,
                'duration_minutes' => $exam->duration_minutes,
                'pass_percent' => (float) $exam->pass_percent,
                'max_attempts' => $exam->max_attempts,
                'questions_count' => $exam->questions_count,
                'shuffle_questions' => $exam->shuffle_questions,
                'show_correct_answers' => $exam->show_correct_answers,
                'available_now' => $exam->isAvailableNow(),
                'grade_bands' => $exam->gradeBands()->get()->map(fn ($b) => [
                    'min_percent' => (float) $b->min_percent,
                    'max_percent' => (float) $b->max_percent,
                    'label' => $b->localized_label,
                    'color' => $b->color,
                ])->values(),
            ],
        ]);
    }

    public function start(Request $request, Course $course, Exam $exam): JsonResponse
    {
        $this->assertExamBelongs($course, $exam);
        $attempt = $this->exams->startAttempt($request->user(), $exam);
        $exam->load(['questions.options']);

        return response()->json([
            'data' => $this->attemptPayload($attempt, $exam, false),
        ], 201);
    }

    public function showAttempt(Request $request, Course $course, Exam $exam, ExamAttempt $attempt): JsonResponse
    {
        $this->assertExamBelongs($course, $exam);
        $this->assertAttemptOwner($request, $exam, $attempt);

        $this->exams->expireIfTimedOut($attempt, $exam);
        $attempt->refresh();
        $exam->load(['questions.options', 'gradeBands']);

        $reveal = ! $attempt->isOpen() && $exam->show_correct_answers;

        return response()->json([
            'data' => $this->attemptPayload($attempt, $exam, $reveal),
        ]);
    }

    public function saveAnswers(Request $request, Course $course, Exam $exam, ExamAttempt $attempt): JsonResponse
    {
        $this->assertExamBelongs($course, $exam);
        $this->assertAttemptOwner($request, $exam, $attempt);

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.selected_option_id' => ['nullable', 'integer'],
            'answers.*.text_answer' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->exams->saveAnswers($attempt, $exam, $data['answers']);

        return response()->json(['message' => 'Saved']);
    }

    public function submit(Request $request, Course $course, Exam $exam, ExamAttempt $attempt): JsonResponse
    {
        $this->assertExamBelongs($course, $exam);
        $this->assertAttemptOwner($request, $exam, $attempt);

        $data = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.selected_option_id' => ['nullable', 'integer'],
            'answers.*.text_answer' => ['nullable', 'string', 'max:2000'],
        ]);

        $attempt = $this->exams->submitAttempt($attempt, $exam, $data['answers'] ?? []);
        $exam->load(['questions.options', 'gradeBands']);

        return response()->json([
            'data' => $this->attemptPayload($attempt, $exam, $exam->show_correct_answers),
            'result' => [
                'score_points' => $attempt->score_points,
                'max_points' => $attempt->max_points,
                'score_percent' => $attempt->score_percent,
                'passed' => $attempt->passed,
                'grade_label' => $attempt->grade_label,
                'grade_label_en' => $attempt->grade_label_en,
                'grade_color' => $attempt->grade_color,
                'time_spent_seconds' => $attempt->time_spent_seconds,
                'status' => $attempt->status,
            ],
        ]);
    }

    private function assertExamBelongs(Course $course, Exam $exam): void
    {
        if ((int) $exam->course_id !== (int) $course->id) {
            throw new HttpException(404, 'Exam not found');
        }
    }

    private function assertEnrollment(int $userId, int $courseId): void
    {
        $ok = \App\Models\CourseEnrollment::query()
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->get()
            ->contains(fn ($e) => $e->hasActiveCourseAccess());

        if (! $ok) {
            throw new HttpException(403, 'Course access required');
        }
    }

    private function assertAttemptOwner(Request $request, Exam $exam, ExamAttempt $attempt): void
    {
        if ((int) $attempt->exam_id !== (int) $exam->id || (int) $attempt->user_id !== (int) $request->user()->id) {
            throw new HttpException(404, 'Attempt not found');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function attemptPayload(ExamAttempt $attempt, Exam $exam, bool $reveal): array
    {
        $answers = $attempt->relationLoaded('answers')
            ? $attempt->answers
            : $attempt->answers()->get();

        $answerMap = $answers->keyBy('question_id');

        $questions = $this->exams->presentQuestionsForTaking($exam, $reveal);
        foreach ($questions as &$q) {
            $a = $answerMap->get($q['id']);
            $q['answer'] = $a ? [
                'selected_option_id' => $a->selected_option_id,
                'text_answer' => $a->text_answer,
                'is_correct' => $reveal ? $a->is_correct : null,
                'points_awarded' => $reveal ? $a->points_awarded : null,
            ] : null;
        }
        unset($q);

        $expiresAt = null;
        if ($attempt->isOpen() && $exam->duration_minutes && $attempt->started_at) {
            $expiresAt = $attempt->started_at->copy()->addMinutes((int) $exam->duration_minutes)->toIso8601String();
        }

        return [
            'id' => $attempt->id,
            'exam_id' => $exam->id,
            'status' => $attempt->status,
            'started_at' => $attempt->started_at?->toIso8601String(),
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'expires_at' => $expiresAt,
            'score_points' => $attempt->score_points,
            'max_points' => $attempt->max_points,
            'score_percent' => $attempt->score_percent,
            'passed' => $attempt->passed,
            'grade_label' => $attempt->grade_label,
            'grade_label_en' => $attempt->grade_label_en,
            'grade_color' => $attempt->grade_color,
            'time_spent_seconds' => $attempt->time_spent_seconds,
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->localized_title,
                'pass_percent' => (float) $exam->pass_percent,
                'show_correct_answers' => $exam->show_correct_answers,
            ],
            'questions' => $questions,
        ];
    }
}
