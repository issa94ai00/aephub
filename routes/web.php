<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CarouselWebController;
use App\Http\Controllers\Admin\FaqWebController;
use App\Http\Controllers\Admin\CourseSessionWebController;
use App\Http\Controllers\Admin\CourseWebController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeviceChangeRequestWebController;
use App\Http\Controllers\Admin\ExamWebController;
use App\Http\Controllers\Admin\FacultyWebController;
use App\Http\Controllers\Admin\PaymentWebController;
use App\Http\Controllers\Admin\QueueWorkerController;
use App\Http\Controllers\Admin\SecurityEventWebController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StorageSettingsWebController;
use App\Http\Controllers\Admin\StudyTermWebController;
use App\Http\Controllers\Admin\StudyYearWebController;
use App\Http\Controllers\Admin\TeacherManagementController;
use App\Http\Controllers\Admin\UniversityWebController;
use App\Http\Controllers\Admin\UserDeviceWebController;
use App\Http\Controllers\Admin\UserReportsController;
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
        Route::get('statistics', [DashboardController::class, 'statistics'])->name('statistics');

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

        Route::get('exams', [ExamWebController::class, 'index'])->name('exams.index');
        Route::get('exams/reports', [ExamWebController::class, 'reports'])->name('exams.reports');
        Route::get('exams/create', [ExamWebController::class, 'create'])->name('exams.create');
        Route::post('exams', [ExamWebController::class, 'store'])->name('exams.store');
        Route::get('exams/{exam}/edit', [ExamWebController::class, 'edit'])->name('exams.edit');
        Route::put('exams/{exam}', [ExamWebController::class, 'update'])->name('exams.update');
        Route::delete('exams/{exam}', [ExamWebController::class, 'destroy'])->name('exams.destroy');
        Route::get('exams/{exam}/attempts/{attempt}', [ExamWebController::class, 'attemptShow'])->name('exams.attempts.show');

        Route::get('users', [UserWebController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserWebController::class, 'create'])->name('users.create');
        Route::post('users', [UserWebController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserWebController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserWebController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserWebController::class, 'destroy'])->name('users.destroy');
        Route::post('users/bulk-delete', [UserWebController::class, 'bulkDestroy'])->name('users.bulk-destroy');
        Route::patch('users/{user}/role', [UserWebController::class, 'updateRole'])->name('users.role');
        Route::post('users/{user}/freeze', [UserWebController::class, 'freeze'])->name('users.freeze');
        Route::post('users/{user}/unfreeze', [UserWebController::class, 'unfreeze'])->name('users.unfreeze');
        Route::get('user-reports', [UserReportsController::class, 'index'])->name('user-reports.index');
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
        Route::post('settings/rotate-encryption-key', [SettingsController::class, 'rotateEncryptionKey'])->name('settings.rotate-encryption-key');
        Route::post('settings/clear-cache', [SettingsController::class, 'clearCache'])->name('settings.clear-cache');

        // Storage destinations: where uploaded videos and course files are kept,
        // and which destination new uploads go to.
        Route::get('storage-settings', [StorageSettingsWebController::class, 'index'])->name('storage-settings.index');
        Route::post('storage-settings', [StorageSettingsWebController::class, 'store'])->name('storage-settings.store');

        // Capacity, health and housekeeping. Keyed by disk rather than by
        // destination id: `local` is a real destination with no row behind it,
        // and these three screens have to work for it too.
        Route::post('storage-settings/check-all', [StorageSettingsWebController::class, 'checkAll'])->name('storage-settings.check-all');
        Route::get('storage-settings/disks/{disk}/browse', [StorageSettingsWebController::class, 'browse'])->name('storage-settings.browse');
        Route::delete('storage-settings/disks/{disk}/cleanup', [StorageSettingsWebController::class, 'cleanup'])->name('storage-settings.cleanup');

        // Moving everything off a destination so it can be retired.
        Route::post('storage-settings/transfers', [StorageSettingsWebController::class, 'startTransfer'])->name('storage-settings.transfers.start');
        Route::post('storage-settings/transfers/{transfer}/cancel', [StorageSettingsWebController::class, 'cancelTransfer'])->name('storage-settings.transfers.cancel');

        // Declared after the literal segments above so `check-all` and
        // `transfers` are not swallowed by the `{destination}` wildcard.
        Route::put('storage-settings/{destination}', [StorageSettingsWebController::class, 'update'])->name('storage-settings.update');
        Route::post('storage-settings/{destination}/default', [StorageSettingsWebController::class, 'makeDefault'])->name('storage-settings.default');
        Route::post('storage-settings/{destination}/test', [StorageSettingsWebController::class, 'test'])->name('storage-settings.test');
        Route::delete('storage-settings/{destination}', [StorageSettingsWebController::class, 'destroy'])->name('storage-settings.destroy');

        Route::get('queue-workers', [QueueWorkerController::class, 'index'])->name('queue-workers.index');
        Route::post('queue-workers/manage', [QueueWorkerController::class, 'manage'])->name('queue-workers.manage');

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
