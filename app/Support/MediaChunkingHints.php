<?php

namespace App\Support;

final class MediaChunkingHints
{
    /**
     * Fields merged into POST .../files/multipart/init JSON (201).
     *
     * @param  'local'|'s3'  $backend  local = server-assembled parts; s3 = Wasabi/R2 rules (≥ 5 MiB).
     * @return array<string, int>
     */
    public static function multipartInitFields(string $backend = 's3'): array
    {
        $parallel = max(1, min(16, (int) config('media_chunking.multipart.recommended_parallel_parts', 4)));

        if ($backend === 'local') {
            $rec = max(256 * 1024, (int) config('media_chunking.multipart.local_recommended_part_bytes', 2 * 1024 * 1024));
            $floor = max(256 * 1024, (int) config('media_chunking.multipart.local_min_part_bytes', 512 * 1024));
            $floor = min($floor, $rec);
            $rec = max($floor, $rec);

            return [
                'part_size_bytes' => $floor,
                'recommended_part_size_bytes' => $rec,
                'recommended_parallel_parts' => $parallel,
            ];
        }

        $minS3 = 5 * 1024 * 1024;
        $part = max($minS3, (int) config('media_chunking.multipart.part_size_bytes', $minS3));
        $rec = max($minS3, (int) config('media_chunking.multipart.recommended_part_size_bytes', $minS3));
        $rec = min($rec, $part);

        return [
            'part_size_bytes' => $part,
            'recommended_part_size_bytes' => $rec,
            'recommended_parallel_parts' => $parallel,
        ];
    }

    /**
     * Hints for GET .../videos/{id}/encrypted (Range + client-side AES-CBC).
     *
     * @return array<string, bool|int>
     */
    public static function playbackHints(?int $encryptedBlobSizeBytes = null): array
    {
        $align = (int) config('media_chunking.stream.cipher_block_bytes', 16);
        $suggested = (int) config('media_chunking.stream.suggested_range_request_bytes', 1024 * 1024);
        $suggested = max($align, (int) (floor($suggested / $align) * $align));

        $prefetch = max($suggested * 2, 512 * 1024);
        if ($encryptedBlobSizeBytes !== null && $encryptedBlobSizeBytes > 0) {
            $prefetch = min($encryptedBlobSizeBytes, $prefetch);
        }

        return [
            'supports_byte_range' => true,
            'cipher_block_alignment_bytes' => $align,
            'suggested_range_request_bytes' => $suggested,
            'suggested_initial_prefetch_bytes' => $prefetch,
        ];
    }
}
