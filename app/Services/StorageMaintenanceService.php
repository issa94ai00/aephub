<?php

namespace App\Services;

use App\Models\StorageDestination;
use App\Support\StoredObjectSources;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FileAttributes;
use Throwable;

/**
 * What is actually sitting on a destination, and what should not be.
 *
 * StorageUsageService answers "how many bytes has the platform written here",
 * from the database. This class answers the other half — "what is in the bucket"
 * — by listing the destination itself, and the gap between the two answers is
 * the point. Objects accumulate that no row references any more: a course
 * deleted while its video stayed behind, an upload that completed on the wire
 * and failed before the row was written, parts of a resumable upload the
 * teacher abandoned. None of them are visible from the database, and all of
 * them are billed for.
 *
 * Deleting is deliberately conservative. Every candidate is re-checked at
 * deletion time against a freshly read reference set, and anything younger than
 * the age floor is refused outright, because "no row points at this object" is
 * indistinguishable from "the row has not been written yet" during the seconds
 * an upload is completing.
 */
class StorageMaintenanceService
{
    /**
     * Objects younger than this are never treated as deletable.
     *
     * An upload writes the object first and the row second. In the window
     * between the two the object is, by every test this class can apply, an
     * orphan — and deleting it destroys a file the user is watching succeed.
     */
    public const ORPHAN_MIN_AGE_HOURS = 24;

    /**
     * A ceiling on one listing pass.
     *
     * Listing is paid for per thousand objects and held in memory here, so a
     * bucket with a million objects must not be walked in a web request. The
     * screen says when a scan was truncated rather than pretending the answer
     * is complete.
     */
    public const MAX_SCAN_OBJECTS = 5000;

    /** Marks the directory holding parts of an upload still in progress. */
    private const INCOMPLETE_UPLOAD_MARKER = '/multipart/_tmp/';

    public function __construct(
        private StorageUsageService $usage
    ) {}

    /**
     * One page of objects on a destination, each classified.
     *
     * The reclaimable set covers the whole scan rather than the page being
     * shown. Paging it too would mean an admin looking at page one of ten is
     * offered a tenth of the waste and has to walk the rest by hand — and the
     * classification for every object has already been done here anyway.
     *
     * @return array{items: list<array<string, mixed>>, total: int, truncated: bool, page: int, per_page: int, error: string|null, totals: array{tracked: int, orphan: int, incomplete: int, orphan_bytes: int, incomplete_bytes: int}, reclaimable: array{paths: list<string>, count: int, bytes: int}}
     */
    public function browse(string $diskKey, ?StorageDestination $destination, int $page = 1, int $perPage = 50): array
    {
        $page = max(1, $page);
        $perPage = max(10, min(200, $perPage));

        $empty = [
            'items' => [],
            'total' => 0,
            'truncated' => false,
            'page' => $page,
            'per_page' => $perPage,
            'error' => null,
            'totals' => [
                'tracked' => 0, 'orphan' => 0, 'incomplete' => 0,
                'orphan_bytes' => 0, 'incomplete_bytes' => 0,
            ],
            'reclaimable' => ['paths' => [], 'count' => 0, 'bytes' => 0],
        ];

        try {
            $objects = $this->scan($diskKey, $destination);
        } catch (Throwable $e) {
            // A destination with rotated keys throws here. That is information,
            // not a crash: the screen shows the provider's own words.
            return ['error' => $this->shortError($e)] + $empty;
        }

        $referenced = $this->referencedPaths($diskKey);
        $incompleteFloorHours = (int) ($destination?->option('multipart_token_ttl_hours') ?? 2);

        $items = [];
        $totals = $empty['totals'];
        $reclaimablePaths = [];
        $reclaimableBytes = 0;

        foreach ($objects['files'] as $file) {
            $item = $this->classify($file, $referenced, $incompleteFloorHours);

            $totals[$item['kind']]++;
            if ($item['kind'] !== 'tracked') {
                $totals[$item['kind'].'_bytes'] += $item['size'];
            }

            if ($item['deletable']) {
                $reclaimablePaths[] = $item['path'];
                $reclaimableBytes += $item['size'];
            }

            $items[] = $item;
        }

        // Newest first: the objects an admin is looking for after a failed
        // upload are the ones that just appeared.
        usort($items, fn (array $a, array $b) => $b['last_modified'] <=> $a['last_modified']);

        return [
            'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'total' => count($items),
            'truncated' => $objects['truncated'],
            'page' => $page,
            'per_page' => $perPage,
            'error' => null,
            'totals' => $totals,
            'reclaimable' => [
                'paths' => $reclaimablePaths,
                'count' => count($reclaimablePaths),
                'bytes' => $reclaimableBytes,
            ],
        ];
    }

    /**
     * Deletes reclaimable objects, re-checking every one first.
     *
     * The caller passes paths that were reclaimable when the page rendered. By
     * now a row may reference one of them — the upload that was mid-flight
     * finished — so the decision is taken again here against a reference set
     * read moments ago, and a path that no longer qualifies is skipped rather
     * than deleted. The posted list can only ever narrow what gets removed.
     *
     * @param  list<string>  $paths
     * @return array{deleted: int, bytes: int, skipped: int}
     */
    public function deleteReclaimable(string $diskKey, ?StorageDestination $destination, array $paths): array
    {
        if ($paths === []) {
            return ['deleted' => 0, 'bytes' => 0, 'skipped' => 0];
        }

        $referenced = $this->referencedPaths($diskKey);
        $incompleteFloorHours = (int) ($destination?->option('multipart_token_ttl_hours') ?? 2);
        $requested = array_flip(array_map('strval', $paths));

        $disk = Storage::disk($diskKey);
        $deleted = 0;
        $bytes = 0;
        $skipped = 0;

        foreach ($this->scan($diskKey, $destination)['files'] as $file) {
            $path = $file->path();

            if (! isset($requested[$path])) {
                continue;
            }

            $item = $this->classify($file, $referenced, $incompleteFloorHours);

            if (! $item['deletable']) {
                $skipped++;

                continue;
            }

            try {
                $disk->delete($path);
                $deleted++;
                $bytes += $item['size'];
            } catch (Throwable) {
                // One unreadable object should not abandon the rest of the
                // sweep; it stays listed and can be retried.
                $skipped++;
            }
        }

        if ($deleted > 0) {
            $this->usage->flush();
        }

        return ['deleted' => $deleted, 'bytes' => $bytes, 'skipped' => $skipped];
    }

    /**
     * Decides what one object is and whether it may be removed.
     *
     * @param  array<string, true>  $referenced
     * @return array<string, mixed>
     */
    private function classify(FileAttributes $file, array $referenced, int $incompleteFloorHours): array
    {
        $path = $file->path();
        $size = (int) ($file->fileSize() ?? 0);
        $modified = (int) ($file->lastModified() ?? 0);
        $ageHours = $modified > 0 ? (time() - $modified) / 3600 : 0;

        if (isset($referenced[$path])) {
            return [
                'path' => $path,
                'size' => $size,
                'last_modified' => $modified,
                'kind' => 'tracked',
                'deletable' => false,
            ];
        }

        // Parts of a resumable upload. Deletable once the window in which the
        // client could still resume has passed — before that they are a session
        // someone is expecting to continue, not waste.
        if (str_contains($path, self::INCOMPLETE_UPLOAD_MARKER)) {
            return [
                'path' => $path,
                'size' => $size,
                'last_modified' => $modified,
                'kind' => 'incomplete',
                'deletable' => $modified > 0 && $ageHours >= $incompleteFloorHours,
            ];
        }

        return [
            'path' => $path,
            'size' => $size,
            'last_modified' => $modified,
            'kind' => 'orphan',
            // An object with no usable timestamp is never deletable: age is the
            // only guard against removing an upload that is still landing.
            'deletable' => $modified > 0 && $ageHours >= self::ORPHAN_MIN_AGE_HOURS,
        ];
    }

    /**
     * Lists the destination, capped.
     *
     * Goes through Flysystem rather than `Storage::files()` on purpose: the
     * driver returns size and mtime as part of the listing response, while the
     * facade's helpers would issue a HEAD per object — thousands of round trips
     * for a page that needs one.
     *
     * @return array{files: list<FileAttributes>, truncated: bool}
     */
    private function scan(string $diskKey, ?StorageDestination $destination): array
    {
        $listing = Storage::disk($diskKey)->getDriver()->listContents($this->scanPrefix($destination), true);

        $files = [];
        $truncated = false;

        foreach ($listing as $entry) {
            if (! $entry instanceof FileAttributes) {
                continue;
            }

            if (count($files) >= self::MAX_SCAN_OBJECTS) {
                $truncated = true;
                break;
            }

            $files[] = $entry;
        }

        return ['files' => $files, 'truncated' => $truncated];
    }

    /**
     * The folder this screen is allowed to look at.
     *
     * Never the root. A destination's disk holds more than the platform's
     * uploads — the server disk also carries database backups and whatever else
     * has been dropped in `storage/app/private`, and a shared bucket carries
     * other tenants' objects. None of that is referenced by any row here, so
     * scanning the root would classify all of it as unreferenced and offer it
     * for deletion.
     *
     * The built-in local destination has no row to read a prefix from, so it
     * takes the same default a managed local destination would.
     */
    public function scanPrefix(?StorageDestination $destination): string
    {
        $prefix = trim((string) ($destination?->option('path_prefix') ?? ''), '/ ');

        return $prefix !== ''
            ? $prefix
            : (string) StorageDestination::defaultOptionsFor(StorageDestination::TYPE_LOCAL)['path_prefix'];
    }

    /**
     * Every path on this disk the database still points at.
     *
     * Built from every source in {@see StoredObjectSources}, and that
     * completeness is the safety property of this whole class. An earlier
     * version consulted `course_files` and `course_videos` only, which made
     * every course cover image on the platform look unreferenced — the cleanup
     * button would have offered to delete all of them, with the correct byte
     * total and a confident label.
     *
     * Read fresh each time. A cached copy is exactly the wrong optimisation
     * here: it would be a list of files that were referenced a minute ago, used
     * to decide what to delete now.
     *
     * @return array<string, true>
     */
    private function referencedPaths(string $diskKey): array
    {
        $paths = [];

        foreach (StoredObjectSources::all() as $source) {
            if (! Schema::hasTable($source['table'])) {
                continue;
            }

            $columns = array_column($source['paths'], 'column');

            DB::table($source['table'])
                ->where($source['disk'], $diskKey)
                ->select($columns)
                ->orderBy('id')
                // Chunked because this is every stored object on the
                // destination, and it is held in memory as a lookup.
                ->chunk(2000, function ($rows) use (&$paths, $columns) {
                    foreach ($rows as $row) {
                        foreach ($columns as $column) {
                            $path = ltrim((string) ($row->{$column} ?? ''), '/');
                            if ($path !== '') {
                                $paths[$path] = true;
                            }
                        }
                    }
                });
        }

        return $paths;
    }

    private function shortError(Throwable $e): string
    {
        return mb_substr($e->getMessage(), 0, 300);
    }
}
