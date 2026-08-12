<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunStorageTransferJob;
use App\Models\StorageDestination;
use App\Models\StorageTransfer;
use App\Services\StorageDestinationService;
use App\Services\StorageMaintenanceService;
use App\Services\StorageUsageService;
use App\Support\AdminInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Storage management.
 *
 * This screen used to configure a single provider: one bucket, one credential
 * set, applied over the matching disk at boot. It grew into the set of
 * destinations a video can be uploaded to, and which one new uploads go to.
 *
 * It now also answers the questions that come after "where do uploads go":
 * how full each destination is against its quota, whether it still answers and
 * how fast, what is sitting on it that nothing references any more, and how to
 * get everything off one destination and onto another so the old one can be
 * retired. The heavy lifting is in three services and a job — this class
 * validates, dispatches, and hands the view what it needs to render.
 *
 * Rendered through {@see AdminInertia}, like every other admin screen. It was
 * the last controller still on `AdminFrame`, which builds a standalone Blade
 * page with its own sidebar — so storage management sat outside the Vue shell
 * and never appeared in its navigation, whatever the nav list said.
 */
class StorageSettingsWebController extends Controller
{
    public function __construct(
        private StorageDestinationService $destinations,
        private StorageUsageService $usage,
        private StorageMaintenanceService $maintenance,
    ) {}

    public function index(): Response
    {
        $rows = StorageDestination::query()->orderBy('id')->get();

        // Each option carries its own consumption so the list can show a fill
        // bar per destination rather than one total that hides which bucket is
        // the one about to run out.
        $options = array_map(function (array $option) use ($rows): array {
            $destination = $option['id'] === null ? null : $rows->firstWhere('id', $option['id']);

            return $option + [
                'usage' => $this->usage->summaryFor($destination, $option['disk_key']),
                'health' => $destination?->healthState() ?? 'builtin',
                'last_checked_at' => $destination?->last_checked_at,
                'last_check_message' => $destination?->last_check_message,
                'latency_ms' => $destination?->last_check_latency_ms,
            ];
        }, $this->destinations->options());

        return AdminInertia::frame('admin.storage-settings.index', [
            'options' => $options,
            'rows' => $rows,
            'localSpace' => $this->usage->localDiskSpace(),
            'activeTransfer' => StorageTransfer::query()->active()->latest('id')->first(),
            'recentTransfers' => StorageTransfer::query()
                ->whereNotIn('status', StorageTransfer::ACTIVE_STATUSES)
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }

    /**
     * Objects actually present on a destination, and what the platform makes
     * of each one.
     *
     * Keyed by disk rather than by destination id because `local` is a real
     * destination with no row behind it, and it is the one most likely to be
     * quietly full of abandoned upload parts.
     */
    public function browse(Request $request, string $disk): Response
    {
        [$diskKey, $destination] = $this->resolveDisk($disk);

        $listing = $this->maintenance->browse(
            $diskKey,
            $destination,
            (int) $request->integer('page', 1),
            50
        );

        return AdminInertia::frame('admin.storage-settings.browse', [
            'diskKey' => $diskKey,
            'destination' => $destination,
            'listing' => $listing,
            'usage' => $this->usage->summaryFor($destination, $diskKey),
            'scanPrefix' => $this->maintenance->scanPrefix($destination),
        ]);
    }

    /**
     * Removes objects the platform no longer references.
     *
     * The posted paths are candidates, not instructions: the service re-derives
     * what is safe to delete at the moment of deletion, so a file that gained a
     * row since the page rendered survives regardless of what was submitted.
     */
    public function cleanup(Request $request, string $disk): RedirectResponse
    {
        [$diskKey, $destination] = $this->resolveDisk($disk);

        $data = $request->validate([
            'paths' => ['required', 'array', 'max:'.StorageMaintenanceService::MAX_SCAN_OBJECTS],
            'paths.*' => ['string', 'max:2048'],
        ]);

        $result = $this->maintenance->deleteReclaimable($diskKey, $destination, $data['paths']);

        return redirect()
            ->route('admin.storage-settings.browse', ['disk' => $diskKey])
            ->with('status', __('admin.flash.storage_cleanup_done', [
                'count' => $result['deleted'],
                'size' => StorageUsageService::humanBytes($result['bytes']),
            ]));
    }

    /** Re-checks every configured destination in one pass. */
    public function checkAll(): RedirectResponse
    {
        $result = $this->destinations->testAll();

        if ($result['failed'] > 0) {
            return redirect()
                ->route('admin.storage-settings.index')
                ->withErrors(['test' => __('admin.storage_settings.check_all_failed', $result)]);
        }

        return redirect()
            ->route('admin.storage-settings.index')
            ->with('status', __('admin.storage_settings.check_all_ok', $result));
    }

    /**
     * Starts moving everything off one destination and onto another.
     *
     * One at a time. Two transfers touching the same disk would race on the
     * `storage_disk` column of the same rows, and the second would find its
     * work already half done by the first with no way to tell that from a
     * failure.
     */
    public function startTransfer(Request $request): RedirectResponse
    {
        if (StorageTransfer::query()->active()->exists()) {
            return redirect()
                ->route('admin.storage-settings.index')
                ->withErrors(['transfer' => __('admin.storage_settings.transfer_already_running')]);
        }

        $disks = array_column($this->destinations->options(), 'disk_key');

        $data = $request->validate([
            'from_disk' => ['required', 'string', Rule::in($disks)],
            'to_disk' => ['required', 'string', Rule::in($disks), 'different:from_disk'],
            'delete_source' => ['nullable', 'boolean'],
        ]);

        // Sending files at a destination that is missing half its credentials
        // fails on the first object and leaves the admin with a failed transfer
        // instead of an explanation.
        $target = StorageDestination::query()->where('disk_key', $data['to_disk'])->first();

        if ($target && ! $target->isConfigured()) {
            return redirect()
                ->route('admin.storage-settings.index')
                ->withErrors(['transfer' => __('admin.storage_settings.transfer_target_unconfigured')]);
        }

        // Two disk keys can address one directory or one bucket — every local
        // destination resolves to the same folder. Moving between them would
        // copy each object onto itself and then, with the delete enabled,
        // remove it. `different:from_disk` above only compares the names.
        if ($this->destinations->sameUnderlyingLocation($data['from_disk'], $data['to_disk'])) {
            return redirect()
                ->route('admin.storage-settings.index')
                ->withErrors(['transfer' => __('admin.storage_settings.transfer_same_location')]);
        }

        $transfer = StorageTransfer::create([
            'from_disk' => $data['from_disk'],
            'to_disk' => $data['to_disk'],
            'status' => StorageTransfer::STATUS_PENDING,
            'delete_source' => $request->boolean('delete_source'),
        ]);

        RunStorageTransferJob::dispatch($transfer->id);

        return redirect()
            ->route('admin.storage-settings.index')
            ->with('status', __('admin.flash.storage_transfer_started'));
    }

    /**
     * Asks a running transfer to stop.
     *
     * A flag rather than a kill: the worker is another process, and stopping it
     * mid-object is how a row ends up pointing at a half-written copy. It
     * finishes what it is holding and stops at the next boundary.
     */
    public function cancelTransfer(StorageTransfer $transfer): RedirectResponse
    {
        if (! $transfer->isActive()) {
            return redirect()
                ->route('admin.storage-settings.index')
                ->withErrors(['transfer' => __('admin.storage_settings.transfer_not_running')]);
        }

        $transfer->forceFill(['cancel_requested' => true])->save();

        return redirect()
            ->route('admin.storage-settings.index')
            ->with('status', __('admin.flash.storage_transfer_cancelling'));
    }

    /**
     * Turns a disk key from the URL into the destination behind it.
     *
     * Only keys the storage screen itself offers are accepted. Without that a
     * crafted URL would list any disk in `config/filesystems.php` — including
     * `public`, which is not a storage destination and whose contents are not
     * the platform's to sweep.
     *
     * @return array{0: string, 1: StorageDestination|null}
     */
    private function resolveDisk(string $disk): array
    {
        $known = array_column($this->destinations->options(), 'disk_key');

        if (! in_array($disk, $known, true)) {
            throw new NotFoundHttpException();
        }

        return [$disk, StorageDestination::query()->where('disk_key', $disk)->first()];
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $destination = StorageDestination::create($data + ['is_active' => true]);
        $this->destinations->flushCache();

        if ($request->boolean('make_default')) {
            $this->destinations->makeDefault($destination);
        }

        return redirect()
            ->route('admin.storage-settings.index')
            ->with('status', __('admin.flash.storage_destination_created'));
    }

    public function update(Request $request, StorageDestination $destination): RedirectResponse
    {
        $data = $this->validated($request, $destination);

        // The key is the disk name recorded on every file already stored here,
        // so it is set once and never edited. Changing it would leave those rows
        // pointing at a disk that no longer exists.
        unset($data['disk_key']);

        // A blank secret means "leave it alone" rather than "clear it": the form
        // cannot show the stored value, so echoing an empty field back would
        // wipe working credentials on every unrelated edit.
        foreach (['access_key', 'secret_key'] as $secret) {
            if (trim((string) ($data[$secret] ?? '')) === '') {
                unset($data[$secret]);
            }
        }

        $destination->update($data);
        $this->destinations->flushCache();

        if ($request->boolean('make_default')) {
            $this->destinations->makeDefault($destination);
        }

        return redirect()
            ->route('admin.storage-settings.index')
            ->with('status', __('admin.flash.storage_destination_updated'));
    }

    /** Makes a destination the target for new uploads. */
    public function makeDefault(StorageDestination $destination): RedirectResponse
    {
        if (! $destination->isConfigured()) {
            return redirect()
                ->route('admin.storage-settings.index')
                ->withErrors(['default' => __('admin.storage_settings.cannot_default_unconfigured')]);
        }

        $this->destinations->makeDefault($destination);

        return redirect()
            ->route('admin.storage-settings.index')
            ->with('status', __('admin.flash.storage_destination_default_set'));
    }

    /** Proves the credentials work before a teacher trusts a 150MB upload to them. */
    public function test(StorageDestination $destination): RedirectResponse
    {
        $result = $this->destinations->testConnection($destination);

        if (! $result['ok']) {
            return redirect()
                ->route('admin.storage-settings.index')
                ->withErrors(['test' => $result['message']]);
        }

        return redirect()
            ->route('admin.storage-settings.index')
            ->with('status', $result['message']);
    }

    public function destroy(StorageDestination $destination): RedirectResponse
    {
        // Anything recorded against this disk would become unreadable: the row
        // is what supplies the bucket and credentials to read it back. Videos
        // count as much as files here — they carry the same `storage_disk`, and
        // checking only `course_files` let a destination holding every lecture
        // on the platform be deleted without complaint.
        $filesStored = $this->usage->forDisk((string) $destination->disk_key)['items'];

        if ($filesStored > 0) {
            return redirect()
                ->route('admin.storage-settings.index')
                ->withErrors(['destroy' => __('admin.storage_settings.cannot_delete_in_use', ['count' => $filesStored])]);
        }

        $destination->delete();
        $this->destinations->flushCache();

        return redirect()
            ->route('admin.storage-settings.index')
            ->with('status', __('admin.flash.storage_destination_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * Validates the option set for a type, and drops anything that type does
     * not use.
     *
     * The floors are the backend's, not ours: S3 rejects a multipart part under
     * 5 MiB outright, so accepting 2 here would produce a destination whose
     * every upload fails on the first PUT. Enforced again in the model on read,
     * because a row can also arrive from a seeder or a direct edit.
     *
     * @return array<string, int|string>
     */
    private function validatedOptions(Request $request, string $type): array
    {
        $isObjectStorage = in_array($type, [StorageDestination::TYPE_S3, StorageDestination::TYPE_R2], true);
        $partFloor = $isObjectStorage ? StorageDestination::S3_MIN_PART_MB : 1;

        $rules = [
            'options.path_prefix' => ['nullable', 'string', 'max:120', 'regex:/^[a-zA-Z0-9_\-\/]+$/'],
            'options.part_size_mb' => ['nullable', 'integer', 'min:'.$partFloor, 'max:512'],
            'options.recommended_part_size_mb' => ['nullable', 'integer', 'min:'.$partFloor, 'max:512'],
            'options.recommended_parallel_parts' => ['nullable', 'integer', 'min:1', 'max:16'],
            'options.signed_url_ttl_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'options.multipart_token_ttl_hours' => ['nullable', 'integer', 'min:1', 'max:72'],
        ];

        // Only the local backend assembles parts in PHP, so only it has a
        // ceiling on a single request body.
        if ($type === StorageDestination::TYPE_LOCAL) {
            $rules['options.max_part_mb'] = ['nullable', 'integer', 'min:1', 'max:2048'];
        }

        $validated = $request->validate($rules, [], [
            'options.part_size_mb' => __('admin.storage_settings.opt_part_size'),
            'options.recommended_part_size_mb' => __('admin.storage_settings.opt_recommended_part_size'),
            'options.max_part_mb' => __('admin.storage_settings.opt_max_part'),
        ]);

        // Blank means "use the type default", so empties are dropped rather
        // than stored as zeros that would then be clamped into nonsense.
        return collect($validated['options'] ?? [])
            ->reject(fn ($value) => $value === null || $value === '')
            ->all();
    }

    private function validated(Request $request, ?StorageDestination $existing = null): array
    {
        $diskKeyRule = [
            'required',
            'string',
            'max:64',
            // A disk key becomes a config path segment and a stored column
            // value; anything but a plain slug makes both ambiguous.
            'regex:/^[a-z0-9_-]+$/',
            Rule::notIn(StorageDestination::RESERVED_DISK_KEYS),
            Rule::unique('storage_destinations', 'disk_key')->ignore($existing?->id),
        ];

        // The type decides which fields are even asked for. A local destination
        // has no bucket and no keys, and requiring them made it impossible to
        // create one — which is why `driver` used to be the literal 's3'.
        $type = $existing?->driver ?? $request->input('driver', StorageDestination::TYPE_S3);
        if (! in_array($type, StorageDestination::TYPES, true)) {
            $type = StorageDestination::TYPE_S3;
        }

        $isObjectStorage = in_array($type, [StorageDestination::TYPE_S3, StorageDestination::TYPE_R2], true);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'disk_key' => $existing ? ['nullable'] : $diskKeyRule,
            // Asked for in gigabytes because that is the unit a storage plan is
            // sold in; stored as bytes because that is what usage is measured
            // in and converting once here beats converting at every comparison.
            'quota_gb' => ['nullable', 'numeric', 'min:0', 'max:1048576'],
        ];

        if ($isObjectStorage) {
            $rules += [
                'bucket' => ['required', 'string', 'max:255'],
                'endpoint' => ['required', 'string', 'max:2048', 'url'],
                'region' => ['nullable', 'string', 'max:255'],
                'access_key' => [$existing ? 'nullable' : 'required', 'string', 'max:255'],
                'secret_key' => [$existing ? 'nullable' : 'required', 'string', 'max:255'],
            ];
        }

        $data = $request->validate($rules);

        $data['driver'] = $type;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['options'] = $this->validatedOptions($request, $type);

        // Blank and zero both mean "no ceiling". Storing zero instead would make
        // every destination full the moment the field was cleared.
        $quotaGb = $data['quota_gb'] ?? null;
        unset($data['quota_gb']);
        $data['quota_bytes'] = ($quotaGb === null || (float) $quotaGb <= 0)
            ? null
            : (int) round((float) $quotaGb * 1024 ** 3);

        if ($isObjectStorage) {
            // R2 is only addressable path-style, so the checkbox is not a
            // choice there — enforcing it here stops a destination being saved
            // in a shape that cannot sign a request.
            $data['use_path_style'] = $type === StorageDestination::TYPE_R2
                ? true
                : $request->boolean('use_path_style');

            $data['region'] = trim((string) ($data['region'] ?? ''))
                ?: ($type === StorageDestination::TYPE_R2 ? 'auto' : null);
        } else {
            // Clear the object-storage fields so a destination switched to
            // local does not keep a stale bucket that `isConfigured()` reads.
            $data += ['bucket' => null, 'endpoint' => null, 'region' => null, 'use_path_style' => false];
        }

        return $data;
    }
}
