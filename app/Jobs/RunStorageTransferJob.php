<?php

namespace App\Jobs;

use App\Models\StorageTransfer;
use App\Services\StorageDestinationService;
use App\Services\StorageUsageService;
use App\Support\StoredObjectSources;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Moves every stored object from one destination to another.
 *
 * Retiring a bucket is the operation the storage screen could not perform. A
 * destination cannot be deleted while files reference it, and nothing rewrote
 * those references — so a provider change meant either keeping the old account
 * paid up forever or editing `course_files` by hand.
 *
 * The order of the four steps per object is the whole design:
 *
 *   1. copy the bytes to the new destination
 *   2. verify the copy is the size the source was
 *   3. point the database row at the new disk
 *   4. only then remove the source object
 *
 * A crash at any point leaves the object readable. Between 1 and 3 the row
 * still names the source, and the copy is simply a duplicate the next run
 * overwrites. After 3 the row names the destination, where the bytes already
 * are. The failure this ordering rules out is the one that matters: a row
 * pointing at a disk the object is not on, which is a lecture nobody can play.
 *
 * Streams throughout — a course video is measured in hundreds of megabytes and
 * reading one into a PHP string to hand it to the next disk is how a worker
 * dies of memory exhaustion halfway through a migration.
 */
class RunStorageTransferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * One attempt. A transfer is resumable by construction — every query
     * selects only rows still sitting on the source disk — so a retry would
     * pick up where this left off anyway, but it would do it silently and with
     * the counters already spent. Restarting is the admin's call.
     */
    public int $tries = 1;

    /** Hours, not minutes: this is thousands of objects over the network. */
    public int $timeout = 10800;

    /** How often progress reaches the database, in items. */
    private const PROGRESS_FLUSH_EVERY = 10;

    /** Resolved once per run so moveObject() does not re-read the transfer per object. */
    private string $targetDisk = '';

    private bool $deleteSource = false;

    public function __construct(
        public readonly int $transferId,
    ) {}

    public function handle(StorageUsageService $usage, StorageDestinationService $destinations): void
    {
        $transfer = StorageTransfer::find($this->transferId);

        if (! $transfer || ! $transfer->isActive()) {
            return;
        }

        // Both sides addressing one directory or bucket would copy every object
        // onto itself and then delete it. Checked at dispatch too; repeated
        // here because a queued job outlives the request that validated it, and
        // a destination can be edited in between.
        if ($destinations->sameUnderlyingLocation((string) $transfer->from_disk, (string) $transfer->to_disk)) {
            $this->finish($transfer, StorageTransfer::STATUS_FAILED, 'Source and destination are the same storage location.');

            return;
        }

        $transfer->forceFill([
            'status' => StorageTransfer::STATUS_RUNNING,
            'started_at' => $transfer->started_at ?? now(),
        ])->save();

        $this->countWork($transfer);

        try {
            foreach (StoredObjectSources::all() as $source) {
                if ($this->moveSource($transfer, $source) === false) {
                    $this->finish($transfer, StorageTransfer::STATUS_CANCELLED, 'Cancelled by an administrator.');
                    $usage->flush();

                    return;
                }
            }
        } catch (Throwable $e) {
            $this->finish($transfer, StorageTransfer::STATUS_FAILED, $e->getMessage());
            $usage->flush();

            throw $e;
        }

        $transfer->refresh();

        $this->finish(
            $transfer,
            StorageTransfer::STATUS_COMPLETED,
            $transfer->failed_items > 0
                ? "Finished with {$transfer->failed_items} object(s) that could not be moved."
                : null
        );

        $usage->flush();
    }

    /**
     * Moves one source's rows.
     *
     * @param  array<string, mixed>  $source  one entry from StoredObjectSources
     * @return bool false when a cancellation was requested and honoured
     */
    private function moveSource(StorageTransfer $transfer, array $source): bool
    {
        if (! Schema::hasTable($source['table'])) {
            return true;
        }

        $from = Storage::disk($transfer->from_disk);
        $to = Storage::disk($transfer->to_disk);

        $columns = ['id', ...array_column($source['paths'], 'column')];

        $lastId = 0;
        $sinceFlush = 0;
        $moved = 0;
        $movedBytes = 0;
        $failed = 0;

        while (true) {
            // Keyset pagination rather than chunkById: rows leave the result set
            // as they are moved, so an offset-based walk would skip whatever
            // slid into the pages it had already passed.
            $rows = DB::table($source['table'])
                ->where($source['disk'], $transfer->from_disk)
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit(100)
                ->get($columns);

            if ($rows->isEmpty()) {
                break;
            }

            foreach ($rows as $row) {
                $lastId = (int) $row->id;

                try {
                    $movedBytes += $this->moveRow($from, $to, $source, $row);
                    $moved++;
                } catch (Throwable $e) {
                    $failed++;

                    Log::warning('Storage transfer could not move an object', [
                        'transfer_id' => $transfer->id,
                        'table' => $source['table'],
                        'row_id' => $row->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                if (++$sinceFlush >= self::PROGRESS_FLUSH_EVERY) {
                    $this->flushProgress($transfer, $moved, $movedBytes, $failed);
                    $moved = $movedBytes = $failed = $sinceFlush = 0;

                    // Read back rather than trusting the in-memory row: the
                    // cancel flag is set by a web request in another process.
                    if ((bool) DB::table('storage_transfers')->where('id', $transfer->id)->value('cancel_requested')) {
                        return false;
                    }
                }
            }
        }

        $this->flushProgress($transfer, $moved, $movedBytes, $failed);

        return true;
    }

    /**
     * Copies every object a row owns, then repoints the row.
     *
     * A row can own more than one object — a live-session asset has its media
     * and its thumbnail, both on the disk named by one column. They move
     * together or not at all: repointing the row after copying only the first
     * would leave the second unreachable, since the single disk column now
     * names a destination it was never copied to.
     *
     * Paths are kept exactly as they were. A destination's `path_prefix`
     * governs where *new* uploads land; rewriting existing keys mid-migration
     * buys nothing, because an object is found by the path in its row whatever
     * that path happens to be.
     *
     * @param  array<string, mixed>  $source
     * @return int bytes moved
     */
    private function moveRow(
        \Illuminate\Contracts\Filesystem\Filesystem $from,
        \Illuminate\Contracts\Filesystem\Filesystem $to,
        array $source,
        object $row
    ): int {
        $copied = [];
        $bytes = 0;

        foreach ($source['paths'] as $pathColumn) {
            $path = ltrim((string) ($row->{$pathColumn['column']} ?? ''), '/');

            // Nullable path columns are normal — a thumbnail that was never
            // generated is not a failure.
            if ($path === '') {
                continue;
            }

            if (! $from->exists($path)) {
                // The row points at nothing on the source. Repointing it would
                // move a broken reference onto a healthy destination and hide
                // the breakage; it is left alone and counted as a failure.
                throw new \RuntimeException("Source object is missing: {$path}");
            }

            $bytes += $this->copyObject($from, $to, $path);
            $copied[] = $path;
        }

        DB::table($source['table'])
            ->where('id', $row->id)
            ->update([$source['disk'] => $this->targetDisk]);

        if ($this->deleteSource) {
            foreach ($copied as $path) {
                // Last, and tolerated if it fails: a leftover object on the old
                // destination costs storage, while deleting before the repoint
                // costs the file.
                try {
                    $from->delete($path);
                } catch (Throwable $e) {
                    Log::info('Storage transfer left the source object in place', [
                        'path' => $path,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $bytes;
    }

    /**
     * Streams one object across and proves it arrived whole.
     *
     * @return int bytes copied
     */
    private function copyObject(
        \Illuminate\Contracts\Filesystem\Filesystem $from,
        \Illuminate\Contracts\Filesystem\Filesystem $to,
        string $path
    ): int {
        $stream = $from->readStream($path);

        if ($stream === false || $stream === null) {
            throw new \RuntimeException("Source object could not be opened: {$path}");
        }

        try {
            $to->writeStream($path, $stream);
        } finally {
            // writeStream closes it on most drivers, but not being sure is not
            // a reason to leak a handle per object across a 10,000 file move.
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $sourceSize = (int) $from->size($path);
        $targetSize = (int) $to->size($path);

        // A short write is the failure mode that silently destroys data: the
        // copy looks like it worked, the row is repointed, and the video is
        // truncated. Compared before anything becomes irreversible.
        if ($sourceSize !== $targetSize) {
            $to->delete($path);

            throw new \RuntimeException("Copy size mismatch for {$path}: {$sourceSize} vs {$targetSize}.");
        }

        return $sourceSize;
    }

    /**
     * Establishes the denominator for the progress bar.
     *
     * An estimate, and treated as one: a teacher can upload to the source disk
     * while this runs, and the bar is allowed to be slightly wrong rather than
     * the migration being allowed to lock the table.
     */
    private function countWork(StorageTransfer $transfer): void
    {
        $this->targetDisk = (string) $transfer->to_disk;
        $this->deleteSource = (bool) $transfer->delete_source;

        $items = 0;
        $bytes = 0;

        foreach (StoredObjectSources::all() as $source) {
            if (! Schema::hasTable($source['table'])) {
                continue;
            }

            // Counted in rows, because a row is what the loop moves and what
            // the progress counter increments — a live-session asset carrying
            // a thumbnail is one unit of work here, not two.
            $items += DB::table($source['table'])->where($source['disk'], $transfer->from_disk)->count();

            foreach ($source['paths'] as $path) {
                if ($path['size'] === null) {
                    continue;
                }

                $bytes += (int) DB::table($source['table'])
                    ->where($source['disk'], $transfer->from_disk)
                    ->sum($path['size']);
            }
        }

        $transfer->forceFill([
            'total_items' => $items,
            'total_bytes' => $bytes,
            'moved_items' => 0,
            'moved_bytes' => 0,
            'failed_items' => 0,
        ])->save();
    }

    /** Adds a batch of progress atomically, so a concurrent read never sees it go backwards. */
    private function flushProgress(StorageTransfer $transfer, int $moved, int $movedBytes, int $failed): void
    {
        if ($moved === 0 && $failed === 0) {
            return;
        }

        DB::table('storage_transfers')
            ->where('id', $transfer->id)
            ->update([
                'moved_items' => DB::raw('moved_items + '.$moved),
                'moved_bytes' => DB::raw('moved_bytes + '.$movedBytes),
                'failed_items' => DB::raw('failed_items + '.$failed),
                'updated_at' => now(),
            ]);
    }

    private function finish(StorageTransfer $transfer, string $status, ?string $message): void
    {
        $transfer->forceFill([
            'status' => $status,
            'message' => $message === null ? null : mb_substr($message, 0, 1000),
            'finished_at' => now(),
        ])->save();
    }

    public function failed(Throwable $exception): void
    {
        $transfer = StorageTransfer::find($this->transferId);

        if ($transfer && $transfer->isActive()) {
            $this->finish($transfer, StorageTransfer::STATUS_FAILED, $exception->getMessage());
        }

        Log::error('Storage transfer failed', [
            'transfer_id' => $this->transferId,
            'error' => $exception->getMessage(),
        ]);
    }
}
