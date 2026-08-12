<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Named storage destinations a video can be uploaded to.
 *
 * Storage used to be a single choice: one `storage_provider` in `site_settings`
 * with one set of credentials beside it, applied over the matching disk at boot.
 * That works, but it means the platform can only ever know about one bucket at a
 * time — configuring R2 erases the knowledge of the Wasabi one, and there is
 * nothing to point an upload at.
 *
 * Each row here is a destination the admin has set up and can select. The old
 * single-provider settings are folded into a row below, so an installation that
 * already stores to Wasabi keeps doing so without anyone re-entering keys.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_destinations', function (Blueprint $table) {
            $table->id();

            // Shown to admins. The disk key below is what code and stored rows
            // reference, and it must survive a rename of the label.
            $table->string('name');

            // Becomes the Laravel filesystem disk name, and is written into
            // `course_files.storage_disk` — so it is immutable once used and
            // must not collide with the disks defined in config/filesystems.php.
            $table->string('disk_key')->unique();

            $table->string('driver')->default('s3');

            $table->string('bucket')->nullable();
            $table->string('endpoint', 2048)->nullable();
            $table->string('region')->nullable();

            // Encrypted at rest by the model's casts, so a database dump does
            // not hand over the bucket.
            $table->text('access_key')->nullable();
            $table->text('secret_key')->nullable();

            $table->boolean('use_path_style')->default(false);
            $table->boolean('is_active')->default(true);

            // Exactly one row carries this; the service treats the newest as
            // authoritative if a manual edit ever leaves two.
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->index(['is_active', 'is_default']);
        });

        $this->seedFromLegacySettings();
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_destinations');
    }

    /**
     * Carries the existing single-provider configuration over as a destination.
     *
     * Without this the first boot after migrating would find no destinations and
     * fall back to local, silently sending uploads somewhere other than the
     * bucket the platform has been using.
     */
    private function seedFromLegacySettings(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $settings = DB::table('site_settings')->pluck('value', 'key');
        $provider = trim((string) ($settings['storage_provider'] ?? 'local'));

        // Local needs no row: it is always available and is the implicit
        // fallback when nothing else is configured.
        if (! in_array($provider, ['wasabi', 'r2'], true)) {
            return;
        }

        $bucket = trim((string) ($settings['storage_bucket'] ?? ''));
        $endpoint = trim((string) ($settings['storage_endpoint'] ?? ''));
        $accessKey = trim((string) ($settings['storage_access_key'] ?? ''));
        $secretKey = trim((string) ($settings['storage_secret_key'] ?? ''));

        // A half-filled provider was never actually in use — applyToConfig()
        // rejected it and fell back to local — so there is nothing to carry.
        if ($bucket === '' || $endpoint === '' || $accessKey === '' || $secretKey === '') {
            return;
        }

        DB::table('storage_destinations')->insert([
            'name' => $provider === 'r2' ? 'Cloudflare R2' : 'Wasabi',
            // Reuses the provider name as the disk key so `course_files` rows
            // already carrying "wasabi" keep resolving to this destination.
            'disk_key' => $provider,
            'driver' => 's3',
            'bucket' => $bucket,
            'endpoint' => $endpoint,
            'region' => trim((string) ($settings['storage_region'] ?? '')) ?: null,
            'access_key' => encrypt($accessKey),
            'secret_key' => encrypt($secretKey),
            'use_path_style' => ($settings['storage_use_path_style'] ?? '0') === '1',
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
