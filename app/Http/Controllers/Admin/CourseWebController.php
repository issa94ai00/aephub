<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\StudyTerm;
use App\Models\User;
use App\Support\AdminInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Response;

class CourseWebController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status');

        $q = Course::query()->with('teacher:id,name')->latest('id');
        if (is_string($status) && $status !== '') {
            $q->where('status', $status);
        }

        $courses = $q->paginate(20)->withQueryString();

        return AdminInertia::frame('admin.courses.index', [
            'courses' => $courses,
            'status' => $status,
            'catalogMode' => false,
        ]);
    }

    /**
     * Courses currently visible to students (published only).
     */
    public function indexStudentCatalog(): Response
    {
        $status = 'published';
        $courses = Course::query()
            ->with('teacher:id,name')
            ->where('status', 'published')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return AdminInertia::frame('admin.courses.index', [
            'courses' => $courses,
            'status' => $status,
            'catalogMode' => true,
        ]);
    }

    public function create(): Response
    {
        $teachers = User::query()
            ->assignableAsTeacher()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $terms = $this->termOptions();

        return AdminInertia::frame('admin.courses.create', compact('teachers', 'terms'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedCourseAttributes($request);
        $course = Course::create($data);
        $course->studyTerms()->sync($data['study_term_ids'] ?? []);
        $this->storeCoverIfPresent($request, $course);

        return redirect()->route('admin.courses.index')->with('status', __('admin.flash.course_created'));
    }

    public function edit(Course $course): Response
    {
        $teachers = User::query()
            ->assignableAsTeacher()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $terms = $this->termOptions();
        $selectedTermIds = $course->studyTerms()->pluck('study_terms.id')->all();

        return AdminInertia::frame('admin.courses.edit', compact('course', 'teachers', 'terms', 'selectedTermIds'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $data = $this->validatedCourseAttributes($request);
        $course->update($data);
        $course->studyTerms()->sync($data['study_term_ids'] ?? []);
        $this->storeCoverIfPresent($request, $course);

        return redirect()->route('admin.courses.index')->with('status', __('admin.flash.course_updated'));
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->delete();

        return redirect()->route('admin.courses.index')->with('status', __('admin.flash.course_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedCourseAttributes(Request $request): array
    {
        $validated = $request->validate([
            'teacher_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($q) {
                    $q->where('role', 'admin')
                        ->orWhere(function ($teacherQ) {
                            $teacherQ->where('role', 'teacher')
                                ->where('teacher_approval_status', User::TEACHER_APPROVAL_APPROVED);
                        });
                }),
            ],
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'max:16'],
            'sham_cash_code' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'cover_image' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
            'study_term_ids' => ['nullable', 'array'],
            'study_term_ids.*' => ['integer', 'exists:study_terms,id'],
        ]);

        unset($validated['cover_image']);

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    private function termOptions(): array
    {
        $terms = StudyTerm::query()
            ->with(['studyYear.faculty.university'])
            ->orderBy('id')
            ->get();

        $options = [];
        foreach ($terms as $t) {
            $u = $t->studyYear?->faculty?->university;
            $f = $t->studyYear?->faculty;
            $y = $t->studyYear;
            $label = trim(implode(' / ', array_filter([
                $u?->name,
                $f?->name,
                $y ? ('Year '.$y->year_number) : null,
                $t->name ?: ('Term '.$t->term_number),
            ])));
            $options[(int) $t->id] = $label !== '' ? $label : ('Term #'.$t->id);
        }

        return $options;
    }

    private function storeCoverIfPresent(Request $request, Course $course): void
    {
        if (! $request->hasFile('cover_image')) {
            return;
        }

        $disk = 'local';
        $path = $request->file('cover_image')->store('course-covers/'.$course->id, $disk);

        if ($course->cover_image_path && Storage::disk($course->cover_image_disk ?: 'local')->exists($course->cover_image_path)) {
            Storage::disk($course->cover_image_disk ?: 'local')->delete($course->cover_image_path);
        }

        $course->forceFill([
            'cover_image_disk' => $disk,
            'cover_image_path' => $path,
        ])->save();
    }
}
