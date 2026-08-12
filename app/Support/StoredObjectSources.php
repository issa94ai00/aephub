<?php

namespace App\Support;

/**
 * Every place the platform records that it has put an object on a disk.
 *
 * This list exists because getting it wrong is destructive. The storage screen
 * decides what to delete by asking "does anything reference this object", and
 * an incomplete answer to that question is not a missing feature — it is a
 * cleanup tool that removes live data. The first version of the orphan scan
 * consulted `course_files` and `course_videos` alone and duly reported every
 * course cover image on the platform as unreferenced and safe to delete.
 *
 * So the sources are declared once, here, and the three things that care —
 * consumption totals, the orphan classifier, and the destination-to-destination
 * move — all read from this. Adding a feature that stores a file means adding a
 * line here, and the alternative is that it silently becomes deletable.
 *
 * Tables are skipped at runtime if they do not exist, so an install that has
 * not run the live-session migrations is not a fatal.
 *
 * @phpstan-type ObjectSource array{table: string, disk: string, paths: list<array{column: string, size: string|null}>, kind: string}
 */
final class StoredObjectSources
{
    /**
     * Objects that are course content, counted separately on the storage screen
     * because they are the ones an admin is sizing a bucket for.
     */
    public const KIND_FILE = 'files';

    public const KIND_VIDEO = 'videos';

    /** Everything else the platform stores: covers, receipts, session media. */
    public const KIND_OTHER = 'other';

    /**
     * @return list<ObjectSource>
     */
    public static function all(): array
    {
        return [
            [
                'table' => 'course_files',
                'disk' => 'storage_disk',
                'paths' => [['column' => 'storage_path', 'size' => 'size_bytes']],
                'kind' => self::KIND_FILE,
            ],
            [
                'table' => 'course_videos',
                'disk' => 'storage_disk',
                'paths' => [['column' => 'storage_path', 'size' => 'size_bytes']],
                'kind' => self::KIND_VIDEO,
            ],
            [
                // Cover images. No size column — they count as objects held on
                // the destination but contribute nothing to the byte total,
                // which is the honest reading: the platform never recorded it.
                'table' => 'courses',
                'disk' => 'cover_image_disk',
                'paths' => [['column' => 'cover_image_path', 'size' => null]],
                'kind' => self::KIND_OTHER,
            ],
            [
                'table' => 'payment_requests',
                'disk' => 'receipt_storage_disk',
                'paths' => [['column' => 'receipt_path', 'size' => null]],
                'kind' => self::KIND_OTHER,
            ],
            [
                // One row, two objects: the asset and its thumbnail, both on
                // the disk named by the single `storage_disk` column.
                'table' => 'live_session_assets',
                'disk' => 'storage_disk',
                'paths' => [
                    ['column' => 'storage_path', 'size' => 'file_size'],
                    ['column' => 'thumbnail_path', 'size' => null],
                ],
                'kind' => self::KIND_OTHER,
            ],
            [
                'table' => 'live_session_recordings',
                'disk' => 'storage_disk',
                'paths' => [
                    ['column' => 'audio_path', 'size' => 'audio_size_bytes'],
                    ['column' => 'events_path', 'size' => 'events_size_bytes'],
                ],
                'kind' => self::KIND_OTHER,
            ],
        ];
    }
}
