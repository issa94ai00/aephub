<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
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
}
