<?php

namespace App\Services;

use App\Models\CourseEnrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamGradeBand;
use App\Models\ExamQuestion;
use App\Models\ExamQuestionOption;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExamService
{
    /**
     * @return list<array{min_percent: float, max_percent: float, label: string, label_en: ?string, color: ?string, sort_order: int}>
     */
    public function defaultGradeBands(): array
    {
        return [
            ['min_percent' => 90, 'max_percent' => 100, 'label' => 'ممتاز', 'label_en' => 'Excellent', 'color' => '#34d399', 'sort_order' => 0],
            ['min_percent' => 80, 'max_percent' => 89.99, 'label' => 'جيد جداً', 'label_en' => 'Very good', 'color' => '#38bdf8', 'sort_order' => 1],
            ['min_percent' => 70, 'max_percent' => 79.99, 'label' => 'جيد', 'label_en' => 'Good', 'color' => '#a78bfa', 'sort_order' => 2],
            ['min_percent' => 60, 'max_percent' => 69.99, 'label' => 'مقبول', 'label_en' => 'Pass', 'color' => '#fbbf24', 'sort_order' => 3],
            ['min_percent' => 0, 'max_percent' => 59.99, 'label' => 'راسب', 'label_en' => 'Fail', 'color' => '#fb7185', 'sort_order' => 4],
        ];
    }

    /**
     * Persist exam meta, grade bands, and questions in one transaction.
     *
     * @param  array<string, mixed>  $payload
     */
    public function saveExam(Exam $exam, array $payload, ?User $actor = null): Exam
    {
        return DB::transaction(function () use ($exam, $payload, $actor) {
            $exam->fill([
                'course_id' => $payload['course_id'],
                'title' => $payload['title'],
                'title_en' => $payload['title_en'] ?? null,
                'description' => $payload['description'] ?? null,
                'description_en' => $payload['description_en'] ?? null,
                'status' => $payload['status'] ?? Exam::STATUS_DRAFT,
                'duration_minutes' => $payload['duration_minutes'] ?? null,
                'pass_percent' => $payload['pass_percent'] ?? 60,
                'max_attempts' => $payload['max_attempts'] ?? null,
                'shuffle_questions' => (bool) ($payload['shuffle_questions'] ?? false),
                'shuffle_options' => (bool) ($payload['shuffle_options'] ?? false),
                'show_correct_answers' => (bool) ($payload['show_correct_answers'] ?? true),
                'available_from' => $payload['available_from'] ?? null,
                'available_until' => $payload['available_until'] ?? null,
            ]);

            if (! $exam->exists && $actor) {
                $exam->created_by = $actor->id;
            }

            $exam->save();

            $this->syncGradeBands($exam, $payload['grade_bands'] ?? $this->defaultGradeBands());
            $this->syncQuestions($exam, $payload['questions'] ?? []);

            return $exam->fresh(['questions.options', 'gradeBands', 'course:id,title,title_en']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $bands
     */
    public function syncGradeBands(Exam $exam, array $bands): void
    {
        $exam->gradeBands()->delete();

        foreach (array_values($bands) as $i => $band) {
            $exam->gradeBands()->create([
                'min_percent' => (float) $band['min_percent'],
                'max_percent' => (float) $band['max_percent'],
                'label' => (string) $band['label'],
                'label_en' => $band['label_en'] ?? null,
                'color' => $band['color'] ?? null,
                'sort_order' => (int) ($band['sort_order'] ?? $i),
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     */
    public function syncQuestions(Exam $exam, array $questions): void
    {
        $exam->questions()->each(function (ExamQuestion $q) {
            $q->options()->delete();
            $q->delete();
        });

        foreach (array_values($questions) as $i => $row) {
            $type = (string) ($row['type'] ?? ExamQuestion::TYPE_MULTIPLE_CHOICE);

            $question = $exam->questions()->create([
                'type' => $type,
                'prompt' => (string) $row['prompt'],
                'prompt_en' => $row['prompt_en'] ?? null,
                'points' => (float) ($row['points'] ?? 1),
                'sort_order' => (int) ($row['sort_order'] ?? $i),
                'explanation' => $row['explanation'] ?? null,
                'explanation_en' => $row['explanation_en'] ?? null,
                'accepted_answers' => $type === ExamQuestion::TYPE_SHORT_ANSWER
                    ? array_values(array_filter(array_map('strval', $row['accepted_answers'] ?? [])))
                    : null,
                'case_sensitive' => (bool) ($row['case_sensitive'] ?? false),
            ]);

            if (in_array($type, [ExamQuestion::TYPE_MULTIPLE_CHOICE, ExamQuestion::TYPE_TRUE_FALSE], true)) {
                $options = $row['options'] ?? [];
                if ($type === ExamQuestion::TYPE_TRUE_FALSE && $options === []) {
                    $correct = (bool) ($row['correct_true'] ?? true);
                    $options = [
                        ['label' => 'صح', 'label_en' => 'True', 'is_correct' => $correct, 'sort_order' => 0],
                        ['label' => 'خطأ', 'label_en' => 'False', 'is_correct' => ! $correct, 'sort_order' => 1],
                    ];
                }

                foreach (array_values($options) as $oi => $opt) {
                    $question->options()->create([
                        'label' => (string) $opt['label'],
                        'label_en' => $opt['label_en'] ?? null,
                        'is_correct' => (bool) ($opt['is_correct'] ?? false),
                        'sort_order' => (int) ($opt['sort_order'] ?? $oi),
                    ]);
                }
            }
        }
    }

    public function assertStudentCanAccess(User $user, Exam $exam): CourseEnrollment
    {
        $enrollment = CourseEnrollment::query()
            ->where('course_id', $exam->course_id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if (! $enrollment || ! $enrollment->hasActiveCourseAccess()) {
            throw new HttpException(403, 'Course access required');
        }

        return $enrollment;
    }

    public function startAttempt(User $user, Exam $exam): ExamAttempt
    {
        $this->assertStudentCanAccess($user, $exam);

        if (! $exam->isAvailableNow()) {
            throw ValidationException::withMessages([
                'exam' => [__('Exam is not available')],
            ]);
        }

        if ($exam->questions()->count() === 0) {
            throw ValidationException::withMessages([
                'exam' => [__('Exam has no questions')],
            ]);
        }

        $open = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
            ->latest('id')
            ->first();

        if ($open) {
            $this->expireIfTimedOut($open, $exam);

            $open->refresh();
            if ($open->isOpen()) {
                return $open->load(['answers']);
            }
        }

        if ($exam->max_attempts !== null) {
            $used = ExamAttempt::query()
                ->where('exam_id', $exam->id)
                ->where('user_id', $user->id)
                ->whereIn('status', [ExamAttempt::STATUS_SUBMITTED, ExamAttempt::STATUS_EXPIRED])
                ->count();

            if ($used >= (int) $exam->max_attempts) {
                throw ValidationException::withMessages([
                    'exam' => [__('Maximum attempts reached')],
                ]);
            }
        }

        return ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $user->id,
            'status' => ExamAttempt::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    /**
     * @param  list<array{question_id: int, selected_option_id?: ?int, text_answer?: ?string}>  $answers
     */
    public function saveAnswers(ExamAttempt $attempt, Exam $exam, array $answers): void
    {
        if (! $attempt->isOpen()) {
            throw ValidationException::withMessages([
                'attempt' => [__('Attempt is closed')],
            ]);
        }

        $this->expireIfTimedOut($attempt, $exam);
        $attempt->refresh();

        if (! $attempt->isOpen()) {
            throw ValidationException::withMessages([
                'attempt' => [__('Attempt timed out')],
            ]);
        }

        $questionIds = $exam->questions()->pluck('id')->all();
        $allowed = array_fill_keys($questionIds, true);

        foreach ($answers as $row) {
            $qid = (int) ($row['question_id'] ?? 0);
            if (! isset($allowed[$qid])) {
                continue;
            }

            ExamAttemptAnswer::updateOrCreate(
                ['attempt_id' => $attempt->id, 'question_id' => $qid],
                [
                    'selected_option_id' => $row['selected_option_id'] ?? null,
                    'text_answer' => $row['text_answer'] ?? null,
                ]
            );
        }
    }

    public function submitAttempt(ExamAttempt $attempt, Exam $exam, array $answers = [], bool $forceExpire = false): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $exam, $answers, $forceExpire) {
            $attempt = ExamAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if (! $attempt->isOpen()) {
                return $attempt->load(['answers.question.options', 'exam.gradeBands']);
            }

            if ($answers !== [] && ! $forceExpire) {
                // Persist final answers without nested timeout submission.
                $questionIds = $exam->questions()->pluck('id')->all();
                $allowed = array_fill_keys($questionIds, true);
                foreach ($answers as $row) {
                    $qid = (int) ($row['question_id'] ?? 0);
                    if (! isset($allowed[$qid])) {
                        continue;
                    }
                    ExamAttemptAnswer::updateOrCreate(
                        ['attempt_id' => $attempt->id, 'question_id' => $qid],
                        [
                            'selected_option_id' => $row['selected_option_id'] ?? null,
                            'text_answer' => $row['text_answer'] ?? null,
                        ]
                    );
                }
            }

            $timedOut = $forceExpire || $this->isTimedOut($attempt, $exam);
            $exam->load(['questions.options', 'gradeBands']);
            $attempt->load('answers');

            $answersByQuestion = $attempt->answers->keyBy('question_id');
            $score = 0.0;
            $max = 0.0;

            foreach ($exam->questions as $question) {
                $max += (float) $question->points;
                $answer = $answersByQuestion->get($question->id);
                [$correct, $awarded] = $this->gradeQuestion($question, $answer);

                if ($answer) {
                    $answer->forceFill([
                        'is_correct' => $correct,
                        'points_awarded' => $awarded,
                        'graded_at' => now(),
                    ])->save();
                } else {
                    ExamAttemptAnswer::create([
                        'attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'is_correct' => false,
                        'points_awarded' => 0,
                        'graded_at' => now(),
                    ]);
                }

                $score += $awarded;
            }

            $percent = $max > 0 ? round(($score / $max) * 100, 2) : 0.0;
            $band = $this->resolveGradeBand($exam, $percent);
            $started = $attempt->started_at ?? now();
            $spent = max(0, now()->diffInSeconds($started));

            $attempt->forceFill([
                'status' => $timedOut ? ExamAttempt::STATUS_EXPIRED : ExamAttempt::STATUS_SUBMITTED,
                'submitted_at' => now(),
                'score_points' => round($score, 2),
                'max_points' => round($max, 2),
                'score_percent' => $percent,
                'grade_label' => $band?->label,
                'grade_label_en' => $band?->label_en,
                'grade_color' => $band?->color,
                'passed' => $percent >= (float) $exam->pass_percent,
                'time_spent_seconds' => $spent,
            ])->save();

            return $attempt->fresh(['answers.question.options', 'exam.gradeBands', 'user:id,name,email']);
        });
    }

    /**
     * @return array{0: bool, 1: float}
     */
    public function gradeQuestion(ExamQuestion $question, ?ExamAttemptAnswer $answer): array
    {
        if (! $answer) {
            return [false, 0.0];
        }

        $points = (float) $question->points;

        if ($question->type === ExamQuestion::TYPE_SHORT_ANSWER) {
            $given = trim((string) ($answer->text_answer ?? ''));
            if ($given === '') {
                return [false, 0.0];
            }

            $accepted = $question->accepted_answers ?? [];
            foreach ($accepted as $ok) {
                $needle = (string) $ok;
                if ($question->case_sensitive) {
                    if (hash_equals(trim($needle), $given)) {
                        return [true, $points];
                    }
                } elseif (mb_strtolower(trim($needle)) === mb_strtolower($given)) {
                    return [true, $points];
                }
            }

            return [false, 0.0];
        }

        $optionId = $answer->selected_option_id;
        if (! $optionId) {
            return [false, 0.0];
        }

        $option = $question->options->firstWhere('id', (int) $optionId)
            ?? ExamQuestionOption::query()->find($optionId);

        if (! $option || (int) $option->question_id !== (int) $question->id) {
            return [false, 0.0];
        }

        return $option->is_correct ? [true, $points] : [false, 0.0];
    }

    public function resolveGradeBand(Exam $exam, float $percent): ?ExamGradeBand
    {
        $bands = $exam->relationLoaded('gradeBands')
            ? $exam->gradeBands
            : $exam->gradeBands()->get();

        foreach ($bands as $band) {
            if ($percent >= (float) $band->min_percent && $percent <= (float) $band->max_percent) {
                return $band;
            }
        }

        // Edge: 100% when last band tops at 99.99
        return $bands->sortByDesc('max_percent')->first();
    }

    public function expireIfTimedOut(ExamAttempt $attempt, Exam $exam): void
    {
        if (! $attempt->isOpen() || ! $this->isTimedOut($attempt, $exam)) {
            return;
        }

        // Grade with whatever answers are already saved — avoid re-entering saveAnswers.
        $this->submitAttempt($attempt, $exam, [], true);
    }

    public function isTimedOut(ExamAttempt $attempt, Exam $exam): bool
    {
        if (! $exam->duration_minutes || ! $attempt->started_at) {
            return false;
        }

        return now()->gte($attempt->started_at->copy()->addMinutes((int) $exam->duration_minutes));
    }

    /**
     * @return array<string, mixed>
     */
    public function reportSummary(Exam $exam): array
    {
        $attempts = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->whereIn('status', [ExamAttempt::STATUS_SUBMITTED, ExamAttempt::STATUS_EXPIRED])
            ->get();

        $count = $attempts->count();
        $passed = $attempts->where('passed', true)->count();
        $avg = $count > 0 ? round((float) $attempts->avg('score_percent'), 2) : null;
        $best = $count > 0 ? round((float) $attempts->max('score_percent'), 2) : null;
        $worst = $count > 0 ? round((float) $attempts->min('score_percent'), 2) : null;

        $distribution = [];
        foreach ($exam->gradeBands as $band) {
            $n = $attempts->filter(function (ExamAttempt $a) use ($band) {
                $p = (float) $a->score_percent;

                return $p >= (float) $band->min_percent && $p <= (float) $band->max_percent;
            })->count();

            $distribution[] = [
                'label' => $band->label,
                'label_en' => $band->label_en,
                'color' => $band->color,
                'count' => $n,
                'percent' => $count > 0 ? round(($n / $count) * 100, 1) : 0,
            ];
        }

        return [
            'attempts_count' => $count,
            'unique_students' => $attempts->pluck('user_id')->unique()->count(),
            'passed_count' => $passed,
            'pass_rate' => $count > 0 ? round(($passed / $count) * 100, 1) : null,
            'average_percent' => $avg,
            'best_percent' => $best,
            'worst_percent' => $worst,
            'distribution' => $distribution,
        ];
    }

    /**
     * Student-facing question payload (hides correctness until result if needed).
     *
     * @return list<array<string, mixed>>
     */
    public function presentQuestionsForTaking(Exam $exam, bool $revealAnswers = false): array
    {
        $questions = $exam->questions->values();
        if ($exam->shuffle_questions) {
            $questions = $questions->shuffle()->values();
        }

        return $questions->map(function (ExamQuestion $q) use ($exam, $revealAnswers) {
            $options = $q->options->values();
            if ($exam->shuffle_options && $q->type !== ExamQuestion::TYPE_TRUE_FALSE) {
                $options = $options->shuffle()->values();
            }

            $row = [
                'id' => $q->id,
                'type' => $q->type,
                'prompt' => $q->localized_prompt,
                'points' => (float) $q->points,
                'sort_order' => $q->sort_order,
                'options' => $options->map(function (ExamQuestionOption $o) use ($revealAnswers) {
                    $item = [
                        'id' => $o->id,
                        'label' => $o->localized_label,
                    ];
                    if ($revealAnswers) {
                        $item['is_correct'] = (bool) $o->is_correct;
                    }

                    return $item;
                })->all(),
            ];

            if ($revealAnswers) {
                $row['explanation'] = $q->localized_explanation !== '' ? $q->localized_explanation : null;
                if ($q->type === ExamQuestion::TYPE_SHORT_ANSWER) {
                    $row['accepted_answers'] = $q->accepted_answers ?? [];
                }
            }

            return $row;
        })->all();
    }
}
