<?php

namespace Tests\Feature;

use App\Models\StorageDestination;
use App\Support\MediaChunkingHints;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Per-destination upload options.
 *
 * The knobs that decide how an upload behaves — part size, parallelism, the
 * ceiling on one request, how long a link or a resumable upload lives — were
 * global `.env` values. Global is the wrong scope: object storage refuses parts
 * under 5 MiB while the local disk chokes on parts that large, so one number
 * cannot serve both, and with two destinations configured the single setting is
 * wrong for one of them.
 */
class StorageDestinationOptionsTest extends TestCase
{
    use RefreshDatabase;

    private function destination(string $type, array $options = [], array $overrides = []): StorageDestination
    {
        return StorageDestination::create(array_merge([
            'name' => ucfirst($type).' destination',
            'disk_key' => $type.'-dest',
            'driver' => $type,
            'bucket' => $type === 'local' ? null : 'videos',
            'endpoint' => $type === 'local' ? null : 'https://s3.example.com',
            'access_key' => $type === 'local' ? null : 'AK',
            'secret_key' => $type === 'local' ? null : 'SK',
            'options' => $options,
            'is_active' => true,
        ], $overrides));
    }

    public function test_each_type_starts_from_its_own_defaults(): void
    {
        $local = StorageDestination::defaultOptionsFor(StorageDestination::TYPE_LOCAL);
        $s3 = StorageDestination::defaultOptionsFor(StorageDestination::TYPE_S3);

        // The local disk assembles parts in PHP, so it wants small ones and is
        // the only type with a ceiling on a single request body.
        $this->assertLessThan($s3['part_size_mb'], $local['part_size_mb']);
        $this->assertArrayHasKey('max_part_mb', $local);
        $this->assertArrayNotHasKey('max_part_mb', $s3);
    }

    public function test_object_storage_cannot_be_configured_below_the_s3_floor(): void
    {
        // S3 rejects any part under 5 MiB except the last, so a destination
        // advertising 2 would fail on its first PUT.
        $destination = $this->destination(StorageDestination::TYPE_S3, ['part_size_mb' => 2]);

        $this->assertSame(
            StorageDestination::S3_MIN_PART_MB,
            $destination->effectiveOptions()['part_size_mb']
        );
    }

    public function test_the_local_disk_may_use_parts_smaller_than_the_s3_floor(): void
    {
        $destination = $this->destination(StorageDestination::TYPE_LOCAL, ['part_size_mb' => 1]);

        $this->assertSame(1, $destination->effectiveOptions()['part_size_mb']);
    }

    public function test_the_recommended_size_never_falls_below_the_advertised_minimum(): void
    {
        // A client following both at once would otherwise have no valid size.
        $destination = $this->destination(StorageDestination::TYPE_S3, [
            'part_size_mb' => 32,
            'recommended_part_size_mb' => 8,
        ]);

        $options = $destination->effectiveOptions();

        $this->assertSame(32, $options['part_size_mb']);
        $this->assertSame(32, $options['recommended_part_size_mb']);
    }

    public function test_parallelism_is_held_to_a_sane_range(): void
    {
        $destination = $this->destination(StorageDestination::TYPE_S3, ['recommended_parallel_parts' => 99]);

        $this->assertSame(16, $destination->effectiveOptions()['recommended_parallel_parts']);
    }

    public function test_the_path_prefix_is_normalised(): void
    {
        $destination = $this->destination(StorageDestination::TYPE_S3, ['path_prefix' => '/videos/lectures/']);

        $this->assertSame('videos/lectures', $destination->effectiveOptions()['path_prefix']);
    }

    public function test_options_reach_the_multipart_init_payload(): void
    {
        $destination = $this->destination(StorageDestination::TYPE_S3, [
            'part_size_mb' => 16,
            'recommended_part_size_mb' => 32,
            'recommended_parallel_parts' => 6,
        ]);

        $fields = MediaChunkingHints::multipartInitFieldsFor($destination, 's3');

        // What the app is actually told when it starts an upload.
        $this->assertSame(16 * 1024 * 1024, $fields['part_size_bytes']);
        $this->assertSame(32 * 1024 * 1024, $fields['recommended_part_size_bytes']);
        $this->assertSame(6, $fields['recommended_parallel_parts']);
    }

    public function test_without_a_destination_the_platform_defaults_still_apply(): void
    {
        // Every caller that has no managed destination — the `.env` disks —
        // must keep behaving exactly as before options existed.
        $this->assertSame(
            MediaChunkingHints::multipartInitFields('s3'),
            MediaChunkingHints::multipartInitFieldsFor(null, 's3')
        );
    }

    public function test_an_option_left_behind_by_another_type_is_ignored(): void
    {
        // `max_part_mb` means nothing to object storage; carrying it would let
        // a stale value apply invisibly after a type change.
        $destination = $this->destination(StorageDestination::TYPE_S3, ['max_part_mb' => 999]);

        $this->assertArrayNotHasKey('max_part_mb', $destination->effectiveOptions());
    }

    public function test_r2_defaults_to_the_addressing_it_requires(): void
    {
        // R2 has no regions and is not virtual-hosted; defaulting it like
        // Wasabi means it cannot sign a request.
        $destination = $this->destination(StorageDestination::TYPE_R2, [], ['region' => null]);

        $config = $destination->toDiskConfig();

        $this->assertSame('auto', $config['region']);
        $this->assertSame('s3', $config['driver']);
    }
}
