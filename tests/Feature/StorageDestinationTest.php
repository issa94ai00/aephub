<?php

namespace Tests\Feature;

use App\Models\StorageDestination;
use App\Services\StorageDestinationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Where an uploaded video actually lands.
 *
 * Storage was a single setting, so the platform could know one bucket at a time
 * and an upload had no destination to be aimed at — it took whatever `.env` had
 * made the default disk. These tests hold the new behaviour: destinations are
 * rows, they become real filesystem disks, and the one marked default is where
 * new uploads go.
 */
class StorageDestinationTest extends TestCase
{
    use RefreshDatabase;

    private function destination(array $overrides = []): StorageDestination
    {
        return StorageDestination::create(array_merge([
            'name' => 'Wasabi Main',
            'disk_key' => 'wasabi-main',
            'driver' => 's3',
            'bucket' => 'lms-videos',
            'endpoint' => 'https://s3.eu-central-1.wasabisys.com',
            'region' => 'eu-central-1',
            'access_key' => 'AKIAEXAMPLE',
            'secret_key' => 'secret-value',
            'use_path_style' => false,
            'is_active' => true,
            'is_default' => false,
        ], $overrides));
    }

    private function service(): StorageDestinationService
    {
        $service = app(StorageDestinationService::class);
        $service->flushCache();

        return $service;
    }

    public function test_uploads_go_to_local_when_no_destination_is_configured(): void
    {
        // The floor: an install that has never opened the storage screen still
        // stores files somewhere sane.
        $this->assertSame('local', $this->service()->uploadDisk());
    }

    public function test_the_default_destination_is_the_upload_target(): void
    {
        $this->destination(['is_default' => true]);

        $this->assertSame('wasabi-main', $this->service()->uploadDisk());
    }

    public function test_an_incomplete_destination_is_never_the_upload_target(): void
    {
        // Saved but missing an endpoint: a configuration someone started and did
        // not finish. Sending a 150MB video at it fails only after the upload.
        $this->destination(['is_default' => true, 'endpoint' => null]);

        $this->assertSame('local', $this->service()->uploadDisk());
    }

    public function test_a_disabled_destination_is_never_the_upload_target(): void
    {
        $this->destination(['is_default' => true, 'is_active' => false]);

        $this->assertSame('local', $this->service()->uploadDisk());
    }

    public function test_active_destinations_become_filesystem_disks(): void
    {
        $this->destination(['is_default' => true]);

        $this->service()->registerDisks();

        $this->assertSame('s3', config('filesystems.disks.wasabi-main.driver'));
        $this->assertSame('lms-videos', config('filesystems.disks.wasabi-main.bucket'));
        $this->assertSame('AKIAEXAMPLE', config('filesystems.disks.wasabi-main.key'));
    }

    public function test_a_destination_cannot_shadow_a_built_in_disk(): void
    {
        $localRoot = config('filesystems.disks.local.root');

        // A row calling itself "local" would replace the disk the whole
        // application falls back to, and every file recorded as living there
        // would start resolving to someone's bucket.
        $this->destination(['disk_key' => 'local', 'is_default' => true]);

        $this->service()->registerDisks();

        $this->assertSame('local', config('filesystems.disks.local.driver'));
        $this->assertSame($localRoot, config('filesystems.disks.local.root'));
    }

    public function test_credentials_are_encrypted_at_rest(): void
    {
        $destination = $this->destination();

        $raw = \DB::table('storage_destinations')->where('id', $destination->id)->first();

        // A database dump must not be a bucket handover.
        $this->assertNotSame('secret-value', $raw->secret_key);
        $this->assertNotSame('AKIAEXAMPLE', $raw->access_key);

        // ...and it still reads back correctly through the model.
        $this->assertSame('secret-value', $destination->fresh()->secret_key);
    }

    public function test_credentials_are_not_serialised(): void
    {
        $destination = $this->destination();

        $json = $destination->toJson();

        $this->assertStringNotContainsString('secret-value', $json);
        $this->assertStringNotContainsString('AKIAEXAMPLE', $json);
    }

    public function test_making_one_destination_default_clears_the_others(): void
    {
        $first = $this->destination(['is_default' => true]);
        $second = $this->destination(['disk_key' => 'r2-backup', 'name' => 'R2 Backup']);

        $this->service()->makeDefault($second);

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
        $this->assertSame('r2-backup', $this->service()->uploadDisk());
    }

    public function test_options_always_offer_local_alongside_the_managed_rows(): void
    {
        $this->destination(['is_default' => true]);

        $keys = array_column($this->service()->options(), 'disk_key');

        $this->assertSame(['local', 'wasabi-main'], $keys);
    }

    public function test_options_report_live_configuration_state(): void
    {
        $this->destination(['disk_key' => 'half-done', 'bucket' => null]);

        $row = collect($this->service()->options())->firstWhere('disk_key', 'half-done');

        $this->assertFalse($row['is_configured']);
    }
}
