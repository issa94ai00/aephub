<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseFile;
use App\Support\ApiPagination;
use App\Support\LocalEncryptedBlobRangeResponse;
use App\Support\MediaChunkingHints;
use Aws\S3\S3Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class CourseFileController extends Controller
{
    public function index(Request $request, Course $course): JsonResponse
    {
        $this->authorizeCourseAccess($request, $course);

        $files = CourseFile::query()
            ->where('course_id', $course->id)
            ->latest('id')
            ->paginate(50);

        $files->setCollection(
            $files->getCollection()->map(function (CourseFile $f) use ($course) {
                return $this->filePayload($course, $f);
            })
        );

        $payload = ApiPagination::format($files);
        return response()->json([
            'files' => $payload['data'],
            'meta' => $payload['meta'],
        ]);
    }

    public function show(Request $request, Course $course, CourseFile $file): JsonResponse
    {
        $this->authorizeCourseAccess($request, $course);
        abort_unless((int) $file->course_id === (int) $course->id, 404);

        return response()->json([
            'file' => $this->filePayload($course, $file),
        ]);
    }

    public function store(Request $request, Course $course): JsonResponse
    {
        $this->authorizeTeacherAccess($request, $course);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:2048000'], // ~2GB in KB
            'name' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'cipher' => ['required', 'string', 'max:50'],
            'content_key' => ['required', 'string'],
            'content_iv' => ['required', 'string'],
            'key_version' => ['nullable', 'string', 'max:50'],
            'encrypted_sha256' => ['nullable', 'string', 'max:128'],
        ]);

        // Validate AES-128 base64 key/iv lengths (must decode to 16 bytes)
        $keyBytes = base64_decode((string) $data['content_key'], true);
        if ($keyBytes === false || strlen($keyBytes) !== 16) {
            return response()->json(['message' => 'content_key must be base64 for 16 bytes'], 422);
        }
        $ivBytes = base64_decode((string) $data['content_iv'], true);
        if ($ivBytes === false || strlen($ivBytes) !== 16) {
            return response()->json(['message' => 'content_iv must be base64 for 16 bytes'], 422);
        }

        $disk = (string) config('filesystems.default', 'local');
        $path = $request->file('file')->store('course-files/'.$course->id, $disk);

        $file = CourseFile::create([
            'course_id' => $course->id,
            'uploader_id' => $request->user()->id,
            'name' => $data['name'] ?? $request->file('file')->getClientOriginalName(),
            'name_en' => $data['name_en'] ?? null,
            'original_name' => $request->file('file')->getClientOriginalName(),
            'storage_disk' => $disk,
            'storage_path' => $path,
            'size_bytes' => $request->file('file')->getSize(),
            'mime_type' => $request->file('file')->getClientMimeType(),
            'cipher' => $data['cipher'],
            'content_key' => $data['content_key'],
            'content_iv' => $data['content_iv'],
            'key_version' => $data['key_version'] ?? 'v1',
            'encrypted_sha256' => $data['encrypted_sha256'] ?? null,
        ]);

        return response()->json([
            'file' => $this->filePayload($course, $file),
        ], 201);
    }

    public function multipartInit(Request $request, Course $course): JsonResponse
    {
        $this->authorizeTeacherAccess($request, $course);

        $data = $request->validate([
            'original_name' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'size_bytes' => ['nullable', 'integer', 'min:1'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'cipher' => ['required', 'string', 'max:50'],
            'content_key' => ['required', 'string'],
            'content_iv' => ['required', 'string'],
            'key_version' => ['nullable', 'string', 'max:50'],
            'encrypted_sha256' => ['nullable', 'string', 'max:128'],
            'storage_disk' => ['nullable', 'string', 'in:wasabi,r2,local'],
        ]);

        $keyBytes = base64_decode((string) $data['content_key'], true);
        if ($keyBytes === false || strlen($keyBytes) !== 16) {
            return response()->json(['message' => 'content_key must be base64 for 16 bytes'], 422);
        }
        $ivBytes = base64_decode((string) $data['content_iv'], true);
        if ($ivBytes === false || strlen($ivBytes) !== 16) {
            return response()->json(['message' => 'content_iv must be base64 for 16 bytes'], 422);
        }

        $mime = (string) ($data['mime_type'] ?? 'application/octet-stream');
        $requested = isset($data['storage_disk']) ? trim((string) $data['storage_disk']) : '';
        $defaultDisk = strtolower((string) config('filesystems.default', 'local'));

        // Explicit local, or default filesystem is local (no storage_disk) → skip object storage attempts
        if ($requested === 'local' || ($requested === '' && $defaultDisk === 'local')) {
            return $this->multipartLocalInitResponse($request, $course, $data, $mime);
        }

        foreach ($this->orderedS3DisksForMultipart($requested) as $s3Disk) {
            if (! $this->isS3DiskConfigured($s3Disk)) {
                continue;
            }

            $client = $this->buildS3ClientForDisk($s3Disk);
            $bucket = (string) config("filesystems.disks.{$s3Disk}.bucket");
            $objectKey = $this->makeMultipartObjectKey($course->id, (string) $data['original_name'], $s3Disk);

            try {
                $result = $client->createMultipartUpload([
                    'Bucket' => $bucket,
                    'Key' => $objectKey,
                    'ContentType' => $mime,
                ]);
            } catch (Throwable) {
                continue;
            }

            $uploadId = (string) ($result['UploadId'] ?? '');
            if ($uploadId === '') {
                continue;
            }

            $tokenPayload = [
                'course_id' => (int) $course->id,
                'uploader_id' => (int) $request->user()->id,
                'upload_id' => $uploadId,
                'object_key' => $objectKey,
                'storage_disk' => $s3Disk,
                'name' => $data['name'] ?? $data['original_name'],
                'name_en' => $data['name_en'] ?? null,
                'original_name' => $data['original_name'],
                'size_bytes' => $data['size_bytes'] ?? null,
                'mime_type' => $mime,
                'cipher' => $data['cipher'],
                'content_key' => $data['content_key'],
                'content_iv' => $data['content_iv'],
                'key_version' => $data['key_version'] ?? 'v1',
                'encrypted_sha256' => $data['encrypted_sha256'] ?? null,
                'expires_at' => now()->addHours(2)->timestamp,
            ];

            return response()->json(array_merge([
                'upload_id' => $uploadId,
                'object_key' => $objectKey,
                'storage_disk' => $s3Disk,
                'max_parts' => 10000,
                'expires_in_seconds' => 7200,
                'multipart_token' => Crypt::encryptString(json_encode($tokenPayload, JSON_THROW_ON_ERROR)),
            ], MediaChunkingHints::multipartInitFields('s3')), 201);
        }

        return $this->multipartLocalInitResponse($request, $course, $data, $mime);
    }

    public function multipartSignPart(Request $request, Course $course): JsonResponse
    {
        $this->authorizeTeacherAccess($request, $course);

        $data = $request->validate([
            'upload_id' => ['required', 'string', 'max:512'],
            'object_key' => ['required', 'string', 'max:2048'],
            'part_number' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        $objectKey = (string) $data['object_key'];
        if (!$this->isMultipartKeyForCourse($course->id, $objectKey)) {
            return response()->json(['message' => 'object_key is invalid for this course'], 422);
        }

        $storageDisk = $this->multipartStorageDiskFromObjectKey($objectKey);
        $uploadId = (string) $data['upload_id'];
        $partNumber = (int) $data['part_number'];

        if ($storageDisk === 'local') {
            if (! $this->localMultipartSessionValid($uploadId, (int) $course->id, (int) $request->user()->id)) {
                return response()->json(['message' => 'Multipart session not found or expired'], 422);
            }

            $partPayload = [
                'cid' => (int) $course->id,
                'uid' => (int) $request->user()->id,
                'up' => $uploadId,
                'ok' => $objectKey,
                'pn' => $partNumber,
                'exp' => now()->addMinutes(15)->timestamp,
            ];
            $partToken = Crypt::encryptString(json_encode($partPayload, JSON_THROW_ON_ERROR));
            $base = rtrim((string) config('app.url', ''), '/');
            $multipartBase = str_contains($request->path(), '/videos/multipart/') ? 'videos' : 'files';
            $url = $base.'/api/v1/courses/'.$course->id.'/'.$multipartBase.'/multipart/part?part_token='.rawurlencode($partToken);

            return response()->json([
                'url' => $url,
                'method' => 'PUT',
                'headers' => [],
                'part_number' => $partNumber,
            ]);
        }

        $client = $this->buildS3ClientForDisk($storageDisk);
        $bucket = (string) config("filesystems.disks.{$storageDisk}.bucket");
        $command = $client->getCommand('UploadPart', [
            'Bucket' => $bucket,
            'Key' => $objectKey,
            'UploadId' => $uploadId,
            'PartNumber' => $partNumber,
        ]);
        $presigned = $client->createPresignedRequest($command, '+15 minutes');

        return response()->json([
            'url' => (string) $presigned->getUri(),
            'method' => 'PUT',
            'headers' => [],
            'part_number' => $partNumber,
        ]);
    }

    /**
     * Upload one part for {@see multipartInit} when storage_disk is local (body = raw bytes).
     */
    public function multipartPutPart(Request $request, Course $course)
    {
        $this->authorizeTeacherAccess($request, $course);

        $token = (string) $request->query('part_token', '');
        $payload = $this->parseMultipartPartUploadToken($token);
        if ($payload === null || (int) ($payload['exp'] ?? 0) < now()->timestamp) {
            return response()->json(['message' => 'Invalid or expired part token'], 422);
        }
        if ((int) ($payload['cid'] ?? 0) !== (int) $course->id || (int) ($payload['uid'] ?? 0) !== (int) $request->user()->id) {
            return response()->json(['message' => 'Part token does not match course or user'], 422);
        }

        $objectKey = (string) ($payload['ok'] ?? '');
        $uploadId = (string) ($payload['up'] ?? '');
        $partNumber = (int) ($payload['pn'] ?? 0);
        if ($uploadId === '' || $partNumber < 1 || ! str_contains($objectKey, '/multipart/local/')
            || ! $this->isMultipartKeyForCourse($course->id, $objectKey)) {
            return response()->json(['message' => 'Invalid multipart part request'], 422);
        }

        if (! $this->localMultipartSessionValid($uploadId, (int) $course->id, (int) $request->user()->id)) {
            return response()->json(['message' => 'Multipart session not found or expired'], 422);
        }

        $disk = Storage::disk('local');
        $tmpDir = $this->localMultipartTmpRelativePath($course->id, $uploadId);
        $partPath = $tmpDir.'/part-'.$partNumber;
        $disk->makeDirectory($tmpDir);

        $maxBytes = max(1024 * 1024, (int) config('media_chunking.multipart.max_part_bytes', 100 * 1024 * 1024));
        $written = 0;
        $input = fopen('php://input', 'rb');
        if ($input === false) {
            return response()->json(['message' => 'Could not read upload body'], 422);
        }

        $absolutePart = $disk->path($partPath);
        $out = fopen($absolutePart, 'wb');
        if ($out === false) {
            fclose($input);

            return response()->json(['message' => 'Could not store part'], 422);
        }

        try {
            while (! feof($input)) {
                $chunk = fread($input, 1024 * 1024);
                if ($chunk === false) {
                    break;
                }
                $written += strlen($chunk);
                if ($written > $maxBytes) {
                    fclose($out);
                    fclose($input);
                    if ($disk->exists($partPath)) {
                        $disk->delete($partPath);
                    }

                    return response()->json(['message' => 'Part exceeds maximum size'], 413);
                }
                fwrite($out, $chunk);
            }
        } finally {
            fclose($out);
            fclose($input);
        }

        if ($written === 0) {
            if ($disk->exists($partPath)) {
                $disk->delete($partPath);
            }

            return response()->json(['message' => 'Empty part body'], 422);
        }

        $etag = '"'.md5_file($absolutePart).'"';

        return response('', 200)->header('ETag', $etag);
    }

    public function multipartComplete(Request $request, Course $course): JsonResponse
    {
        $this->authorizeTeacherAccess($request, $course);

        $data = $request->validate([
            'upload_id' => ['required', 'string', 'max:512'],
            'object_key' => ['required', 'string', 'max:2048'],
            'multipart_token' => ['required', 'string'],
            'parts' => ['required', 'array', 'min:1'],
            'parts.*.part_number' => ['required', 'integer', 'min:1', 'max:10000'],
            'parts.*.etag' => ['required', 'string', 'max:512'],
        ]);

        $token = $this->parseMultipartToken((string) $data['multipart_token']);
        if ($token === null) {
            return response()->json(['message' => 'Invalid multipart token'], 422);
        }
        if ((int) ($token['expires_at'] ?? 0) < now()->timestamp) {
            return response()->json(['message' => 'Multipart token expired; restart upload'], 422);
        }
        if ((int) ($token['course_id'] ?? 0) !== (int) $course->id || (int) ($token['uploader_id'] ?? 0) !== (int) $request->user()->id) {
            return response()->json(['message' => 'Multipart token does not match user/course'], 422);
        }
        if ((string) ($token['upload_id'] ?? '') !== (string) $data['upload_id'] || (string) ($token['object_key'] ?? '') !== (string) $data['object_key']) {
            return response()->json(['message' => 'Multipart token does not match upload session'], 422);
        }

        $objectKey = (string) $data['object_key'];
        if (!$this->isMultipartKeyForCourse($course->id, $objectKey)) {
            return response()->json(['message' => 'object_key is invalid for this course'], 422);
        }

        $tokenDisk = (string) ($token['storage_disk'] ?? 'wasabi');
        if ($this->multipartStorageDiskFromObjectKey($objectKey) !== $tokenDisk) {
            return response()->json(['message' => 'object_key does not match multipart storage disk'], 422);
        }

        if ($tokenDisk === 'local') {
            return $this->multipartCompleteLocal($request, $course, $data, $token, $objectKey);
        }

        $parts = collect($data['parts'])
            ->map(function (array $part): array {
                $etag = trim((string) $part['etag']);
                if (!str_starts_with($etag, '"')) {
                    $etag = '"'.$etag.'"';
                }

                return [
                    'PartNumber' => (int) $part['part_number'],
                    'ETag' => $etag,
                ];
            })
            ->sortBy('PartNumber')
            ->values()
            ->all();

        $client = $this->buildS3ClientForDisk($tokenDisk);
        $bucket = (string) config("filesystems.disks.{$tokenDisk}.bucket");

        try {
            $client->completeMultipartUpload([
                'Bucket' => $bucket,
                'Key' => $objectKey,
                'UploadId' => (string) $data['upload_id'],
                'MultipartUpload' => ['Parts' => $parts],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Failed to complete multipart upload',
                'error' => $e->getMessage(),
            ], 422);
        }

        $sizeBytes = $token['size_bytes'] ?? null;
        if ($sizeBytes === null) {
            try {
                $head = $client->headObject([
                    'Bucket' => $bucket,
                    'Key' => $objectKey,
                ]);
                $sizeBytes = (int) ($head['ContentLength'] ?? 0);
            } catch (Throwable) {
                $sizeBytes = null;
            }
        }

        $file = CourseFile::create([
            'course_id' => $course->id,
            'uploader_id' => $request->user()->id,
            'name' => (string) ($token['name'] ?? 'uploaded-file'),
            'name_en' => $token['name_en'] ?? null,
            'original_name' => $token['original_name'] ?? null,
            'storage_disk' => $tokenDisk,
            'storage_path' => $objectKey,
            'size_bytes' => $sizeBytes,
            'mime_type' => (string) ($token['mime_type'] ?? 'application/octet-stream'),
            'cipher' => (string) ($token['cipher'] ?? ''),
            'content_key' => (string) ($token['content_key'] ?? ''),
            'content_iv' => (string) ($token['content_iv'] ?? ''),
            'key_version' => (string) ($token['key_version'] ?? 'v1'),
            'encrypted_sha256' => $token['encrypted_sha256'] ?? null,
        ]);

        return response()->json([
            'file' => $this->filePayload($course, $file),
        ], 201);
    }

    public function multipartStatus(Request $request, Course $course): JsonResponse
    {
        $this->authorizeTeacherAccess($request, $course);

        $data = $request->validate([
            'upload_id' => ['required', 'string', 'max:512'],
            'object_key' => ['required', 'string', 'max:2048'],
            'multipart_token' => ['required', 'string'],
        ]);

        $token = $this->parseMultipartToken((string) $data['multipart_token']);
        if ($token === null) {
            return response()->json(['message' => 'Invalid multipart token'], 422);
        }
        if ((int) ($token['expires_at'] ?? 0) < now()->timestamp) {
            return response()->json(['message' => 'Multipart token expired; restart upload'], 422);
        }
        if ((int) ($token['course_id'] ?? 0) !== (int) $course->id || (int) ($token['uploader_id'] ?? 0) !== (int) $request->user()->id) {
            return response()->json(['message' => 'Multipart token does not match user/course'], 422);
        }
        if ((string) ($token['upload_id'] ?? '') !== (string) $data['upload_id'] || (string) ($token['object_key'] ?? '') !== (string) $data['object_key']) {
            return response()->json(['message' => 'Multipart token does not match upload session'], 422);
        }

        $objectKey = (string) $data['object_key'];
        if (!$this->isMultipartKeyForCourse($course->id, $objectKey)) {
            return response()->json(['message' => 'object_key is invalid for this course'], 422);
        }

        $storageDisk = $this->multipartStorageDiskFromObjectKey($objectKey);
        $uploadId = (string) $data['upload_id'];

        if ($storageDisk === 'local') {
            if (!$this->localMultipartSessionValid($uploadId, (int) $course->id, (int) $request->user()->id)) {
                return response()->json(['message' => 'Multipart session not found or expired'], 422);
            }

            $disk = Storage::disk('local');
            $tmpDir = $this->localMultipartTmpRelativePath($course->id, $uploadId);
            $uploadedParts = [];
            if ($disk->exists($tmpDir)) {
                foreach ($disk->files($tmpDir) as $file) {
                    if (preg_match('/part-(\d+)$/', $file, $m)) {
                        $uploadedParts[] = (int) $m[1];
                    }
                }
            }

            return response()->json([
                'upload_id' => $uploadId,
                'object_key' => $objectKey,
                'storage_disk' => 'local',
                'uploaded_parts' => $uploadedParts,
                'expires_at' => $token['expires_at'],
            ]);
        }

        // S3-compatible: list parts already uploaded
        $client = $this->buildS3ClientForDisk($storageDisk);
        $bucket = (string) config("filesystems.disks.{$storageDisk}.bucket");

        try {
            $result = $client->listParts([
                'Bucket' => $bucket,
                'Key' => $objectKey,
                'UploadId' => $uploadId,
            ]);
            $uploadedParts = collect($result->get('Parts', []))
                ->map(fn (array $p) => (int) ($p['PartNumber'] ?? 0))
                ->filter(fn (int $pn) => $pn > 0)
                ->values()
                ->all();
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Failed to list uploaded parts',
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'upload_id' => $uploadId,
            'object_key' => $objectKey,
            'storage_disk' => $storageDisk,
            'uploaded_parts' => $uploadedParts,
            'expires_at' => $token['expires_at'],
        ]);
    }

    public function multipartAbort(Request $request, Course $course): JsonResponse
    {
        $this->authorizeTeacherAccess($request, $course);

        $data = $request->validate([
            'upload_id' => ['required', 'string', 'max:512'],
            'object_key' => ['required', 'string', 'max:2048'],
        ]);

        $objectKey = (string) $data['object_key'];
        if (!$this->isMultipartKeyForCourse($course->id, $objectKey)) {
            return response()->json(['message' => 'object_key is invalid for this course'], 422);
        }

        $storageDisk = $this->multipartStorageDiskFromObjectKey($objectKey);
        $uploadId = (string) $data['upload_id'];

        if ($storageDisk === 'local') {
            $disk = Storage::disk('local');
            $tmpDir = $this->localMultipartTmpRelativePath($course->id, $uploadId);
            if ($disk->exists($tmpDir)) {
                $disk->deleteDirectory($tmpDir);
            }
            Cache::forget($this->localMultipartCacheKey($uploadId));

            return response()->json(['aborted' => true]);
        }

        $client = $this->buildS3ClientForDisk($storageDisk);
        $bucket = (string) config("filesystems.disks.{$storageDisk}.bucket");

        try {
            $client->abortMultipartUpload([
                'Bucket' => $bucket,
                'Key' => $objectKey,
                'UploadId' => $uploadId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Failed to abort multipart upload',
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['aborted' => true]);
    }

    /**
     * Time-limited direct HTTP access to encrypted bytes (Range supported), mirroring S3 presigned URLs for local/public storage.
     */
    public function signedStream(Request $request, Course $course, CourseFile $file)
    {
        abort_unless((int) $file->course_id === (int) $course->id, 404);

        $disk = (string) ($file->storage_disk ?: config('filesystems.default', 'local'));
        try {
            $diskFs = Storage::disk($disk);
        } catch (\Throwable $e) {
            Log::warning('Signed stream invalid storage disk', [
                'file_id' => $file->id,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Invalid storage disk'], 422);
        }

        try {
            $exists = $diskFs->exists($file->storage_path);
        } catch (\Throwable $e) {
            Log::warning('Signed stream existence check failed', [
                'file_id' => $file->id,
                'disk' => $disk,
                'storage_path' => $file->storage_path,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Storage is temporarily unavailable'], 502);
        }

        if (! $exists) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $disposition = strtolower((string) $request->query('disposition', 'inline'));
        $asAttachment = $disposition === 'attachment';
        $safeBase = preg_replace('/[^\p{L}\p{N}\-_\. ]/u', '_', (string) $file->localized_name) ?: 'file';
        $filename = $safeBase.'.encrypted.bin';

        if ($request->isMethod('HEAD')) {
            $size = $file->size_bytes;
            if ($size === null || $size < 0) {
                try {
                    $size = (int) $diskFs->size($file->storage_path);
                } catch (\Throwable $e) {
                    Log::warning('Signed stream HEAD size failed', [
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

    public function download(Request $request, Course $course, CourseFile $file)
    {
        $this->authorizeCourseAccess($request, $course);
        abort_unless((int) $file->course_id === (int) $course->id, 404);

        $disk = (string) ($file->storage_disk ?: config('filesystems.default', 'local'));

        return LocalEncryptedBlobRangeResponse::fromStoragePath(
            $request,
            $disk,
            $file->storage_path,
            $file->localized_name,
            true,
        );
    }

    public function destroy(Request $request, Course $course, CourseFile $file): JsonResponse
    {
        abort_unless((int) $file->course_id === (int) $course->id, 404);

        $disk = (string) ($file->storage_disk ?: config('filesystems.default', 'local'));
        if ($file->storage_path && Storage::disk($disk)->exists($file->storage_path)) {
            Storage::disk($disk)->delete($file->storage_path);
        }

        $fileId = $file->id;
        $file->delete();

        return response()->json([
            'deleted' => true,
            'file_id' => $fileId,
        ]);
    }

    private function authorizeCourseAccess(Request $request, Course $course): void
    {
        $user = $request->user();
        if (in_array($user->role, ['admin'], true)) {
            return;
        }
        if ($user->role === 'teacher' && (int) $course->teacher_id === (int) $user->id) {
            return;
        }
        $enrollment = CourseEnrollment::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->first();
        if (!$enrollment) {
            throw new HttpException(423, 'Enrollment not approved');
        }
        if ($enrollment->access_locked) {
            throw new HttpException(423, 'Course access suspended');
        }
    }

    private function authorizeTeacherAccess(Request $request, Course $course): void
    {
        $user = $request->user();
        if ($user->role === 'admin') {
            return;
        }
        abort_unless($user->role === 'teacher' && (int) $course->teacher_id === (int) $user->id, 403);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function multipartLocalInitResponse(Request $request, Course $course, array $data, string $mime): JsonResponse
    {
        $uploadId = Str::uuid()->toString();
        $objectKey = $this->makeMultipartObjectKey($course->id, (string) $data['original_name'], 'local');
        $tmpDir = $this->localMultipartTmpRelativePath($course->id, $uploadId);
        Storage::disk('local')->makeDirectory($tmpDir);

        Cache::put($this->localMultipartCacheKey($uploadId), [
            'course_id' => (int) $course->id,
            'uploader_id' => (int) $request->user()->id,
            'object_key' => $objectKey,
        ], now()->addHours(2));

        $tokenPayload = [
            'course_id' => (int) $course->id,
            'uploader_id' => (int) $request->user()->id,
            'upload_id' => $uploadId,
            'object_key' => $objectKey,
            'storage_disk' => 'local',
            'name' => $data['name'] ?? $data['original_name'],
            'name_en' => $data['name_en'] ?? null,
            'original_name' => $data['original_name'],
            'size_bytes' => $data['size_bytes'] ?? null,
            'mime_type' => $mime,
            'cipher' => $data['cipher'],
            'content_key' => $data['content_key'],
            'content_iv' => $data['content_iv'],
            'key_version' => $data['key_version'] ?? 'v1',
            'encrypted_sha256' => $data['encrypted_sha256'] ?? null,
            'expires_at' => now()->addHours(2)->timestamp,
        ];

        return response()->json(array_merge([
            'upload_id' => $uploadId,
            'object_key' => $objectKey,
            'storage_disk' => 'local',
            'max_parts' => 10000,
            'expires_in_seconds' => 7200,
            'multipart_token' => Crypt::encryptString(json_encode($tokenPayload, JSON_THROW_ON_ERROR)),
        ], MediaChunkingHints::multipartInitFields('local')), 201);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $token
     */
    private function multipartCompleteLocal(Request $request, Course $course, array $data, array $token, string $objectKey): JsonResponse
    {
        $uploadId = (string) $data['upload_id'];
        $disk = Storage::disk('local');
        $tmpDir = $this->localMultipartTmpRelativePath($course->id, $uploadId);

        $sorted = collect($data['parts'])
            ->sortBy(fn (array $p) => (int) ($p['part_number'] ?? 0))
            ->values();

        foreach ($sorted as $part) {
            $pn = (int) ($part['part_number'] ?? 0);
            if ($pn < 1 || ! $disk->exists($tmpDir.'/part-'.$pn)) {
                return response()->json(['message' => 'Missing uploaded part '.$pn], 422);
            }
        }

        $disk->makeDirectory(dirname($objectKey));
        $finalPath = $disk->path($objectKey);
        $parent = dirname($finalPath);
        if (! is_dir($parent)) {
            mkdir($parent, 0755, true);
        }

        $out = fopen($finalPath, 'wb');
        if ($out === false) {
            return response()->json(['message' => 'Could not finalize multipart file'], 422);
        }

        try {
            foreach ($sorted as $part) {
                $pn = (int) $part['part_number'];
                $partAbs = $disk->path($tmpDir.'/part-'.$pn);
                $in = fopen($partAbs, 'rb');
                if ($in === false) {
                    fclose($out);
                    if (is_file($finalPath)) {
                        unlink($finalPath);
                    }

                    return response()->json(['message' => 'Could not read part '.$pn], 422);
                }
                stream_copy_to_stream($in, $out);
                fclose($in);
            }
        } finally {
            fclose($out);
        }

        $disk->deleteDirectory($tmpDir);
        Cache::forget($this->localMultipartCacheKey($uploadId));

        $sizeBytes = $token['size_bytes'] ?? null;
        if ($sizeBytes === null && is_file($finalPath)) {
            $sz = filesize($finalPath);
            $sizeBytes = $sz !== false ? (int) $sz : null;
        }

        $file = CourseFile::create([
            'course_id' => $course->id,
            'uploader_id' => $request->user()->id,
            'name' => (string) ($token['name'] ?? 'uploaded-file'),
            'name_en' => $token['name_en'] ?? null,
            'original_name' => $token['original_name'] ?? null,
            'storage_disk' => 'local',
            'storage_path' => $objectKey,
            'size_bytes' => $sizeBytes,
            'mime_type' => (string) ($token['mime_type'] ?? 'application/octet-stream'),
            'cipher' => (string) ($token['cipher'] ?? ''),
            'content_key' => (string) ($token['content_key'] ?? ''),
            'content_iv' => (string) ($token['content_iv'] ?? ''),
            'key_version' => (string) ($token['key_version'] ?? 'v1'),
            'encrypted_sha256' => $token['encrypted_sha256'] ?? null,
        ]);

        return response()->json([
            'file' => $this->filePayload($course, $file),
        ], 201);
    }

    private function localMultipartCacheKey(string $uploadId): string
    {
        return 'course_file_multipart_local:'.$uploadId;
    }

    private function localMultipartTmpRelativePath(int $courseId, string $uploadId): string
    {
        return "course-files/{$courseId}/multipart/_tmp/{$uploadId}";
    }

    private function localMultipartSessionValid(string $uploadId, int $courseId, int $userId): bool
    {
        $session = Cache::get($this->localMultipartCacheKey($uploadId));
        if (! is_array($session)) {
            return false;
        }

        return (int) ($session['course_id'] ?? 0) === $courseId
            && (int) ($session['uploader_id'] ?? 0) === $userId;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseMultipartPartUploadToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        try {
            $json = Crypt::decryptString($token);
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    private function orderedS3DisksForMultipart(string $requested): array
    {
        if ($requested === 'r2') {
            return ['r2', 'wasabi'];
        }

        return ['wasabi', 'r2'];
    }

    private function isS3DiskConfigured(string $disk): bool
    {
        if (! in_array($disk, ['wasabi', 'r2'], true)) {
            return false;
        }

        $prefix = "filesystems.disks.{$disk}";
        $key = (string) config("{$prefix}.key");
        $secret = (string) config("{$prefix}.secret");
        $bucket = (string) config("{$prefix}.bucket");
        $endpoint = $this->normalizeEndpoint((string) config("{$prefix}.endpoint", ''));

        return $key !== '' && $secret !== '' && $bucket !== '' && $endpoint !== '';
    }

    /**
     * S3-compatible disks used for encrypted course file multipart uploads.
     */
    private function buildS3ClientForDisk(string $disk): S3Client
    {
        if (! $this->isS3DiskConfigured($disk)) {
            throw new HttpException(500, "Object storage disk [{$disk}] is not fully configured");
        }

        $prefix = "filesystems.disks.{$disk}";
        $key = (string) config("{$prefix}.key");
        $secret = (string) config("{$prefix}.secret");
        $region = (string) config("{$prefix}.region", 'us-east-1');
        $bucket = (string) config("{$prefix}.bucket");
        $endpoint = $this->normalizeEndpoint((string) config("{$prefix}.endpoint", ''));

        return new S3Client([
            'version' => 'latest',
            'region' => $region,
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => (bool) config("{$prefix}.use_path_style_endpoint", false),
            'credentials' => [
                'key' => $key,
                'secret' => $secret,
            ],
        ]);
    }

    /**
     * R2 uploads use .../multipart/r2/...; local uses .../multipart/local/...
     */
    private function multipartStorageDiskFromObjectKey(string $objectKey): string
    {
        if (str_contains($objectKey, '/multipart/r2/')) {
            return 'r2';
        }
        if (str_contains($objectKey, '/multipart/local/')) {
            return 'local';
        }

        return 'wasabi';
    }

    private function normalizeEndpoint(string $endpoint): string
    {
        $endpoint = trim($endpoint);
        if ($endpoint === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $endpoint)) {
            return 'https://'.$endpoint;
        }

        return $endpoint;
    }

    private function makeMultipartObjectKey(int $courseId, string $originalName, string $storageDisk = 'wasabi'): string
    {
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $safeBase = pathinfo($originalName, PATHINFO_FILENAME);
        $safeBase = Str::slug($safeBase);
        if ($safeBase === '') {
            $safeBase = 'file';
        }

        $filename = Str::uuid()->toString().'-'.$safeBase;
        if ($ext !== '') {
            $filename .= '.'.Str::lower($ext);
        }

        $prefix = match ($storageDisk) {
            'r2' => "course-files/{$courseId}/multipart/r2/",
            'local' => "course-files/{$courseId}/multipart/local/",
            default => "course-files/{$courseId}/multipart/",
        };

        return $prefix.$filename;
    }

    private function isMultipartKeyForCourse(int $courseId, string $objectKey): bool
    {
        return str_starts_with($objectKey, "course-files/{$courseId}/multipart/");
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseMultipartToken(string $token): ?array
    {
        try {
            $json = Crypt::decryptString($token);
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function filePayload(Course $course, CourseFile $f): array
    {
        return [
            'id' => $f->id,
            'course_id' => $f->course_id,
            'name' => $f->name,
            'name_en' => $f->name_en,
            'original_name' => $f->original_name,
            'localized_name' => $f->localized_name,
            'mime_type' => $f->mime_type ?: 'application/pdf',
            'storage_disk' => $f->storage_disk,
            'storage_path' => $f->storage_path,
            'size_bytes' => $f->size_bytes,
            'download_path' => "/api/v1/courses/{$course->id}/files/{$f->id}/download",
            'encryption' => [
                'cipher' => $f->cipher,
                'content_key' => $f->content_key,
                'content_iv' => $f->content_iv,
                'key_version' => $f->key_version ?: null,
                'encrypted_sha256' => $f->encrypted_sha256 ?: null,
            ],
        ];
    }
}
