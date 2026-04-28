<?php

namespace App\Support;

use App\Models\Course;
use App\Models\CourseFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Same client contract as Wasabi/R2: object key + optional public URL + time-limited direct byte URL.
 * For S3-compatible disks uses Storage::temporaryUrl; for local/public uses a Laravel signed app URL.
 */
final class CourseVideoBlobUrls
{
    /**
     * @return array{wasabi_object_key: string, wasabi_url: string|null, wasabi_temporary_url: string|null}
     */
    public static function wasabiStyleFields(Course $course, CourseFile $file): array
    {
        $disk = (string) ($file->storage_disk ?: config('filesystems.default', 'local'));
        $path = $file->storage_path;

        return [
            'wasabi_object_key' => $path,
            'wasabi_url' => self::s3PublicUrl($disk, $path),
            'wasabi_temporary_url' => self::temporaryBlobUrl($course, $file),
        ];
    }

    private static function s3PublicUrl(string $disk, string $path): ?string
    {
        if (! in_array($disk, ['wasabi', 'r2', 's3'], true)) {
            return null;
        }
        try {
            return Storage::disk($disk)->url($path);
        } catch (Throwable) {
            return null;
        }
    }

    private static function temporaryBlobUrl(Course $course, CourseFile $file): ?string
    {
        $disk = (string) ($file->storage_disk ?: config('filesystems.default', 'local'));
        $path = $file->storage_path;
        $ttl = now()->addMinutes(max(5, (int) config('media_chunking.stream.signed_temp_url_ttl_minutes', 30)));

        if (in_array($disk, ['wasabi', 'r2', 's3'], true)) {
            try {
                return Storage::disk($disk)->temporaryUrl($path, $ttl);
            } catch (Throwable) {
                return null;
            }
        }

        if (in_array($disk, ['local', 'public'], true)) {
            try {
                return URL::temporarySignedRoute(
                    'api.v1.courses.files.stream-signed',
                    $ttl,
                    ['course' => $course->id, 'file' => $file->id]
                );
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }
}
