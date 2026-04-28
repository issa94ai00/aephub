<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseVideo;
use App\Models\Faq;
use App\Models\HomeCarouselSlide;
use App\Models\University;
use App\Support\SiteInertiaPresenter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $latestCourses = Course::query()
            ->with(['teacher:id,name'])
            ->withCount(['videos', 'enrollments'])
            ->where('status', 'published')
            ->latest('id')
            ->take(6)
            ->get();

        $featuredCourse = $latestCourses->first();

        $publishedScope = fn ($q) => $q->where('status', 'published');

        $stats = [
            'courses' => Course::query()->where('status', 'published')->count(),
            'videos' => CourseVideo::query()->whereHas('course', $publishedScope)->count(),
            'students' => CourseEnrollment::query()
                ->where('status', 'approved')
                ->whereHas('course', $publishedScope)
                ->count(),
        ];

        $universities = University::query()
            ->withCount('faculties')
            ->orderBy('name')
            ->get();

        $carouselSlides = $this->carouselSlidesForHome();

        return Inertia::render('Site/Home', [
            'carouselSlides' => $carouselSlides,
            'stats' => $stats,
            'featuredCourse' => $featuredCourse ? SiteInertiaPresenter::courseCard($featuredCourse) : null,
            'latestCourses' => $latestCourses
                ->map(fn (Course $c) => SiteInertiaPresenter::courseCard($c))
                ->values()
                ->all(),
            'faqs' => $this->faqsForSite(),
            'universities' => $universities
                ->map(fn (University $u) => SiteInertiaPresenter::universityListItem($u))
                ->values()
                ->all(),
        ]);
    }

    public function faq(): Response
    {
        return Inertia::render('Site/Faq', [
            'faqs' => $this->faqsForSite(),
        ]);
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function faqsForSite(): array
    {
        return Faq::query()->ordered()->get()
            ->map(fn (Faq $f) => [
                'question' => $f->localized_question,
                'answer' => $f->localized_answer,
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{src: string, title: string, subtitle: string}>
     */
    private function carouselSlidesForHome(): Collection
    {
        if (! Schema::hasTable('home_carousel_slides')) {
            return collect();
        }

        return HomeCarouselSlide::query()
            ->active()
            ->ordered()
            ->get()
            ->map(function (HomeCarouselSlide $s) {
                $url = $s->resolvedImageUrl();
                if ($url === '') {
                    return null;
                }

                return [
                    'src' => $url,
                    'title' => $s->localizedTitle(),
                    'subtitle' => $s->localizedSubtitle(),
                ];
            })
            ->filter()
            ->values();
    }

    public function showUniversity(University $university): Response
    {
        $university->loadCount('faculties');
        $faculties = $university->faculties()
            ->withCount('studyYears')
            ->orderBy('name')
            ->get();

        return Inertia::render('Site/UniversityShow', [
            'university' => [
                'id' => $university->id,
                'localized_name' => $university->localized_name,
            ],
            'faculties' => $faculties
                ->map(fn ($f) => SiteInertiaPresenter::facultyCard($f))
                ->values()
                ->all(),
        ]);
    }

    public function androidDownload(): Response
    {
        $apkRelativePath = 'downloads/lms-android.apk';
        $apkPath = public_path($apkRelativePath);
        $androidApkUrl = file_exists($apkPath) ? asset($apkRelativePath) : '';

        return Inertia::render('Site/AndroidDownload', [
            'androidApkUrl' => $androidApkUrl,
        ]);
    }

    public function showCourse(Course $course): Response
    {
        if ($course->status !== 'published') {
            abort(404);
        }

        $course->load(['teacher:id,name'])->loadCount(['videos', 'enrollments']);

        $relatedCourses = Course::query()
            ->where('status', 'published')
            ->whereKeyNot($course->id)
            ->with(['teacher:id,name'])
            ->withCount(['videos', 'enrollments'])
            ->latest('id')
            ->take(3)
            ->get();

        return Inertia::render('Site/CourseShow', [
            'course' => SiteInertiaPresenter::courseCard($course),
            'relatedCourses' => $relatedCourses
                ->map(fn (Course $c) => SiteInertiaPresenter::relatedCourseCard($c))
                ->values()
                ->all(),
        ]);
    }
}
