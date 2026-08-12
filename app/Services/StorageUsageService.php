<?php

namespace App\Services;

use App\Models\StorageDestination;
use App\Support\StoredObjectSources;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * How much each destination is actually holding.
 *
 * The platform already knew this and never asked: every stored object writes a
 * row carrying both the disk it went to and its size, so consumption is a
 * `group by` away. Reading it from the database rather than from the provider
 * matters — listing a bucket to total it costs a request per thousand objects
 * and tells you about objects the platform may no longer reference, which is a
 * different question (see StorageMaintenanceService).
 *
 * Two tables contribute. `course_files` is the general attachment path and
 * `course_videos` is the encrypted lecture path; they are separate tables with
 * the same two columns, and a destination holds whichever of them was pointed
 * at it.
 */
class StorageUsageService
{
    private const CACHE_KEY = 'storage_destinations.usage.v1';

    /**
     * Short, not forever. The number moves with every upload, and a storage
     * screen showing yesterday's total is a screen nobody trusts. Sixty seconds
     * is enough to absorb a page refresh without the aggregate running twice.
     */
    private const CACHE_TTL_SECONDS = 60;

    /**
     * Consumption per disk key.
     *
     * Covers every source in {@see StoredObjectSources}, not just course files
     * and videos — a destination also holds cover images, payment receipts and
     * live-session media, and a quota that ignored them would be measuring the
     * wrong bucket.
     *
     * @return array<string, array{files: int, videos: int, other: int, items: int, bytes: int}>
     */
    public function map(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
            $usage = [];

            foreach (StoredObjectSources::all() as $source) {
                // Guarded because this runs from the admin screen and from the
                // upload path; a partially migrated install must not 500 here.
                if (! Schema::hasTable($source['table'])) {
                    continue;
                }

                foreach ($source['paths'] as $path) {
                    // One aggregate per path column: a live-session asset row is
                    // two objects on the disk, and counting the row once would
                    // undercount what is actually stored.
                    $sizeExpression = $path['size'] === null
                        ? '0'
                        : 'COALESCE(SUM('.$path['size'].'), 0)';

                    $rows = DB::table($source['table'])
                        ->whereNotNull($path['column'])
                        ->where($path['column'], '!=', '')
                        ->selectRaw($source['disk'].' as disk_key, COUNT(*) as item_count, '.$sizeExpression.' as byte_total')
                        ->groupBy($source['disk'])
                        ->get();

                    foreach ($rows as $row) {
                        // Rows written before destinations existed can carry an
                        // empty disk; they live on whatever `local` resolves to.
                        $disk = trim((string) $row->disk_key) ?: 'local';

                        $usage[$disk] ??= ['files' => 0, 'videos' => 0, 'other' => 0, 'items' => 0, 'bytes' => 0];
                        $usage[$disk][$source['kind']] += (int) $row->item_count;
                        $usage[$disk]['items'] += (int) $row->item_count;

                        // A nullable or absent size column means the platform
                        // never recorded how big these are. They count as
                        // objects and as zero bytes, which is the honest
                        // reading rather than a guess.
                        $usage[$disk]['bytes'] += (int) $row->byte_total;
                    }
                }
            }

            return $usage;
        });
    }

    /**
     * @return array{files: int, videos: int, other: int, items: int, bytes: int}
     */
    public function forDisk(string $diskKey): array
    {
        return $this->map()[$diskKey] ?? ['files' => 0, 'videos' => 0, 'other' => 0, 'items' => 0, 'bytes' => 0];
    }

    public function bytesOn(string $diskKey): int
    {
        return $this->forDisk($diskKey)['bytes'];
    }

    /** Call after anything that moves or deletes stored objects. */
    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * A destination's consumption with the quota context the screen renders.
     *
     * `percent` is null when there is no quota rather than 0, so the bar can
     * tell "unlimited" apart from "empty" — they look identical otherwise and
     * mean opposite things.
     *
     * @return array{files: int, videos: int, other: int, items: int, bytes: int, quota_bytes: int|null, percent: float|null, remaining_bytes: int|null, state: string}
     */
    public function summaryFor(?StorageDestination $destination, string $diskKey): array
    {
        $usage = $this->forDisk($diskKey);
        $quota = $destination?->hasQuota() ? (int) $destination->quota_bytes : null;

        $percent = null;
        if ($quota !== null && $quota > 0) {
            $percent = round(($usage['bytes'] / $quota) * 100, 1);
        }

        return $usage + [
            'quota_bytes' => $quota,
            'percent' => $percent,
            'remaining_bytes' => $destination?->quotaRemaining($usage['bytes']),
            'state' => $this->pressureState($percent),
        ];
    }

    /**
     * Free space on the server disk, for the built-in local destination.
     *
     * Local has no row and therefore no quota, but it is the one destination
     * with a hard limit the platform can actually read: the filesystem the
     * database also lives on. Worth surfacing — filling it takes the whole
     * application down, not just uploads.
     *
     * @return array{free: int, total: int, percent: float}|null
     */
    public function localDiskSpace(): ?array
    {
        $path = storage_path('app/private');

        // Open-basedir and some hosts disable these outright, and a warning in
        // the middle of the admin screen is worse than the figure being absent.
        if (! is_dir($path) || ! function_exists('disk_free_space')) {
            return null;
        }

        $free = @disk_free_space($path);
        $total = @disk_total_space($path);

        if ($free === false || $total === false || $total <= 0) {
            return null;
        }

        return [
            'free' => (int) $free,
            'total' => (int) $total,
            'percent' => round((($total - $free) / $total) * 100, 1),
        ];
    }

    /**
     * Turns a fill percentage into the band the UI colours by.
     *
     * The thresholds are about lead time, not aesthetics: at 75% there is room
     * to plan a migration, at 90% there is time to raise the quota, and past
     * 100% uploads are already being refused.
     */
    private function pressureState(?float $percent): string
    {
        return match (true) {
            $percent === null => 'unlimited',
            $percent >= 100 => 'full',
            $percent >= 90 => 'critical',
            $percent >= 75 => 'warning',
            default => 'ok',
        };
    }

    /** Bytes as something a human reads, e.g. "1.4 GB". */
    public static function humanBytes(?int $bytes): string
    {
        $bytes = max(0, (int) $bytes);

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        $value = $bytes / (1024 ** $power);

        // Whole bytes and kilobytes read as noise with decimals attached.
        return $power <= 1
            ? number_format($value, 0).' '.$units[$power]
            : number_format($value, 1).' '.$units[$power];
    }
}
