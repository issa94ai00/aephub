<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CarouselWebController;
use App\Http\Controllers\Admin\FaqWebController;
use App\Http\Controllers\Admin\CourseSessionWebController;
use App\Http\Controllers\Admin\CourseWebController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeviceChangeRequestWebController;
use App\Http\Controllers\Admin\FacultyWebController;
use App\Http\Controllers\Admin\PaymentWebController;
use App\Http\Controllers\Admin\SecurityEventWebController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StudyTermWebController;
use App\Http\Controllers\Admin\StudyYearWebController;
use App\Http\Controllers\Admin\TeacherManagementController;
use App\Http\Controllers\Admin\UniversityWebController;
use App\Http\Controllers\Admin\UserDeviceWebController;
use App\Http\Controllers\Admin\UserWebController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])
    ->where('locale', 'ar|en|auto')
    ->name('locale.switch');

Route::get('/', [HomeController::class, 'index']);
Route::get('/faq', [HomeController::class, 'faq'])->name('faq');
Route::get('/universities/{university}', [HomeController::class, 'showUniversity'])->name('universities.show');
Route::get('/android-download', [HomeController::class, 'androidDownload'])->name('android.download');
Route::get('/courses/{course}', [HomeController::class, 'showCourse'])->name('courses.show');
Route::get('/subscription/register', [RegistrationController::class, 'show'])->name('subscription.register');
Route::post('/subscription/register', [RegistrationController::class, 'store'])->name('subscription.register.store');
Route::get('/legal/privacy-and-terms', [LegalController::class, 'privacyTerms'])->name('legal.privacy-terms');

Route::view('/welcome', 'welcome');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('login', [AdminAuthController::class, 'login'])->name('login.submit');
    });

    Route::middleware(['auth', 'admin.web'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('student-courses', [CourseWebController::class, 'indexStudentCatalog'])->name('courses.student-catalog');

        Route::resource('courses', CourseWebController::class)->except(['show']);

        Route::get('courses/{course}/sessions', [CourseSessionWebController::class, 'index'])->name('courses.sessions.index');
        Route::get('courses/{course}/sessions/create', [CourseSessionWebController::class, 'create'])->name('courses.sessions.create');
        Route::post('courses/{course}/sessions', [CourseSessionWebController::class, 'store'])->name('courses.sessions.store');
        Route::get('courses/{course}/sessions/{session}/edit', [CourseSessionWebController::class, 'edit'])->name('courses.sessions.edit');
        Route::put('courses/{course}/sessions/{session}', [CourseSessionWebController::class, 'update'])->name('courses.sessions.update');
        Route::delete('courses/{course}/sessions/{session}', [CourseSessionWebController::class, 'destroy'])->name('courses.sessions.destroy');
        Route::get('courses/{course}/sessions/{session}/videos', [CourseSessionWebController::class, 'videos'])->name('courses.sessions.videos');
        Route::post('courses/{course}/sessions/{session}/videos', [CourseSessionWebController::class, 'syncVideos'])->name('courses.sessions.videos.sync');

        Route::get('users', [UserWebController::class, 'index'])->name('users.index');
        Route::patch('users/{user}/role', [UserWebController::class, 'updateRole'])->name('users.role');
        Route::post('users/{user}/freeze', [UserWebController::class, 'freeze'])->name('users.freeze');
        Route::post('users/{user}/unfreeze', [UserWebController::class, 'unfreeze'])->name('users.unfreeze');
        Route::get('teachers', [TeacherManagementController::class, 'index'])->name('teachers.index');
        Route::post('teachers/{user}/approve', [TeacherManagementController::class, 'approve'])->name('teachers.approve');
        Route::post('teachers/{user}/reject', [TeacherManagementController::class, 'reject'])->name('teachers.reject');
        Route::post('teachers/courses/{course}/reassign', [TeacherManagementController::class, 'reassignCourseTeacher'])->name('teachers.reassign-course');

        Route::get('payments', [PaymentWebController::class, 'index'])->name('payments.index');
        Route::get('payments/{paymentRequest}', [PaymentWebController::class, 'show'])->name('payments.show');
        Route::post('payments/{paymentRequest}/review', [PaymentWebController::class, 'review'])->name('payments.review');
        Route::get('payments/{paymentRequest}/receipt', [PaymentWebController::class, 'receipt'])->name('payments.receipt');

        Route::get('device-change-requests', [DeviceChangeRequestWebController::class, 'index'])->name('device-change-requests.index');
        Route::post('device-change-requests/{deviceChangeRequest}/review', [DeviceChangeRequestWebController::class, 'review'])->name('device-change-requests.review');

        Route::get('security-events', [SecurityEventWebController::class, 'index'])->name('security-events.index');
        Route::get('security-events/{securityEvent}', [SecurityEventWebController::class, 'show'])->name('security-events.show');

        Route::post('users/{user}/reset-device', [UserDeviceWebController::class, 'resetDevice'])->name('users.reset-device');

        Route::resource('carousel', CarouselWebController::class)->parameters(['carousel' => 'slide'])->except(['show']);

        Route::resource('faqs', FaqWebController::class)->except(['show']);

        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/clear-cache', [SettingsController::class, 'clearCache'])->name('settings.clear-cache');

        // Academics (Universities -> Faculties -> Years -> Terms)
        Route::prefix('academics')->name('academics.')->group(function () {
            Route::resource('universities', UniversityWebController::class)->except(['show']);

            Route::get('universities/{university}/faculties', [FacultyWebController::class, 'index'])->name('universities.faculties.index');
            Route::get('universities/{university}/faculties/create', [FacultyWebController::class, 'create'])->name('universities.faculties.create');
            Route::post('universities/{university}/faculties', [FacultyWebController::class, 'store'])->name('universities.faculties.store');
            Route::get('universities/{university}/faculties/{faculty}/edit', [FacultyWebController::class, 'edit'])->name('universities.faculties.edit');
            Route::put('universities/{university}/faculties/{faculty}', [FacultyWebController::class, 'update'])->name('universities.faculties.update');
            Route::delete('universities/{university}/faculties/{faculty}', [FacultyWebController::class, 'destroy'])->name('universities.faculties.destroy');

            Route::get('universities/{university}/faculties/{faculty}/years', [StudyYearWebController::class, 'index'])->name('universities.faculties.years.index');
            Route::get('universities/{university}/faculties/{faculty}/years/create', [StudyYearWebController::class, 'create'])->name('universities.faculties.years.create');
            Route::post('universities/{university}/faculties/{faculty}/years', [StudyYearWebController::class, 'store'])->name('universities.faculties.years.store');
            Route::get('universities/{university}/faculties/{faculty}/years/{year}/edit', [StudyYearWebController::class, 'edit'])->name('universities.faculties.years.edit');
            Route::put('universities/{university}/faculties/{faculty}/years/{year}', [StudyYearWebController::class, 'update'])->name('universities.faculties.years.update');
            Route::delete('universities/{university}/faculties/{faculty}/years/{year}', [StudyYearWebController::class, 'destroy'])->name('universities.faculties.years.destroy');

            Route::get('universities/{university}/faculties/{faculty}/years/{year}/terms', [StudyTermWebController::class, 'index'])->name('universities.faculties.years.terms.index');
            Route::get('universities/{university}/faculties/{faculty}/years/{year}/terms/create', [StudyTermWebController::class, 'create'])->name('universities.faculties.years.terms.create');
            Route::post('universities/{university}/faculties/{faculty}/years/{year}/terms', [StudyTermWebController::class, 'store'])->name('universities.faculties.years.terms.store');
            Route::get('universities/{university}/faculties/{faculty}/years/{year}/terms/{term}/edit', [StudyTermWebController::class, 'edit'])->name('universities.faculties.years.terms.edit');
            Route::put('universities/{university}/faculties/{faculty}/years/{year}/terms/{term}', [StudyTermWebController::class, 'update'])->name('universities.faculties.years.terms.update');
            Route::delete('universities/{university}/faculties/{faculty}/years/{year}/terms/{term}', [StudyTermWebController::class, 'destroy'])->name('universities.faculties.years.terms.destroy');
        });
    });
});
