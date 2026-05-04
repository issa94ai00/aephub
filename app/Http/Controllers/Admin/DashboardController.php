<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseFile;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Support\AdminInertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'courses_total' => Course::count(),
            'courses_published' => Course::where('status', 'published')->count(),
            'users_total' => User::count(),
            'users_by_role' => User::query()
                ->selectRaw('role, count(*) as c')
                ->groupBy('role')
                ->pluck('c', 'role'),
            'enrollments_pending' => CourseEnrollment::where('status', 'pending')->count(),
            'payments_pending' => PaymentRequest::where('status', 'pending')->count(),
        ];

        $recentCourses = Course::query()
            ->with('teacher:id,name')
            ->latest('id')
            ->take(5)
            ->get();

        $recentPayments = PaymentRequest::query()
            ->with(['user:id,name,email', 'course:id,title'])
            ->latest('id')
            ->take(5)
            ->get();

        return AdminInertia::frame('admin.dashboard', compact('stats', 'recentCourses', 'recentPayments'));
    }

    public function statistics(): Response
    {
        // File size per course
        $fileSizeByCourse = CourseFile::query()
            ->selectRaw('course_id, SUM(size_bytes) as total_bytes')
            ->groupBy('course_id')
            ->pluck('total_bytes', 'course_id');

        // Revenue per course (approved payments only)
        $revenueByCourse = PaymentRequest::where('status', 'approved')
            ->selectRaw('course_id, SUM(amount_paid_cents) as total_cents')
            ->groupBy('course_id')
            ->pluck('total_cents', 'course_id');

        // Get all courses with their stats
        $courses = Course::query()
            ->with('teacher:id,name')
            ->get()
            ->map(function (Course $course) use ($fileSizeByCourse, $revenueByCourse) {
                return [
                    'id' => $course->id,
                    'title' => $course->localized_title,
                    'teacher' => $course->teacher?->name,
                    'file_size_bytes' => (int) ($fileSizeByCourse[$course->id] ?? 0),
                    'revenue_cents' => (int) ($revenueByCourse[$course->id] ?? 0),
                    'enrollments_count' => $course->enrollments()->where('status', 'active')->count(),
                ];
            })
            ->sortByDesc('revenue_cents')
            ->values();

        $totalFileSize = $fileSizeByCourse->sum();
        $totalRevenue = $revenueByCourse->sum();

        // Top 10 for charts
        $topFiles = $courses->sortByDesc('file_size_bytes')->take(10)->values();
        $topRevenue = $courses->sortByDesc('revenue_cents')->take(10)->values();

        return AdminInertia::frame('admin.statistics', compact(
            'courses', 'totalFileSize', 'totalRevenue', 'topFiles', 'topRevenue'
        ));
    }
}
