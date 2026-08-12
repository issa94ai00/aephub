<?php

namespace App\Services;

use App\Models\StorageDestination;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Owns where uploads go.
 *
 * Storage was a single setting: one provider, one credential set, applied over
 * the matching disk in `SiteSettingsService::applyToConfig()`. The platform
 * could therefore only know one bucket at a time, and an upload had no
 * destination to be pointed at — it took `filesystems.default` and, failing
 * that, walked a hardcoded `wasabi → r2 → local` list built from `.env`.
 *
 * Destinations are rows now. This class turns them into filesystem disks at
 * boot and answers the one question the upload paths actually have: which disk
 * does a new video belong on.
 *
 * The disks in `config/filesystems.php` are left alone. They are the floor the
 * app falls back to, and `local` in particular must keep working whether or not
 * anyone has ever opened the storage screen.
 */
class StorageDestinationService
{
    private const CACHE_KEY = 'storage_destinations.active.v1';

    /**
     * Active destinations, cached because this runs on every boot.
     *
     * @return Collection<int, StorageDestination>
     */
    public function active(): Collection
    {
        // Missing table means the migration has not run yet — during `migrate`
        // itself, for one. Booting must not fail because of it.
        if (! Schema::hasTable('storage_destinations')) {
            return collect();
        }

        $rows = Cache::rememberForever(self::CACHE_KEY, fn () => StorageDestination::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->all());

        return collect($rows);
    }

    /** Drops the cached set. Call after any write to a destination. */
    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Registers every active destination as a filesystem disk.
     *
     * Reserved keys are skipped rather than overwritten: a destination that
     * called itself `local` would replace the disk the whole application falls
     * back to, and every existing file recorded as living on `local` would start
     * resolving to someone's bucket.
     */
    public function registerDisks(): void
    {
        foreach ($this->active() as $destination) {
            $key = (string) $destination->disk_key;

            if ($key === '' || in_array($key, StorageDestination::RESERVED_DISK_KEYS, true)) {
                continue;
            }

            if (! $destination->isConfigured()) {
                continue;
            }

            config(["filesystems.disks.{$key}" => $destination->toDiskConfig()]);
        }
    }

    /** The destination new uploads should go to, or `null` when none is usable. */
    public function defaultDestination(): ?StorageDestination
    {
        $active = $this->active()->filter(fn (StorageDestination $d) => $d->isConfigured());

        return $active->firstWhere('is_default', true) ?? $active->first();
    }

    /**
     * The disk name a new video should be written to.
     *
     * Falls back to `local` rather than to `filesystems.default`: the default
     * disk is whatever `.env` happens to say, and using it here is how an upload
     * ends up in a bucket nobody selected on this screen.
     */
    public function uploadDisk(): string
    {
        return (string) ($this->defaultDestination()?->disk_key ?? 'local');
    }

    /**
     * Every destination an admin can choose between, with its live state.
     *
     * `local` is always listed — it needs no configuring and is what the app
     * uses when nothing else is set up — so the screen shows the real set of
     * options rather than only the rows someone has created.
     *
     * @return list<array<string, mixed>>
     */
    public function options(): array
    {
        $defaultKey = $this->defaultDestination()?->disk_key;

        $rows = [[
            'id' => null,
            'name' => __('admin.storage_settings.provider_local'),
            'disk_key' => 'local',
            'driver' => 'local',
            'bucket' => null,
            'endpoint' => null,
            'is_active' => true,
            'is_configured' => true,
            'is_default' => $defaultKey === null || $defaultKey === 'local',
            'is_builtin' => true,
        ]];

        if (! Schema::hasTable('storage_destinations')) {
            return $rows;
        }

        foreach (StorageDestination::query()->orderBy('id')->get() as $destination) {
            $rows[] = [
                'id' => $destination->id,
                'name' => $destination->name,
                'disk_key' => $destination->disk_key,
                'driver' => $destination->driver,
                'bucket' => $destination->bucket,
                'endpoint' => $destination->endpoint,
                'is_active' => (bool) $destination->is_active,
                'is_configured' => $destination->isConfigured(),
                'is_default' => $destination->disk_key === $defaultKey,
                'is_builtin' => false,
            ];
        }

        return $rows;
    }

    /**
     * Makes one destination the default, in a single transaction-free sweep.
     *
     * Clearing every other row first keeps the "exactly one default" invariant
     * that `defaultDestination()` would otherwise have to guess at.
     */
    public function makeDefault(StorageDestination $destination): void
    {
        StorageDestination::query()->where('id', '!=', $destination->id)->update(['is_default' => false]);
        $destination->forceFill(['is_default' => true, 'is_active' => true])->save();

        $this->flushCache();
    }

    /**
     * Proves the credentials work by listing the bucket.
     *
     * A saved destination is only a claim; this is the difference between
     * finding out now and finding out after a teacher has uploaded 150MB.
     *
     * @return array{ok: bool, message: string}
     */
    public function testConnection(StorageDestination $destination): array
    {
        if (! $destination->isConfigured()) {
            $message = __('admin.storage_settings.test_incomplete');
            $destination->recordCheck(false, $message);

            return ['ok' => false, 'message' => $message, 'latency_ms' => null];
        }

        $key = 'storage-destination-test-'.$destination->id;
        $startedAt = microtime(true);

        try {
            // Registered under a scratch name so a failing test cannot disturb
            // the disk the application is currently serving files from.
            config(["filesystems.disks.{$key}" => $destination->toDiskConfig()]);

            Storage::disk($key)->files();

            // A bucket that answers is not the same as a bucket that answers
            // quickly. Four seconds to list is a destination that will make
            // every part of a multipart upload crawl, and the number is the
            // only warning of that before the uploads start.
            $latency = (int) round((microtime(true) - $startedAt) * 1000);
            $message = __('admin.storage_settings.test_ok');

            $destination->recordCheck(true, $message, $latency);

            return ['ok' => true, 'message' => $message, 'latency_ms' => $latency];
        } catch (Throwable $e) {
            $latency = (int) round((microtime(true) - $startedAt) * 1000);
            $destination->recordCheck(false, $e->getMessage(), $latency);

            return ['ok' => false, 'message' => $e->getMessage(), 'latency_ms' => $latency];
        } finally {
            config(["filesystems.disks.{$key}" => null]);
            Storage::forgetDisk($key);
        }
    }

    /**
     * Runs the check over every configured destination.
     *
     * Health that has to be requested per destination is health nobody looks
     * at. One button, and the badges on the list mean something afterwards.
     *
     * @return array{checked: int, failed: int}
     */
    public function testAll(): array
    {
        $checked = 0;
        $failed = 0;

        foreach (StorageDestination::query()->get() as $destination) {
            if (! $destination->isConfigured()) {
                continue;
            }

            $checked++;

            if (! $this->testConnection($destination)['ok']) {
                $failed++;
            }
        }

        return ['checked' => $checked, 'failed' => $failed];
    }

    /**
     * Whether two disk keys are really the same place.
     *
     * Different keys do not mean different storage. Every local destination
     * resolves to `storage/app/private` — the root is not derived from the disk
     * key — so `local` and a second local destination are two names for one
     * directory. The same is true of two S3 destinations pointed at one bucket.
     *
     * This matters for the move: the copy would open an object for reading and
     * write to that identical path, and then, with "delete the source" enabled,
     * remove it. The file is destroyed by a feature whose entire purpose is to
     * preserve it. Checked before a transfer is allowed to start.
     */
    public function sameUnderlyingLocation(string $diskA, string $diskB): bool
    {
        if ($diskA === $diskB) {
            return true;
        }

        $a = $this->locationFingerprint($diskA);
        $b = $this->locationFingerprint($diskB);

        // An unresolvable disk is not proof of difference. Refusing the move is
        // the recoverable outcome; allowing it is not.
        return $a === null || $b === null || $a === $b;
    }

    /**
     * What a disk actually addresses, as a comparable string.
     *
     * Credentials are deliberately excluded: two key pairs for the same bucket
     * still name the same objects.
     */
    private function locationFingerprint(string $diskKey): ?string
    {
        $config = config("filesystems.disks.{$diskKey}");

        if (! is_array($config)) {
            return null;
        }

        $driver = (string) ($config['driver'] ?? '');

        if ($driver === 'local') {
            $root = (string) ($config['root'] ?? '');

            // realpath() normalises the separators and any `..`, so two spellings
            // of one directory do not read as two directories.
            return 'local:'.(realpath($root) ?: $root);
        }

        if ($driver === 's3') {
            return 's3:'.rtrim((string) ($config['endpoint'] ?? ''), '/').'/'.(string) ($config['bucket'] ?? '');
        }

        return null;
    }

    /**
     * Whether an upload of this size may go to the given disk.
     *
     * Consulted at upload init rather than at completion. A quota that is only
     * noticed once the bytes have arrived has already cost what it exists to
     * prevent, and leaves the client with no way to say why the upload died.
     *
     * Disks with no destination row behind them — `local`, and anything defined
     * in `.env` — have no quota and always accept.
     *
     * @return array{allowed: bool, used: int, quota: int|null}
     */
    public function quotaCheck(string $diskKey, int $incomingBytes = 0): array
    {
        $destination = $this->active()->firstWhere('disk_key', $diskKey);
        $used = app(StorageUsageService::class)->bytesOn($diskKey);

        if (! $destination || ! $destination->hasQuota()) {
            return ['allowed' => true, 'used' => $used, 'quota' => null];
        }

        return [
            'allowed' => $destination->canAccept($used, $incomingBytes),
            'used' => $used,
            'quota' => (int) $destination->quota_bytes,
        ];
    }
}
