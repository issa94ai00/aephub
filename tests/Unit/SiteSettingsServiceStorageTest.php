<?php

namespace Tests\Unit;

use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingsServiceStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_effective_upload_disk_falls_back_to_local_when_provider_is_not_configured(): void
    {
        $service = app(SiteSettingsService::class);

        $service->persist([
            'storage_provider' => 'r2',
            'storage_bucket' => '',
            'storage_endpoint' => '',
            'storage_access_key' => '',
            'storage_secret_key' => '',
        ]);

        $this->assertSame('local', $service->effectiveUploadDisk());
    }

    public function test_effective_upload_disk_returns_selected_provider_when_configured(): void
    {
        $service = app(SiteSettingsService::class);

        $service->persist([
            'storage_provider' => 'wasabi',
            'storage_bucket' => 'demo-bucket',
            'storage_endpoint' => 'https://s3.wasabisys.com',
            'storage_access_key' => 'demo-key',
            'storage_secret_key' => 'demo-secret',
        ]);

        $this->assertSame('wasabi', $service->effectiveUploadDisk());
    }
}
