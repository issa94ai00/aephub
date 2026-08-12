<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A place videos and course files can be stored.
 *
 * Rows become Laravel filesystem disks at boot (see StorageDestinationService),
 * so `disk_key` is not a label — it is the disk name written into
 * `course_files.storage_disk` and used to read the file back years later.
 * Renaming it orphans every file already stored under it, which is why the
 * admin screen lets the display name change and the key never does.
 */
class StorageDestination extends Model
{
    use HasFactory;

    /** Disk names owned by config/filesystems.php that a destination must not shadow. */
    public const RESERVED_DISK_KEYS = ['local', 'public', 's3'];

    /* ------------------------------------------------------------------ *
     * Storage types
     *
     * The type decides which options mean anything, what they default to, and
     * what the limits are. They are genuinely different backends, not labels:
     * S3 refuses a multipart part under 5 MiB, while the local disk is
     * assembling parts in PHP and wants them far smaller.
     * ------------------------------------------------------------------ */

    public const TYPE_LOCAL = 'local';
    public const TYPE_S3 = 's3';
    public const TYPE_R2 = 'r2';

    public const TYPES = [self::TYPE_LOCAL, self::TYPE_S3, self::TYPE_R2];

    /** S3's own floor for every part except the last. Not ours to lower. */
    public const S3_MIN_PART_MB = 5;

    protected $fillable = [
        'name',
        'disk_key',
        'driver',
        'bucket',
        'endpoint',
        'region',
        'access_key',
        'secret_key',
        'use_path_style',
        'options',
        'is_active',
        'is_default',
        'quota_bytes',
    ];

    protected $casts = [
        // Laravel's encrypted casts: the credentials are ciphertext at rest and
        // plaintext only in memory, so a database dump is not a bucket handover.
        'access_key' => 'encrypted',
        'secret_key' => 'encrypted',
        'use_path_style' => 'boolean',
        'options' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'quota_bytes' => 'integer',
        'last_checked_at' => 'datetime',
        'last_check_ok' => 'boolean',
        'last_check_latency_ms' => 'integer',
    ];

    /**
     * Never serialise the credentials. Without this an admin JSON response or a
     * `dd()` in a controller would print the secret key in full.
     *
     * @var list<string>
     */
    protected $hidden = ['access_key', 'secret_key'];

    /** True for every type that talks to an S3 endpoint. */
    public function isObjectStorage(): bool
    {
        return in_array($this->driver, [self::TYPE_S3, self::TYPE_R2], true);
    }

    /**
     * The options this type supports, with the defaults it should start from.
     *
     * These mirror what config/media_chunking.php has been applying globally,
     * so a destination that overrides nothing behaves exactly as the platform
     * did before options existed. The two sets differ where the backends do:
     *
     *   local  parts are assembled by PHP, so they are small and the ceiling on
     *          a single PUT is what the web server will actually accept
     *   s3/r2  the API refuses parts under 5 MiB, and objects are fetched over
     *          a presigned URL whose lifetime is worth controlling
     *
     * @return array<string, int|string>
     */
    public static function defaultOptionsFor(string $type): array
    {
        $shared = [
            // Where objects land inside the bucket or disk. Lets one bucket
            // serve several environments without them colliding.
            'path_prefix' => 'course-files',

            'recommended_parallel_parts' => (int) config('media_chunking.multipart.recommended_parallel_parts', 4),

            // How long the client may keep resuming an interrupted upload
            // before it has to start over.
            'multipart_token_ttl_hours' => 2,
        ];

        if ($type === self::TYPE_LOCAL) {
            return $shared + [
                'part_size_mb' => self::bytesToMb((int) config('media_chunking.multipart.local_min_part_bytes', 512 * 1024)),
                'recommended_part_size_mb' => self::bytesToMb((int) config('media_chunking.multipart.local_recommended_part_bytes', 2 * 1024 * 1024)),
                // The hard cap on one PUT body. Must stay under whatever nginx
                // and PHP will accept, or the failure lands mid-upload.
                'max_part_mb' => self::bytesToMb((int) config('media_chunking.multipart.max_part_bytes', 100 * 1024 * 1024)),
                'signed_url_ttl_minutes' => (int) config('media_chunking.stream.signed_temp_url_ttl_minutes', 30),
            ];
        }

        return $shared + [
            'part_size_mb' => max(self::S3_MIN_PART_MB, self::bytesToMb((int) config('media_chunking.multipart.part_size_bytes', 5 * 1024 * 1024))),
            'recommended_part_size_mb' => max(self::S3_MIN_PART_MB, self::bytesToMb((int) config('media_chunking.multipart.recommended_part_size_bytes', 5 * 1024 * 1024))),
            // Presigned URL lifetime. Short enough that a leaked link expires,
            // long enough to watch a lecture without re-signing mid-playback.
            'signed_url_ttl_minutes' => (int) config('media_chunking.stream.signed_temp_url_ttl_minutes', 30),
        ];
    }

    /**
     * Every option for this destination: its own values over the type defaults.
     *
     * @return array<string, int|string>
     */
    public function effectiveOptions(): array
    {
        $defaults = self::defaultOptionsFor((string) $this->driver);
        $stored = is_array($this->options) ? $this->options : [];

        // Only keys this type knows about: an option left behind by a type
        // change would otherwise keep applying invisibly.
        $merged = array_merge($defaults, array_intersect_key($stored, $defaults));

        return $this->clamp($merged);
    }

    public function option(string $key): int|string|null
    {
        return $this->effectiveOptions()[$key] ?? null;
    }

    /**
     * Holds the options to what the backend will actually accept.
     *
     * Applied on read rather than only on save, so a row written before a limit
     * existed — or edited straight in the database — still cannot advertise a
     * part size that S3 will reject on the first PUT.
     *
     * @param  array<string, int|string>  $options
     * @return array<string, int|string>
     */
    private function clamp(array $options): array
    {
        $floor = $this->isObjectStorage() ? self::S3_MIN_PART_MB : 1;

        foreach (['part_size_mb', 'recommended_part_size_mb'] as $key) {
            $options[$key] = max($floor, (int) ($options[$key] ?? $floor));
        }

        // The advertised minimum can never exceed the recommendation, or a
        // client following both at once has no valid size to pick.
        $options['recommended_part_size_mb'] = max(
            (int) $options['part_size_mb'],
            (int) $options['recommended_part_size_mb']
        );

        $options['recommended_parallel_parts'] = max(1, min(16, (int) ($options['recommended_parallel_parts'] ?? 4)));
        $options['signed_url_ttl_minutes'] = max(1, (int) ($options['signed_url_ttl_minutes'] ?? 30));
        $options['multipart_token_ttl_hours'] = max(1, (int) ($options['multipart_token_ttl_hours'] ?? 2));

        if (array_key_exists('max_part_mb', $options)) {
            $options['max_part_mb'] = max((int) $options['part_size_mb'], (int) $options['max_part_mb']);
        }

        $options['path_prefix'] = trim((string) ($options['path_prefix'] ?? 'course-files'), '/ ') ?: 'course-files';

        return $options;
    }

    private static function bytesToMb(int $bytes): int
    {
        return max(1, (int) round($bytes / (1024 * 1024)));
    }

    /**
     * Whether this destination has everything an S3 client needs.
     *
     * Checked before a destination is offered as an upload target: a row saved
     * with a missing endpoint is a configuration someone started and did not
     * finish, and sending a 150MB video at it fails only after the upload.
     */
    public function isConfigured(): bool
    {
        if ($this->driver === self::TYPE_LOCAL) {
            return true;
        }

        return trim((string) $this->bucket) !== ''
            && trim((string) $this->endpoint) !== ''
            && trim((string) $this->access_key) !== ''
            && trim((string) $this->secret_key) !== '';
    }

    /**
     * The disk definition this destination contributes to `filesystems.disks`.
     *
     * @return array<string, mixed>
     */
    public function toDiskConfig(): array
    {
        if ($this->driver === self::TYPE_LOCAL) {
            return [
                'driver' => 'local',
                'root' => storage_path('app/private'),
                'serve' => true,
                'throw' => false,
                'report' => false,
            ];
        }

        // R2 has no regions and its endpoints are not virtual-hosted, so it
        // needs "auto" and path-style addressing. Defaulting it like Wasabi is
        // how an R2 destination saved with the fields left blank fails to sign
        // a single request.
        $isR2 = $this->driver === self::TYPE_R2;

        return [
            'driver' => 's3',
            'key' => (string) $this->access_key,
            'secret' => (string) $this->secret_key,
            'region' => (string) ($this->region ?: ($isR2 ? 'auto' : 'us-east-1')),
            'bucket' => (string) $this->bucket,
            'endpoint' => (string) $this->endpoint,
            'use_path_style_endpoint' => (bool) $this->use_path_style,
            'throw' => false,
            'report' => false,
        ];
    }

    /* ------------------------------------------------------------------ *
     * Capacity
     *
     * The ceiling is optional and null means unlimited, so every destination
     * that predates quotas keeps accepting uploads exactly as it did.
     * ------------------------------------------------------------------ */

    public function hasQuota(): bool
    {
        return $this->quota_bytes !== null && $this->quota_bytes > 0;
    }

    /** How much of the quota is left, or null when there is no quota. */
    public function quotaRemaining(int $usedBytes): ?int
    {
        if (! $this->hasQuota()) {
            return null;
        }

        return max(0, (int) $this->quota_bytes - $usedBytes);
    }

    /**
     * Whether an upload of this size still fits.
     *
     * Asked before the transfer starts rather than after: the whole point of a
     * quota is to refuse the 150MB video at init, when the client can be told
     * why, instead of at the final part when the bytes have already been paid
     * for. A destination with no quota always accepts.
     */
    public function canAccept(int $usedBytes, int $incomingBytes = 0): bool
    {
        if (! $this->hasQuota()) {
            return true;
        }

        return $usedBytes + max(0, $incomingBytes) <= (int) $this->quota_bytes;
    }

    /* ------------------------------------------------------------------ *
     * Health
     * ------------------------------------------------------------------ */

    /**
     * A check older than this is not evidence any more.
     *
     * Credentials get rotated and buckets get deleted without anyone telling
     * the platform, so a green badge from three weeks ago is worse than no
     * badge — it is a claim the application cannot stand behind.
     */
    public const HEALTH_STALE_AFTER_HOURS = 24;

    /** Stores the outcome of a connection check so the list can show it. */
    public function recordCheck(bool $ok, string $message, ?int $latencyMs = null): void
    {
        $this->forceFill([
            'last_checked_at' => now(),
            'last_check_ok' => $ok,
            // Provider errors run to paragraphs; the column is a badge tooltip,
            // not a log, and the full text is still in the flash message.
            'last_check_message' => mb_substr(trim($message), 0, 500),
            'last_check_latency_ms' => $latencyMs,
        ])->save();
    }

    /** One of: unknown (never checked), ok, stale, failing. */
    public function healthState(): string
    {
        if ($this->last_checked_at === null || $this->last_check_ok === null) {
            return 'unknown';
        }

        if (! $this->last_check_ok) {
            return 'failing';
        }

        return $this->last_checked_at->lt(now()->subHours(self::HEALTH_STALE_AFTER_HOURS))
            ? 'stale'
            : 'ok';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
