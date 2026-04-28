<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseFile;
use App\Models\CourseVideo;
use App\Support\CourseVideoBlobUrls;
use App\Support\LocalEncryptedBlobRangeResponse;
use App\Support\MediaChunkingHints;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    public function store(Request $request, Course $course): JsonResponse
    {
        $this->authorize('create', [CourseVideo::class, $course]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'storage_disk' => ['nullable', 'string', 'in:local,wasabi,s3,r2'],
            'storage_path' => ['required', 'string', 'max:2048'],
            'size_bytes' => ['nullable', 'integer', 'min:0'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'cipher' => ['required', 'string', 'in:AES-128-CBC'],
            'content_key' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $decoded = base64_decode((string) $value, true);
                    if ($decoded === false || strlen($decoded) !== 16) {
                        $fail($attribute.' must be a valid Base64-encoded 16-byte key.');
                    }
                },
            ],
            'content_iv' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $decoded = base64_decode((string) $value, true);
                    if ($decoded === false || strlen($decoded) !== 16) {
                        $fail($attribute.' must be a valid Base64-encoded 16-byte IV.');
                    }
                },
            ],
            'key_version' => ['nullable', 'string', 'max:32'],
            'encrypted_sha256' => ['nullable', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/i'],
        ]);

        // Accept relative API path, even if client accidentally sends a full URL.
        $storagePath = (string) $data['storage_path'];
        if (preg_match('#^https?://#i', $storagePath)) {
            $parsed = parse_url($storagePath);
            $storagePath = (string) ($parsed['path'] ?? $storagePath);
        }

        // Keep as relative API path only (no base URL) and ensure it points to this course.
        $expectedPrefix = "/api/v1/courses/{$course->id}/files/";
        if (!str_starts_with($storagePath, $expectedPrefix)) {
            return response()->json([
                'message' => 'storage_path must be a relative download path for this course',
                'expected_prefix' => $expectedPrefix,
            ], 422);
        }

        $file = $this->resolveBackingCourseFilePath($course->id, $storagePath);
        if (!$file) {
            return response()->json([
                'message' => 'storage_path must reference an existing file for this course',
            ], 422);
        }

        $video = CourseVideo::create([
            'course_id' => $course->id,
            'title' => $data['title'],
            'title_en' => $data['title_en'] ?? null,
            'description' => $data['description'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'storage_disk' => $file->storage_disk ?: (string) config('filesystems.default', 'local'),
            'storage_path' => $storagePath,
            'size_bytes' => $data['size_bytes'] ?? null,
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'mime_type' => $data['mime_type'] ?? null,
            'encryption_cipher' => $data['cipher'],
            'encrypted_content_key' => Crypt::encryptString($data['content_key']),
            'content_iv' => $data['content_iv'],
            'key_version' => $data['key_version'] ?? 'v1',
            'encrypted_sha256' => $data['encrypted_sha256'] ?? null,
            'status' => 'active',
        ]);

        return response()->json([
            'video' => [
                'id' => $video->id,
                'course_id' => $video->course_id,
                'title' => $video->title,
                'title_en' => $video->title_en,
                'localized_title' => $video->localized_title,
                'description' => $video->description,
                'description_en' => $video->description_en,
                'localized_description' => $video->localized_description,
                'storage_path' => $video->storage_path,
                'size_bytes' => $video->size_bytes,
                'cipher' => $video->encryption_cipher,
                'encrypted_sha256' => $video->encrypted_sha256,
            ],
        ], 201);
    }

    public function show(CourseVideo $video): JsonResponse
    {
        $this->authorize('view', $video);

        $course = Course::query()->find($video->course_id);
        $backingFile = $this->resolveBackingCourseFile($video);
        $blobFields = [
            'wasabi_object_key' => null,
            'wasabi_url' => null,
            'wasabi_temporary_url' => null,
        ];
        if ($course !== null && $backingFile !== null) {
            $blobFields = CourseVideoBlobUrls::wasabiStyleFields($course, $backingFile);
        }

        return response()->json([
            'video' => array_merge([
                'id' => $video->id,
                'course_id' => $video->course_id,
                'title' => $video->title,
                'title_en' => $video->title_en,
                'localized_title' => $video->localized_title,
                'description' => $video->description,
                'description_en' => $video->description_en,
                'localized_description' => $video->localized_description,
                'storage_disk' => $video->storage_disk,
                'storage_path' => $video->storage_path,
                /** Preferred URL for encrypted bytes (Range + JWT); see `GET /videos/{id}/encrypted`. */
                'encrypted_stream_path' => '/api/v1/videos/'.$video->id.'/encrypted',
                'size_bytes' => $video->size_bytes,
                'duration_seconds' => $video->duration_seconds,
                'mime_type' => $video->mime_type,
                'encryption_cipher' => $video->encryption_cipher,
                'key_version' => $video->key_version,
                'encrypted_sha256' => $video->encrypted_sha256,
                'status' => $video->status,
                'playback' => MediaChunkingHints::playbackHints(
                    $video->size_bytes !== null ? (int) $video->size_bytes : null
                ),
            ], $blobFields),
        ]);
    }

    /**
     * Delete course video metadata; removes backing CourseFile from storage when no other video shares the same storage_path.
     */
    public function destroy(Course $course, CourseVideo $video): JsonResponse
    {
        if ((int) $video->course_id !== (int) $course->id) {
            abort(404, 'Video not found for this course.');
        }

        $this->authorize('delete', $video);

        $backingFile = $this->resolveBackingCourseFile($video);
        $storagePathRef = (string) $video->storage_path;
        $videoId = (int) $video->id;

        $video->delete();

        if ($backingFile !== null) {
            $stillReferenced = CourseVideo::query()
                ->where('storage_path', $storagePathRef)
                ->exists();

            if (! $stillReferenced) {
                $disk = (string) ($backingFile->storage_disk ?: config('filesystems.default', 'local'));
                if ($backingFile->storage_path && Storage::disk($disk)->exists($backingFile->storage_path)) {
                    Storage::disk($disk)->delete($backingFile->storage_path);
                }
                $backingFile->delete();
            }
        }

        return response()->json([
            'deleted' => true,
            'video_id' => $videoId,
        ]);
    }

    /**
     * Stream encrypted video bytes (same blob as linked course file). Supports HTTP Range for progressive download / ExoPlayer.
     */
    public function encryptedStream(Request $request, CourseVideo $video)
    {
        $this->authorize('view', $video);

        if ($video->status !== 'active') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $file = $this->resolveBackingCourseFile($video);
        if (!$file) {
            return response()->json([
                'message' => 'Video encrypted blob is not available via this endpoint',
                'hint' => 'storage_path must be /api/v1/courses/{courseId}/files/{fileId}/download matching this video course_id',
            ], 422);
        }

        $disk = (string) ($file->storage_disk ?: config('filesystems.default', 'local'));
        try {
            $diskFs = Storage::disk($disk);
        } catch (\Throwable $e) {
            Log::warning('Encrypted stream invalid storage disk', [
                'video_id' => $video->id,
                'file_id' => $file->id,
                'disk' => $disk,
                'storage_path' => $file->storage_path,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Invalid storage disk configured for this video'], 422);
        }

        try {
            $exists = $diskFs->exists($file->storage_path);
        } catch (\Throwable $e) {
            Log::warning('Encrypted stream existence check failed', [
                'video_id' => $video->id,
                'file_id' => $file->id,
                'disk' => $disk,
                'storage_path' => $file->storage_path,
                'range' => (string) $request->header('Range', ''),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Storage is temporarily unavailable'], 502);
        }

        if (!$exists) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $disposition = strtolower((string) $request->query('disposition', 'inline'));
        $asAttachment = $disposition === 'attachment';
        $safeTitle = preg_replace('/[^\p{L}\p{N}\-_\. ]/u', '_', (string) $video->localized_title) ?: 'video';
        $filename = $safeTitle.'.encrypted.bin';

        if ($request->isMethod('HEAD')) {
            $size = $file->size_bytes;
            if ($size === null || $size < 0) {
                try {
                    $size = (int) $diskFs->size($file->storage_path);
                } catch (\Throwable $e) {
                    Log::warning('Encrypted stream HEAD size failed', [
                        'video_id' => $video->id,
                        'file_id' => $file->id,
                        'disk' => $disk,
                        'storage_path' => $file->storage_path,
                        'error' => $e->getMessage(),
                    ]);
                    $size = 0;
                }
            }

            $align = (int) config('media_chunking.stream.cipher_block_bytes', 16);
            $sug = (int) config('media_chunking.stream.suggested_range_request_bytes', 1024 * 1024);
            $sug = max($align, (int) (floor($sug / $align) * $align));

            return response('', 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => (string) $size,
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'private, no-store',
                'X-Encrypted-Blob-Block-Align' => (string) $align,
                'X-Suggested-Range-Bytes' => (string) $sug,
            ]);
        }

        return LocalEncryptedBlobRangeResponse::fromStoragePath(
            $request,
            $disk,
            $file->storage_path,
            $filename,
            $asAttachment,
        );
    }

    private function resolveBackingCourseFile(CourseVideo $video): ?CourseFile
    {
        return $this->resolveBackingCourseFilePath((int) $video->course_id, (string) $video->storage_path);
    }

    private function resolveBackingCourseFilePath(int $expectedCourseId, string $path): ?CourseFile
    {
        $path = trim($path);
        if (!preg_match('#^/api/v1/courses/(\d+)/files/(\d+)/download$#', $path, $m)) {
            return null;
        }
        $courseId = (int) $m[1];
        $fileId = (int) $m[2];
        if ($courseId !== $expectedCourseId) {
            return null;
        }

        $file = CourseFile::query()->find($fileId);
        if (!$file || (int) $file->course_id !== $courseId) {
            return null;
        }

        return $file;
    }
}
