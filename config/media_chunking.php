<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Multipart upload (course files)
    |--------------------------------------------------------------------------
    |
    | Smaller parts + parallel uploads reduce time-to-first-complete and ease
    | retries. S3 allows up to 10,000 parts; min part size except last is 5 MiB.
    |
    */

    'multipart' => [
        /** Minimum advertised part size for Wasabi/R2 (S3 API requires ≥ 5 MiB except last part). */
        'part_size_bytes' => (int) env('MULTIPART_PART_SIZE_BYTES', 5 * 1024 * 1024),
        /** Target part size for object storage (must be ≥ 5 MiB). */
        'recommended_part_size_bytes' => (int) env('MULTIPART_RECOMMENDED_PART_BYTES', 5 * 1024 * 1024),
        /** Local disk multipart: smaller chunks → faster per-request work + easier parallel uploads. */
        'local_recommended_part_bytes' => (int) env('MULTIPART_LOCAL_RECOMMENDED_PART_BYTES', 2 * 1024 * 1024),
        'local_min_part_bytes' => (int) env('MULTIPART_LOCAL_MIN_PART_BYTES', 512 * 1024),
        /** Hint: how many parts to upload concurrently when the client supports it. */
        'recommended_parallel_parts' => (int) env('MULTIPART_PARALLEL_PARTS', 4),
        /** Hard cap for PUT .../multipart/part (local disk) body size. */
        'max_part_bytes' => (int) env('MULTIPART_MAX_PART_BYTES', 100 * 1024 * 1024),
    ],

    /*
    |--------------------------------------------------------------------------
    | Encrypted blob streaming (AES-128-CBC)
    |--------------------------------------------------------------------------
    |
    | Clients should use HTTP Range with lengths aligned to 16-byte blocks
    | so decryption buffers stay block-aligned after the first segment.
    |
    */

    'stream' => [
        'cipher_block_bytes' => 16,
        'suggested_range_request_bytes' => (int) env('STREAM_SUGGESTED_RANGE_BYTES', 1024 * 1024),
        /** Laravel signed URL TTL for local/public blob streaming (Wasabi-style direct HTTP). */
        'signed_temp_url_ttl_minutes' => (int) env('STREAM_SIGNED_URL_TTL_MINUTES', 30),
    ],

];
