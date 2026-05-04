<?php

use App\Http\Controllers\Api\AcademicsController;
use App\Http\Controllers\Api\Admin\AcademicsAdminController;
use App\Http\Controllers\Api\Admin\CourseSessionAdminController;
use App\Http\Controllers\Api\Admin\CourseStudyTermAdminController;
use App\Http\Controllers\Api\AdminTeacherController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseChatController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseFileController;
use App\Http\Controllers\Api\CourseSessionController;
use App\Http\Controllers\Api\DeviceChangeRequestController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\LearningController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PlaybackController;
use App\Http\Controllers\Api\SecurityEventController;
use App\Http\Controllers\Api\SiteSettingsController;
use App\Http\Controllers\Api\UserDeviceAdminController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\VideoProgressController;
use App\Http\Controllers\Api\Admin\SiteSettingsAdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/site-settings', [SiteSettingsController::class, 'show']);

    Route::get('/academics/universities', [AcademicsController::class, 'universities']);
    Route::get('/academics/faculties', [AcademicsController::class, 'faculties']);
    Route::get('/academics/study-years', [AcademicsController::class, 'studyYears']);
    Route::get('/academics/study-terms', [AcademicsController::class, 'studyTerms']);
    Route::get('/academics/study-terms/{studyTerm}/courses', [AcademicsController::class, 'studyTermPublishedCourses']);
    Route::get('/academics/study-term-context', [AcademicsController::class, 'studyTermContext']);

    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:5,1');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPasswordWithCode'])
        ->middleware('throttle:10,1');
    Route::post('/device-change-requests/unauth', [DeviceChangeRequestController::class, 'storeUnauth']);
    Route::get('/courses/{course}/cover', [CourseController::class, 'cover']);

    Route::middleware(['auth.jwt', 'account.freeze', 'device.lock'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::patch('/users/me', [UserProfileController::class, 'updateMe']);
        Route::delete('/users/me', [UserProfileController::class, 'deleteMe'])
            ->middleware('role:student');
        Route::post('/users/me/password', [UserProfileController::class, 'updatePassword']);
        Route::patch('/users/me/academic-profile', [UserProfileController::class, 'updateAcademic'])
            ->middleware('role:student');
        Route::patch('/users/me/sham-cash-code', [UserProfileController::class, 'updateShamCashCode'])
            ->middleware('role:student');

        Route::post('/users/me/device-change-requests', [DeviceChangeRequestController::class, 'store']);
        Route::get('/users/me/notifications', [NotificationController::class, 'index']);
        Route::post('/users/me/notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::get('/users/me/learning', [LearningController::class, 'index']);

        Route::get('/courses', [CourseController::class, 'index']);
        Route::get('/courses/{course}', [CourseController::class, 'show']);
        Route::get('/courses/{course}/sessions', [CourseSessionController::class, 'index'])->middleware('role:student');
        Route::post('/courses/{course}/sessions/{session}/attend', [CourseSessionController::class, 'attend'])->middleware('role:student');
        Route::post('/courses/{course}/cover', [CourseController::class, 'uploadCover'])
            ->middleware('role:teacher,admin');
        Route::get('/courses/{course}/students', [CourseController::class, 'students'])
            ->middleware('role:teacher,admin');

        // Teacher course management (no delete)
        Route::get('/teacher/courses', [CourseController::class, 'teacherIndex'])
            ->middleware('role:teacher');
        Route::post('/teacher/courses', [CourseController::class, 'teacherStore'])
            ->middleware('role:teacher');
        Route::patch('/teacher/courses/{course}', [CourseController::class, 'teacherUpdate'])
            ->middleware('role:teacher');

        Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'requestEnrollment'])
            ->middleware('role:student');
        Route::post('/courses/{course}/enroll/express', [EnrollmentController::class, 'expressEnroll'])
            ->middleware('role:student');
        Route::post('/courses/{course}/enroll/approve', [EnrollmentController::class, 'approve'])
            ->middleware('role:teacher,admin');
        Route::post('/courses/{course}/enrollments/lock', [EnrollmentController::class, 'lockAccess'])
            ->middleware('role:teacher,admin');
        Route::post('/courses/{course}/enrollments/unlock', [EnrollmentController::class, 'unlockAccess'])
            ->middleware('role:teacher,admin');

        Route::post('/payments', [PaymentController::class, 'store'])->middleware('role:student');
        Route::get('/users/me/payments', [PaymentController::class, 'studentIndex'])->middleware('role:student');
        Route::get('/teacher/payments', [PaymentController::class, 'teacherIndex'])->middleware('role:teacher');
        Route::get('/teacher/payments/{paymentRequest}', [PaymentController::class, 'teacherShow'])->middleware('role:teacher');
        Route::get('/teacher/payments/{paymentRequest}/receipt', [PaymentController::class, 'teacherReceipt'])->middleware('role:teacher');
        Route::get('/admin/payments', [PaymentController::class, 'adminIndex'])->middleware('role:admin');
        Route::get('/admin/payments/{paymentRequest}', [PaymentController::class, 'adminShow'])->middleware('role:admin');
        Route::post('/admin/payments/{paymentRequest}/review', [PaymentController::class, 'adminReview'])->middleware('role:admin');
        Route::get('/admin/payments/{paymentRequest}/receipt', [PaymentController::class, 'adminReceipt'])->middleware('role:admin');

        Route::get('/admin/enrollments', [EnrollmentController::class, 'adminIndex'])->middleware('role:admin');
        Route::get('/admin/enrollments/{enrollment}', [EnrollmentController::class, 'adminShow'])->middleware('role:admin');
        Route::post('/admin/enrollments/{enrollment}/review', [EnrollmentController::class, 'adminReview'])->middleware('role:admin');

        Route::get('/courses/{course}/files', [CourseFileController::class, 'index']);
        Route::get('/courses/{course}/files/{file}', [CourseFileController::class, 'show']);
        Route::get('/courses/{course}/files/{file}/download', [CourseFileController::class, 'download']);
        Route::delete('/courses/{course}/files/{file}', [CourseFileController::class, 'destroy'])
            ->middleware('role:admin');
        Route::post('/courses/{course}/files', [CourseFileController::class, 'store'])
            ->middleware('role:teacher,admin');
        Route::post('/courses/{course}/files/multipart/init', [CourseFileController::class, 'multipartInit'])
            ->middleware('role:teacher,admin');
        Route::post('/courses/{course}/files/multipart/sign-part', [CourseFileController::class, 'multipartSignPart'])
            ->middleware('role:teacher,admin');
        Route::post('/courses/{course}/files/multipart/complete', [CourseFileController::class, 'multipartComplete'])
            ->middleware('role:teacher,admin');
        Route::post('/courses/{course}/files/multipart/abort', [CourseFileController::class, 'multipartAbort'])
            ->middleware('role:teacher,admin');

        Route::get('/courses/{course}/chat', [CourseChatController::class, 'index']);
        Route::post('/courses/{course}/chat', [CourseChatController::class, 'store']);

        Route::get('/videos/{video}/encrypted', [VideoController::class, 'encryptedStream']);
        Route::get('/videos/{video}', [VideoController::class, 'show']);
        Route::post('/videos/{video}/progress', [VideoProgressController::class, 'store']);
        Route::post('/courses/{course}/videos', [VideoController::class, 'store'])
            ->middleware('role:teacher,admin');
        Route::delete('/courses/{course}/videos/{video}', [VideoController::class, 'destroy'])
            ->middleware('role:teacher,admin');

        // Video multipart upload — delegates to CourseFileController (same logic as file multipart)
        Route::post('/courses/{course}/videos/multipart/init', [CourseFileController::class, 'multipartInit'])
            ->middleware('role:teacher,admin');
        Route::post('/courses/{course}/videos/multipart/sign-part', [CourseFileController::class, 'multipartSignPart'])
            ->middleware('role:teacher,admin');
        Route::post('/courses/{course}/videos/multipart/complete', [CourseFileController::class, 'multipartComplete'])
            ->middleware('role:teacher,admin');
        Route::post('/courses/{course}/videos/multipart/abort', [CourseFileController::class, 'multipartAbort'])
            ->middleware('role:teacher,admin');

        Route::post('/videos/{video}/playback/session', [PlaybackController::class, 'createSession'])
            ->middleware('role:student,teacher,admin');

        Route::post('/security/events', [SecurityEventController::class, 'store']);
        Route::get('/admin/security/events', [SecurityEventController::class, 'adminIndex'])->middleware('role:admin');
        Route::get('/admin/security/events/{event}', [SecurityEventController::class, 'adminShow'])->middleware('role:admin');

        Route::get('/admin/device-change-requests', [DeviceChangeRequestController::class, 'adminIndex'])
            ->middleware('role:admin');
        Route::post('/admin/device-change-requests/{deviceChangeRequest}/review', [DeviceChangeRequestController::class, 'adminReview'])
            ->middleware('role:admin');

        Route::post('/admin/users/{user}/reset-device', [AuthController::class, 'adminResetDevice'])
            ->middleware('role:admin');
        Route::get('/admin/users', [AdminUserController::class, 'index'])->middleware('role:admin');
        Route::get('/admin/users/suggest', [AdminUserController::class, 'suggest'])->middleware('role:admin');
        Route::post('/admin/users/freeze', [AdminUserController::class, 'freezeByName'])->middleware('role:admin');
        Route::post('/admin/users/{user}/freeze', [AdminUserController::class, 'freeze'])->middleware('role:admin');
        Route::post('/admin/users/{user}/unfreeze', [AdminUserController::class, 'unfreeze'])->middleware('role:admin');

        Route::get('/admin/teachers/pending', [AdminTeacherController::class, 'pending'])->middleware('role:admin');
        Route::post('/admin/teachers/{user}/approve', [AdminTeacherController::class, 'approve'])->middleware('role:admin');
        Route::post('/admin/teachers/{user}/reject', [AdminTeacherController::class, 'reject'])->middleware('role:admin');
        Route::get('/admin/teachers', [AdminTeacherController::class, 'index'])->middleware('role:admin');

        Route::get('/admin/courses', [CourseController::class, 'adminIndex'])->middleware('role:admin');
        Route::post('/admin/courses', [CourseController::class, 'adminStore'])->middleware('role:admin');
        Route::patch('/admin/courses/{course}', [CourseController::class, 'adminUpdate'])->middleware('role:admin');
        Route::delete('/admin/courses/{course}', [CourseController::class, 'adminDestroy'])->middleware('role:admin');
        Route::post('/admin/courses/{course}/assign-teacher', [CourseController::class, 'adminAssignTeacher'])->middleware('role:admin');

        Route::get('/admin/courses/{course}/study-terms', [CourseStudyTermAdminController::class, 'show'])->middleware('role:admin');
        Route::put('/admin/courses/{course}/study-terms', [CourseStudyTermAdminController::class, 'sync'])->middleware('role:admin');

        Route::middleware('role:teacher,admin')->group(function () {
            Route::get('/admin/courses/{course}/sessions', [CourseSessionAdminController::class, 'index']);
            Route::post('/admin/courses/{course}/sessions', [CourseSessionAdminController::class, 'store']);
            Route::patch('/admin/courses/{course}/sessions/{session}', [CourseSessionAdminController::class, 'update']);
            Route::delete('/admin/courses/{course}/sessions/{session}', [CourseSessionAdminController::class, 'destroy']);
            Route::put('/admin/courses/{course}/sessions/{session}/videos', [CourseSessionAdminController::class, 'syncVideos']);
        });

        Route::middleware('role:admin')->group(function () {
            Route::get('/admin/academics/universities', [AcademicsAdminController::class, 'universitiesIndex']);
            Route::post('/admin/academics/universities', [AcademicsAdminController::class, 'universitiesStore']);
            Route::patch('/admin/academics/universities/{university}', [AcademicsAdminController::class, 'universitiesUpdate']);
            Route::delete('/admin/academics/universities/{university}', [AcademicsAdminController::class, 'universitiesDestroy']);

            Route::get('/admin/academics/universities/{university}/faculties', [AcademicsAdminController::class, 'facultiesIndex']);
            Route::post('/admin/academics/universities/{university}/faculties', [AcademicsAdminController::class, 'facultiesStore']);
            Route::patch('/admin/academics/universities/{university}/faculties/{faculty}', [AcademicsAdminController::class, 'facultiesUpdate']);
            Route::delete('/admin/academics/universities/{university}/faculties/{faculty}', [AcademicsAdminController::class, 'facultiesDestroy']);

            Route::get('/admin/academics/faculties/{faculty}/study-years', [AcademicsAdminController::class, 'studyYearsIndex']);
            Route::post('/admin/academics/faculties/{faculty}/study-years', [AcademicsAdminController::class, 'studyYearsStore']);
            Route::patch('/admin/academics/faculties/{faculty}/study-years/{studyYear}', [AcademicsAdminController::class, 'studyYearsUpdate']);
            Route::delete('/admin/academics/faculties/{faculty}/study-years/{studyYear}', [AcademicsAdminController::class, 'studyYearsDestroy']);

            Route::get('/admin/academics/study-years/{studyYear}/study-terms', [AcademicsAdminController::class, 'studyTermsIndex']);
            Route::post('/admin/academics/study-years/{studyYear}/study-terms', [AcademicsAdminController::class, 'studyTermsStore']);
            Route::patch('/admin/academics/study-years/{studyYear}/study-terms/{studyTerm}', [AcademicsAdminController::class, 'studyTermsUpdate']);
            Route::delete('/admin/academics/study-years/{studyYear}/study-terms/{studyTerm}', [AcademicsAdminController::class, 'studyTermsDestroy']);

            Route::get('/admin/academics/study-terms/{studyTerm}/courses', [AcademicsAdminController::class, 'studyTermCourses']);
            Route::post('/admin/academics/study-terms/{studyTerm}/courses', [AcademicsAdminController::class, 'studyTermAttachCourse']);
            Route::delete('/admin/academics/study-terms/{studyTerm}/courses/{course}', [AcademicsAdminController::class, 'studyTermDetachCourse']);
        });

        Route::get('/admin/users/{user}/devices', [UserDeviceAdminController::class, 'index'])
            ->middleware('role:admin');
        Route::post('/admin/users/{user}/devices/{userDevice}/deactivate', [UserDeviceAdminController::class, 'deactivate'])
            ->middleware('role:admin');
        Route::post('/admin/users/{user}/devices/{userDevice}/activate', [UserDeviceAdminController::class, 'activate'])
            ->middleware('role:admin');
        Route::post('/admin/users/{user}/lock-device', [UserDeviceAdminController::class, 'lockToDevice'])
            ->middleware('role:admin');

        Route::patch('/admin/site-settings', [SiteSettingsAdminController::class, 'update'])
            ->middleware('role:admin');
    });

    // Multipart part uploads — outside JWT group because raw PUTs use part_token instead of Bearer
    Route::put('/courses/{course}/files/multipart/part', [CourseFileController::class, 'multipartPutPart'])
        ->middleware(['auth.multipart_local_part', 'account.freeze']);
    Route::put('/courses/{course}/videos/multipart/part', [CourseFileController::class, 'multipartPutPart'])
        ->middleware(['auth.multipart_local_part', 'account.freeze']);

    Route::middleware(['auth.jwt'])->group(function () {
        Route::post('/videos/playback/key', [PlaybackController::class, 'getKeyForSession'])
            ->middleware('throttle:playback-key');
    });
});
