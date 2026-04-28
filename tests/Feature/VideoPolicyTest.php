<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseVideo;
use App\Models\User;
use App\Policies\VideoPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_video_when_enrollment_is_approved(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'teacher_approval_status' => User::TEACHER_APPROVAL_APPROVED,
        ]);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'LMS Security',
            'status' => 'published',
        ]);

        $video = CourseVideo::create([
            'course_id' => $course->id,
            'title' => 'Lecture 1',
            'storage_disk' => 's3',
            'storage_path' => 'course-files/1/lecture-1.enc',
            'encryption_cipher' => 'AES-128-CBC',
            'encrypted_content_key' => 'encrypted-key',
            'content_iv' => 'base64-iv',
            'key_version' => 'v1',
            'status' => 'active',
        ]);

        CourseEnrollment::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 'approved',
        ]);

        $policy = new VideoPolicy;

        $this->assertTrue($policy->view($student, $video));
    }

    public function test_unenrolled_student_cannot_view_video(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'teacher_approval_status' => User::TEACHER_APPROVAL_APPROVED,
        ]);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'LMS Security',
            'status' => 'published',
        ]);

        $video = CourseVideo::create([
            'course_id' => $course->id,
            'title' => 'Lecture 2',
            'storage_disk' => 's3',
            'storage_path' => 'course-files/1/lecture-2.enc',
            'encryption_cipher' => 'AES-128-CBC',
            'encrypted_content_key' => 'encrypted-key',
            'content_iv' => 'base64-iv',
            'key_version' => 'v1',
            'status' => 'active',
        ]);

        $policy = new VideoPolicy;

        $this->assertFalse($policy->view($student, $video));
    }

    public function test_teacher_can_delete_video_on_own_course(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'teacher_approval_status' => User::TEACHER_APPROVAL_APPROVED,
        ]);

        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'LMS Security',
            'status' => 'published',
        ]);

        $video = CourseVideo::create([
            'course_id' => $course->id,
            'title' => 'Lecture 1',
            'storage_disk' => 's3',
            'storage_path' => 'course-files/1/lecture-1.enc',
            'encryption_cipher' => 'AES-128-CBC',
            'encrypted_content_key' => 'encrypted-key',
            'content_iv' => 'base64-iv',
            'key_version' => 'v1',
            'status' => 'active',
        ]);

        $policy = new VideoPolicy;

        $this->assertTrue($policy->delete($teacher, $video));
    }

    public function test_student_cannot_delete_video(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'teacher_approval_status' => User::TEACHER_APPROVAL_APPROVED,
        ]);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'LMS Security',
            'status' => 'published',
        ]);

        $video = CourseVideo::create([
            'course_id' => $course->id,
            'title' => 'Lecture 1',
            'storage_disk' => 's3',
            'storage_path' => 'course-files/1/lecture-1.enc',
            'encryption_cipher' => 'AES-128-CBC',
            'encrypted_content_key' => 'encrypted-key',
            'content_iv' => 'base64-iv',
            'key_version' => 'v1',
            'status' => 'active',
        ]);

        $policy = new VideoPolicy;

        $this->assertFalse($policy->delete($student, $video));
    }
}
