<?php

namespace App\Support;

use App\Models\Course;
use App\Models\CourseFile;
use App\Services\StorageDestinationService;
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
        // Managed destinations are S3-backed under any name, so membership is
        // decided by the resolved driver instead of a fixed list of three.
        $isObjectStorage = in_array($disk, ['wasabi', 'r2', 's3'], true)
            || (string) config("filesystems.disks.{$disk}.driver") === 's3';

        if (! $isObjectStorage) {
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

        // Read from the destination the file actually lives on, so a bucket
        // configured for short-lived links is not handed the platform default.
        // Keyed on the file's own disk rather than the current upload target:
        // an old video still streams from wherever it was stored.
        $destination = app(StorageDestinationService::class)->active()->firstWhere('disk_key', $disk);
        $minutes = (int) ($destination?->option('signed_url_ttl_minutes')
            ?? config('media_chunking.stream.signed_temp_url_ttl_minutes', 30));
        $ttl = now()->addMinutes(max(5, $minutes));

        // Managed destinations are S3-backed under any name, so membership is
        // decided by the resolved driver instead of a fixed list of three.
        $isObjectStorage = in_array($disk, ['wasabi', 'r2', 's3'], true)
            || (string) config("filesystems.disks.{$disk}.driver") === 's3';

        if ($isObjectStorage) {
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
