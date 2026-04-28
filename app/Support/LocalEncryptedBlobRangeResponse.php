<?php

namespace App\Support;

use Aws\S3\S3Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LocalEncryptedBlobRangeResponse
{
    /**
     * Stream a file from a local disk with optional HTTP Range support (206).
     *
     * @return StreamedResponse|JsonResponse
     */
    public static function fromStoragePath(
        Request $request,
        string $diskName,
        string $storagePath,
        string $downloadFilename,
        bool $asAttachment,
    ) {
        $disk = Storage::disk($diskName);
        if (! $disk->exists($storagePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        if (self::isS3CompatibleDisk($diskName)) {
            return self::fromS3StoragePath(
                $request,
                $diskName,
                $storagePath,
                $downloadFilename,
                $asAttachment,
            );
        }

        $absolutePath = null;
        $size = null;
        try {
            $absolutePath = $disk->path($storagePath);
            $localSize = @filesize($absolutePath);
            if ($localSize !== false) {
                $size = (int) $localSize;
            }
        } catch (\Throwable) {
            $absolutePath = null;
        }

        if ($size === null) {
            try {
                $size = (int) $disk->size($storagePath);
            } catch (\Throwable) {
                return response()->json(['message' => 'File not found'], 404);
            }
        }

        $range = (string) $request->header('Range', '');
        $dispositionType = $asAttachment ? 'attachment' : 'inline';
        $headers = array_merge(self::encryptedStreamHintHeaders(), [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => $dispositionType.'; filename="'.addslashes($downloadFilename).'"',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, no-store',
        ]);

        if ($range === '' || ! preg_match('/bytes=(\d*)-(\d*)/i', $range, $m)) {
            $headers['Content-Length'] = (string) $size;

            return $disk->download($storagePath, $downloadFilename, $headers);
        }

        $start = ($m[1] === '') ? 0 : (int) $m[1];
        $end = ($m[2] === '') ? ($size - 1) : (int) $m[2];
        if ($start < 0 || $end < 0 || $start > $end || $start >= $size) {
            return response()->json(['message' => 'Invalid Range'], 416, [
                'Content-Range' => "bytes */{$size}",
            ]);
        }
        if ($end >= $size) {
            $end = $size - 1;
        }

        $length = $end - $start + 1;
        $headers['Content-Length'] = (string) $length;
        $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";

        $response = new StreamedResponse(function () use ($disk, $storagePath, $absolutePath, $start, $length) {
            $handle = $absolutePath ? fopen($absolutePath, 'rb') : $disk->readStream($storagePath);
            if ($handle === false) {
                return;
            }
            try {
                $remaining = $length;
                if ($absolutePath) {
                    fseek($handle, $start);
                } else {
                    $toSkip = $start;
                    while ($toSkip > 0 && ! feof($handle)) {
                        $chunk = fread($handle, (int) min(1024 * 1024, $toSkip));
                        if ($chunk === false || $chunk === '') {
                            break;
                        }
                        $toSkip -= strlen($chunk);
                    }
                }
                while ($remaining > 0 && ! feof($handle)) {
                    $chunk = fread($handle, (int) min(1024 * 1024, $remaining));
                    if ($chunk === false || $chunk === '') {
                        break;
                    }
                    $remaining -= strlen($chunk);
                    echo $chunk;
                    flush();
                }
            } finally {
                fclose($handle);
            }
        }, 206, $headers);

        return $response;
    }

    /**
     * @return StreamedResponse|JsonResponse
     */
    private static function fromS3StoragePath(
        Request $request,
        string $diskName,
        string $storagePath,
        string $downloadFilename,
        bool $asAttachment,
    ) {
        try {
            $client = self::buildS3Client($diskName);
            $bucket = (string) config("filesystems.disks.{$diskName}.bucket", '');
            if ($bucket === '') {
                return response()->json(['message' => 'Storage bucket is not configured'], 500);
            }

            $head = $client->headObject([
                'Bucket' => $bucket,
                'Key' => $storagePath,
            ]);
            $size = (int) ($head['ContentLength'] ?? 0);
            if ($size <= 0) {
                return response()->json(['message' => 'File not found'], 404);
            }
        } catch (\Throwable $e) {
            Log::warning('S3 range HEAD failed', [
                'disk' => $diskName,
                'storage_path' => $storagePath,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Storage is temporarily unavailable'], 502);
        }

        $range = (string) $request->header('Range', '');
        $parsedRange = self::parseRange($range, $size);
        if ($parsedRange === false) {
            return response()->json(['message' => 'Invalid Range'], 416, [
                'Content-Range' => "bytes */{$size}",
            ]);
        }

        [$start, $end] = $parsedRange ?? [0, $size - 1];
        $length = $end - $start + 1;
        $isPartial = $parsedRange !== null;
        $dispositionType = $asAttachment ? 'attachment' : 'inline';
        $getObjectArgs = [
            'Bucket' => $bucket,
            'Key' => $storagePath,
        ];
        if ($isPartial) {
            $getObjectArgs['Range'] = "bytes={$start}-{$end}";
        }

        try {
            $result = $client->getObject($getObjectArgs);
        } catch (\Throwable $e) {
            Log::warning('S3 range GET failed', [
                'disk' => $diskName,
                'storage_path' => $storagePath,
                'range' => $isPartial ? "bytes={$start}-{$end}" : null,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Storage is temporarily unavailable'], 502);
        }

        $headers = array_merge(self::encryptedStreamHintHeaders(), [
            'Content-Type' => (string) ($result['ContentType'] ?? 'application/octet-stream'),
            'Content-Disposition' => $dispositionType.'; filename="'.addslashes($downloadFilename).'"',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, no-store',
            'Content-Length' => (string) $length,
        ]);
        if ($isPartial) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        return new StreamedResponse(function () use ($result, $length, $diskName, $storagePath, $start, $end, $isPartial): void {
            $body = $result['Body'];
            $remaining = $length;

            while ($remaining > 0 && ! $body->eof()) {
                $chunk = $body->read((int) min(1024 * 1024, $remaining));
                if ($chunk === '') {
                    break;
                }
                $remaining -= strlen($chunk);
                echo $chunk;
                flush();
            }

            if ($remaining > 0) {
                Log::warning('S3 ranged stream ended early', [
                    'disk' => $diskName,
                    'storage_path' => $storagePath,
                    'range' => $isPartial ? "bytes={$start}-{$end}" : null,
                    'missing_bytes' => $remaining,
                ]);
            }
        }, $isPartial ? 206 : 200, $headers);
    }

    /**
     * Helps clients fetch AES-CBC ciphertext in block-aligned ranges (see JSON playback hints on video resources).
     *
     * @return array<string, string>
     */
    private static function encryptedStreamHintHeaders(): array
    {
        $align = (int) config('media_chunking.stream.cipher_block_bytes', 16);
        $sug = (int) config('media_chunking.stream.suggested_range_request_bytes', 1024 * 1024);
        $sug = max($align, (int) (floor($sug / $align) * $align));

        return [
            'X-Encrypted-Blob-Block-Align' => (string) $align,
            'X-Suggested-Range-Bytes' => (string) $sug,
        ];
    }

    private static function isS3CompatibleDisk(string $diskName): bool
    {
        return (string) config("filesystems.disks.{$diskName}.driver", '') === 's3';
    }

    private static function buildS3Client(string $diskName): S3Client
    {
        $prefix = "filesystems.disks.{$diskName}";
        $key = (string) config("{$prefix}.key", '');
        $secret = (string) config("{$prefix}.secret", '');
        $region = (string) config("{$prefix}.region", 'us-east-1');
        $bucket = (string) config("{$prefix}.bucket", '');
        $endpoint = self::normalizeEndpoint((string) config("{$prefix}.endpoint", ''));

        if ($key === '' || $secret === '' || $bucket === '' || $endpoint === '') {
            throw new \RuntimeException("S3 disk [{$diskName}] is not fully configured");
        }

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

    private static function normalizeEndpoint(string $endpoint): string
    {
        $endpoint = trim($endpoint);
        if ($endpoint === '') {
            return '';
        }

        if (! preg_match('#^https?://#i', $endpoint)) {
            return 'https://'.$endpoint;
        }

        return $endpoint;
    }

    /**
     * @return array{0:int,1:int}|null|false
     */
    private static function parseRange(string $range, int $size): array|null|false
    {
        if ($range === '') {
            return null;
        }
        if (! preg_match('/bytes=(\d*)-(\d*)/i', $range, $m)) {
            return null;
        }

        $start = ($m[1] === '') ? 0 : (int) $m[1];
        $end = ($m[2] === '') ? ($size - 1) : (int) $m[2];
        if ($start < 0 || $end < 0 || $start > $end || $start >= $size) {
            return false;
        }
        if ($end >= $size) {
            $end = $size - 1;
        }

        return [$start, $end];
    }
}
