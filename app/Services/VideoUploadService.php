<?php

namespace App\Services;

use App\Models\CourseFile;
use App\Models\CourseVideo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoUploadService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_FAILED = 'failed';

    public function resolveBackingCourseFilePath(int $expectedCourseId, string $storagePath): ?CourseFile
    {
        $storagePath = trim($storagePath);
        if (preg_match('#^https?://#i', $storagePath)) {
            $parsed = parse_url($storagePath);
            $storagePath = (string) ($parsed['path'] ?? $storagePath);
        }

        if (!preg_match('#^/api/v1/courses/(\d+)/files/(\d+)/download$#', $storagePath, $matches)) {
            return null;
        }

        $courseId = (int) $matches[1];
        $fileId = (int) $matches[2];
        if ($courseId !== $expectedCourseId) {
            return null;
        }

        $file = CourseFile::query()->find($fileId);
        if (! $file || (int) $file->course_id !== $courseId) {
            return null;
        }

        return $file;
    }

    public function processPendingVideo(int $videoId): void
    {
        $video = CourseVideo::find($videoId);
        if (! $video) {
            Log::warning('Course video upload job could not find video record', ['video_id' => $videoId]);
            return;
        }

        if ($video->status === self::STATUS_ACTIVE) {
            return;
        }

        $backingFile = $this->resolveBackingCourseFilePath((int) $video->course_id, (string) $video->storage_path);
        if (! $backingFile) {
            $this->markFailed($video, 'Backing course file reference was invalid or missing.');
            return;
        }

        $disk = (string) ($backingFile->storage_disk ?: config('filesystems.default', 'local'));
        try {
            $diskFs = Storage::disk($disk);
        } catch (\Throwable $exception) {
            $this->markFailed($video, 'Failed to resolve storage disk: '.$exception->getMessage());
            return;
        }

        try {
            if (! $diskFs->exists($backingFile->storage_path)) {
                $this->markFailed($video, 'Backing file was not found in storage.');
                return;
            }
        } catch (\Throwable $exception) {
            $this->markFailed($video, 'Could not verify backing file existence: '.$exception->getMessage());
            return;
        }

        $needsSave = false;
        if ($video->size_bytes === null && $backingFile->size_bytes !== null) {
            $video->size_bytes = $backingFile->size_bytes;
            $needsSave = true;
        }

        if ($video->mime_type === null && $backingFile->mime_type !== null) {
            $video->mime_type = $backingFile->mime_type;
            $needsSave = true;
        }

        if ($video->status !== self::STATUS_ACTIVE) {
            $video->status = self::STATUS_ACTIVE;
            $needsSave = true;
        }

        if ($needsSave) {
            $video->save();
        }
    }

    private function markFailed(CourseVideo $video, string $reason): void
    {
        $video->status = self::STATUS_FAILED;
        $video->save();

        Log::error('Course video upload processing failed', [
            'video_id' => $video->id,
            'course_id' => $video->course_id,
            'reason' => $reason,
        ]);
    }
}
