<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExamWebController extends Controller
{
    public function __construct(private ExamService $exams) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $courseId = $request->query('course_id');

        $q = Exam::query()
            ->with(['course:id,title,title_en'])
            ->withCount(['questions', 'attempts'])
            ->latest('id');

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('title', 'like', "%{$search}%")
                    ->orWhere('title_en', 'like', "%{$search}%");
            });
        }

        if (is_string($status) && $status !== '') {
            $q->where('status', $status);
        }

        if ($courseId) {
            $q->where('course_id', (int) $courseId);
        }

        $exams = $q->paginate(20)->withQueryString();

        $stats = [
            'total' => Exam::query()->count(),
            'published' => Exam::query()->where('status', Exam::STATUS_PUBLISHED)->count(),
            'draft' => Exam::query()->where('status', Exam::STATUS_DRAFT)->count(),
            'attempts' => ExamAttempt::query()
                ->whereIn('status', [ExamAttempt::STATUS_SUBMITTED, ExamAttempt::STATUS_EXPIRED])
                ->count(),
        ];

        return Inertia::render('Admin/Exams/Index', [
            'exams' => $exams,
            'search' => $search,
            'status' => $status,
            'course_id' => $courseId ? (int) $courseId : null,
            'courses' => $this->courseOptions(),
            'stats' => $stats,
        ]);
    }

    public function create(Request $request): Response
    {
        $courseId = $request->query('course_id');

        return Inertia::render('Admin/Exams/Form', [
            'exam' => null,
            'courses' => $this->courseOptions(),
            'default_course_id' => $courseId ? (int) $courseId : null,
            'default_grade_bands' => $this->exams->defaultGradeBands(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $exam = $this->exams->saveExam(new Exam, $data, $request->user());

        return redirect()
            ->route('admin.exams.edit', $exam)
            ->with('status', __('admin.flash.exam_created'));
    }

    public function edit(Exam $exam): Response
    {
        $exam->load(['questions.options', 'gradeBands', 'course:id,title,title_en']);

        return Inertia::render('Admin/Exams/Form', [
            'exam' => $this->examPayload($exam),
            'courses' => $this->courseOptions(),
            'default_course_id' => $exam->course_id,
            'default_grade_bands' => $this->exams->defaultGradeBands(),
        ]);
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $data = $this->validated($request);
        $this->exams->saveExam($exam, $data, $request->user());

        return redirect()
            ->route('admin.exams.edit', $exam)
            ->with('status', __('admin.flash.exam_updated'));
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $exam->delete();

        return redirect()
            ->route('admin.exams.index')
            ->with('status', __('admin.flash.exam_deleted'));
    }

    public function reports(Request $request): Response
    {
        $courseId = $request->query('course_id');
        $examId = $request->query('exam_id');

        $examsQuery = Exam::query()
            ->with(['course:id,title,title_en'])
            ->withCount([
                'questions',
                'attempts as submitted_attempts_count' => fn ($q) => $q->whereIn('status', [
                    ExamAttempt::STATUS_SUBMITTED,
                    ExamAttempt::STATUS_EXPIRED,
                ]),
            ])
            ->latest('id');

        if ($courseId) {
            $examsQuery->where('course_id', (int) $courseId);
        }

        $exams = $examsQuery->paginate(20)->withQueryString();

        $selected = null;
        $summary = null;
        $attempts = null;

        if ($examId) {
            $selected = Exam::query()
                ->with(['course:id,title,title_en', 'gradeBands'])
                ->find((int) $examId);

            if ($selected) {
                $summary = $this->exams->reportSummary($selected);
                $attempts = ExamAttempt::query()
                    ->where('exam_id', $selected->id)
                    ->whereIn('status', [ExamAttempt::STATUS_SUBMITTED, ExamAttempt::STATUS_EXPIRED])
                    ->with(['user:id,name,email'])
                    ->latest('submitted_at')
                    ->paginate(30, ['*'], 'attempts_page')
                    ->withQueryString();
            }
        }

        return Inertia::render('Admin/Exams/Reports', [
            'exams' => $exams,
            'courses' => $this->courseOptions(),
            'course_id' => $courseId ? (int) $courseId : null,
            'exam_id' => $examId ? (int) $examId : null,
            'selected' => $selected,
            'summary' => $summary,
            'attempts' => $attempts,
        ]);
    }

    public function attemptShow(Exam $exam, ExamAttempt $attempt): Response
    {
        abort_unless((int) $attempt->exam_id === (int) $exam->id, 404);

        $attempt->load(['user:id,name,email', 'answers.question.options', 'answers.selectedOption']);
        $exam->load(['gradeBands', 'course:id,title,title_en']);

        return Inertia::render('Admin/Exams/Attempt', [
            'exam' => $exam,
            'attempt' => $attempt,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'description_en' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in([Exam::STATUS_DRAFT, Exam::STATUS_PUBLISHED, Exam::STATUS_ARCHIVED])],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'pass_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:100'],
            'shuffle_questions' => ['sometimes', 'boolean'],
            'shuffle_options' => ['sometimes', 'boolean'],
            'show_correct_answers' => ['sometimes', 'boolean'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after_or_equal:available_from'],
            'grade_bands' => ['required', 'array', 'min:1'],
            'grade_bands.*.min_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'grade_bands.*.max_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'grade_bands.*.label' => ['required', 'string', 'max:100'],
            'grade_bands.*.label_en' => ['nullable', 'string', 'max:100'],
            'grade_bands.*.color' => ['nullable', 'string', 'max:32'],
            'grade_bands.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'questions' => ['nullable', 'array'],
            'questions.*.type' => ['required', Rule::in([
                'multiple_choice',
                'true_false',
                'short_answer',
            ])],
            'questions.*.prompt' => ['required', 'string', 'max:5000'],
            'questions.*.prompt_en' => ['nullable', 'string', 'max:5000'],
            'questions.*.points' => ['required', 'numeric', 'min:0.25', 'max:100'],
            'questions.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'questions.*.explanation' => ['nullable', 'string', 'max:5000'],
            'questions.*.explanation_en' => ['nullable', 'string', 'max:5000'],
            'questions.*.case_sensitive' => ['sometimes', 'boolean'],
            'questions.*.correct_true' => ['sometimes', 'boolean'],
            'questions.*.accepted_answers' => ['nullable', 'array'],
            'questions.*.accepted_answers.*' => ['string', 'max:500'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*.label' => ['required_with:questions.*.options', 'string', 'max:500'],
            'questions.*.options.*.label_en' => ['nullable', 'string', 'max:500'],
            'questions.*.options.*.is_correct' => ['sometimes', 'boolean'],
            'questions.*.options.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        foreach ($data['questions'] ?? [] as $i => $q) {
            $type = $q['type'];
            if ($type === 'short_answer') {
                $accepted = array_values(array_filter(array_map('trim', $q['accepted_answers'] ?? [])));
                if ($accepted === []) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "questions.$i.accepted_answers" => [__('admin.exams.validation.short_answer_required')],
                    ]);
                }
                $data['questions'][$i]['accepted_answers'] = $accepted;
            }

            if ($type === 'multiple_choice') {
                $opts = $q['options'] ?? [];
                if (count($opts) < 2) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "questions.$i.options" => [__('admin.exams.validation.mcq_options')],
                    ]);
                }
                $correct = collect($opts)->where('is_correct', true)->count();
                if ($correct < 1) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "questions.$i.options" => [__('admin.exams.validation.mcq_correct')],
                    ]);
                }
            }
        }

        return $data;
    }

    /**
     * @return list<array{id: int, title: string, localized_title: string}>
     */
    private function courseOptions(): array
    {
        return Course::query()
            ->orderByDesc('id')
            ->get(['id', 'title', 'title_en'])
            ->map(fn (Course $c) => [
                'id' => $c->id,
                'title' => $c->title,
                'localized_title' => $c->localized_title,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function examPayload(Exam $exam): array
    {
        return [
            'id' => $exam->id,
            'course_id' => $exam->course_id,
            'title' => $exam->title,
            'title_en' => $exam->title_en,
            'description' => $exam->description,
            'description_en' => $exam->description_en,
            'status' => $exam->status,
            'duration_minutes' => $exam->duration_minutes,
            'pass_percent' => $exam->pass_percent,
            'max_attempts' => $exam->max_attempts,
            'shuffle_questions' => $exam->shuffle_questions,
            'shuffle_options' => $exam->shuffle_options,
            'show_correct_answers' => $exam->show_correct_answers,
            'available_from' => $exam->available_from?->format('Y-m-d\TH:i'),
            'available_until' => $exam->available_until?->format('Y-m-d\TH:i'),
            'grade_bands' => $exam->gradeBands->map(fn ($b) => [
                'min_percent' => $b->min_percent,
                'max_percent' => $b->max_percent,
                'label' => $b->label,
                'label_en' => $b->label_en,
                'color' => $b->color,
                'sort_order' => $b->sort_order,
            ])->values()->all(),
            'questions' => $exam->questions->map(function ($q) {
                $correctTrue = true;
                if ($q->type === 'true_false') {
                    $trueOpt = $q->options->first(fn ($o) => in_array(mb_strtolower($o->label_en ?? ''), ['true'], true)
                        || in_array($o->label, ['صح', 'صحيح'], true));
                    $correctTrue = $trueOpt ? (bool) $trueOpt->is_correct : true;
                }

                return [
                    'type' => $q->type,
                    'prompt' => $q->prompt,
                    'prompt_en' => $q->prompt_en,
                    'points' => $q->points,
                    'sort_order' => $q->sort_order,
                    'explanation' => $q->explanation,
                    'explanation_en' => $q->explanation_en,
                    'case_sensitive' => $q->case_sensitive,
                    'accepted_answers' => $q->accepted_answers ?? [],
                    'correct_true' => $correctTrue,
                    'options' => $q->options->map(fn ($o) => [
                        'label' => $o->label,
                        'label_en' => $o->label_en,
                        'is_correct' => $o->is_correct,
                        'sort_order' => $o->sort_order,
                    ])->values()->all(),
                ];
            })->values()->all(),
            'course' => $exam->course,
        ];
    }
}
