<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseFile;
use App\Models\StorageDestination;
use App\Models\User;
use App\Services\StorageDestinationService;
use App\Services\StorageMaintenanceService;
use App\Services\StorageUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * What the storage screen is allowed to delete.
 *
 * This is the destructive half of storage management, so the tests are about
 * refusals rather than features. The cleanup tool works by asking "does
 * anything reference this object" — and every way that question can be answered
 * wrongly ends with a lecture, a receipt or a course cover being deleted, with
 * a confident label and the correct byte total beside it.
 *
 * Three guards are held here:
 *
 *   - the reference set covers every table that stores a file, not just the
 *     two obvious ones
 *   - the scan never leaves the folder uploads go to, so backups and other
 *     tenants of a shared bucket are out of reach by construction
 *   - an object too young to judge is never removed, because "no row points
 *     here" and "the row has not been written yet" look identical
 */
class StorageMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'course-files/4242';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('scratch');
    }

    private function maintenance(): StorageMaintenanceService
    {
        app(StorageUsageService::class)->flush();

        return app(StorageMaintenanceService::class);
    }

    /** Puts an object on the fake disk, optionally aged past a deletion floor. */
    private function object(string $path, string $body = 'x', ?int $ageHours = null): void
    {
        $disk = Storage::disk('scratch');
        $disk->put($path, $body);

        if ($ageHours !== null) {
            touch($disk->path($path), time() - $ageHours * 3600);
        }
    }

    private function course(): Course
    {
        return Course::create([
            'teacher_id' => User::factory()->create()->id,
            'title' => 'Storage housekeeping',
            'status' => 'published',
        ]);
    }

    /** A course file row pointing at an object on the fake disk. */
    private function courseFile(string $path): CourseFile
    {
        $teacher = User::factory()->create();

        return CourseFile::create([
            'course_id' => $this->course()->id,
            'uploader_id' => $teacher->id,
            'name' => 'Referenced object',
            'original_name' => basename($path),
            'storage_disk' => 'scratch',
            'storage_path' => $path,
            'size_bytes' => 4,
            'mime_type' => 'application/octet-stream',
            'cipher' => 'AES-128-CBC',
            'content_key' => 'encrypted-key',
            'content_iv' => 'iv',
            'key_version' => 'v1',
        ]);
    }

    private function classify(string $path): ?array
    {
        $items = $this->maintenance()->browse('scratch', null)['items'];

        return collect($items)->firstWhere('path', $path);
    }

    public function test_an_object_a_course_file_points_at_is_never_deletable(): void
    {
        $this->object(self::BASE.'/lecture.bin', 'body', ageHours: 72);

        $this->courseFile(self::BASE.'/lecture.bin');

        $item = $this->classify(self::BASE.'/lecture.bin');

        $this->assertSame('tracked', $item['kind']);
        $this->assertFalse($item['deletable']);
    }

    /**
     * The regression this suite exists for.
     *
     * Cover images live on the same disks and are recorded on `courses`, not on
     * `course_files`. A reference set built from course files and videos alone
     * reported every cover on the platform as unreferenced and offered the lot
     * for deletion.
     */
    public function test_a_course_cover_is_recognised_as_referenced(): void
    {
        $this->object(self::BASE.'/cover.jpg', 'image', ageHours: 72);

        $course = $this->course();
        $course->forceFill([
            'cover_image_disk' => 'scratch',
            'cover_image_path' => self::BASE.'/cover.jpg',
        ])->save();

        $item = $this->classify(self::BASE.'/cover.jpg');

        $this->assertSame('tracked', $item['kind'], 'A cover image must not be classified as an orphan.');
        $this->assertFalse($item['deletable']);
    }

    public function test_an_old_unreferenced_object_is_offered_for_deletion(): void
    {
        $this->object(self::BASE.'/stray.bin', 'body', ageHours: 72);

        $item = $this->classify(self::BASE.'/stray.bin');

        $this->assertSame('orphan', $item['kind']);
        $this->assertTrue($item['deletable']);
    }

    /**
     * An upload writes the object first and the row second. In between, a
     * perfectly healthy file is indistinguishable from rubbish.
     */
    public function test_a_recent_unreferenced_object_is_listed_but_not_deletable(): void
    {
        $this->object(self::BASE.'/landing-now.bin', 'body');

        $item = $this->classify(self::BASE.'/landing-now.bin');

        $this->assertSame('orphan', $item['kind']);
        $this->assertFalse($item['deletable']);
    }

    public function test_parts_of_an_abandoned_upload_are_reclaimable_once_the_resume_window_has_passed(): void
    {
        $this->object(self::BASE.'/multipart/_tmp/abc/part-1', 'part', ageHours: 72);
        $this->object(self::BASE.'/multipart/_tmp/def/part-1', 'part');

        $stale = $this->classify(self::BASE.'/multipart/_tmp/abc/part-1');
        $fresh = $this->classify(self::BASE.'/multipart/_tmp/def/part-1');

        $this->assertSame('incomplete', $stale['kind']);
        $this->assertTrue($stale['deletable']);

        $this->assertSame('incomplete', $fresh['kind']);
        $this->assertFalse($fresh['deletable'], 'An upload still inside its resume window is not waste.');
    }

    /**
     * The disk holds more than the platform's uploads — database backups, and
     * on a shared bucket, another tenant's objects entirely. Nothing here
     * references any of it, which is exactly why it must never be scanned.
     */
    public function test_the_scan_never_leaves_the_upload_folder(): void
    {
        $this->object('Laravel/backup-2026-05-04.zip', 'backup', ageHours: 720);
        $this->object('course-covers/1/cover.jpg', 'image', ageHours: 720);
        $this->object(self::BASE.'/inside.bin', 'body', ageHours: 72);

        $listing = $this->maintenance()->browse('scratch', null);
        $paths = array_column($listing['items'], 'path');

        $this->assertContains(self::BASE.'/inside.bin', $paths);
        $this->assertNotContains('Laravel/backup-2026-05-04.zip', $paths);
        $this->assertNotContains('course-covers/1/cover.jpg', $paths);
        $this->assertSame(1, $listing['reclaimable']['count']);
    }

    public function test_deletion_re_checks_every_path_and_ignores_the_rest(): void
    {
        $this->object(self::BASE.'/safe.bin', 'body', ageHours: 72);
        $this->object(self::BASE.'/too-new.bin', 'body');
        $this->object(self::BASE.'/referenced.bin', 'body', ageHours: 72);

        $this->courseFile(self::BASE.'/referenced.bin');

        // Everything is asked for, including the two that must survive.
        $result = $this->maintenance()->deleteReclaimable('scratch', null, [
            self::BASE.'/safe.bin',
            self::BASE.'/too-new.bin',
            self::BASE.'/referenced.bin',
        ]);

        $this->assertSame(1, $result['deleted']);
        $this->assertSame(2, $result['skipped']);

        Storage::disk('scratch')->assertMissing(self::BASE.'/safe.bin');
        Storage::disk('scratch')->assertExists(self::BASE.'/too-new.bin');
        Storage::disk('scratch')->assertExists(self::BASE.'/referenced.bin');
    }

    public function test_a_path_that_was_never_offered_cannot_be_smuggled_in(): void
    {
        $this->object('Laravel/backup.zip', 'backup', ageHours: 720);

        // Outside the scanned folder, so the sweep never sees it — a posted
        // path can only ever narrow what gets removed, never widen it.
        $result = $this->maintenance()->deleteReclaimable('scratch', null, ['Laravel/backup.zip']);

        $this->assertSame(0, $result['deleted']);
        Storage::disk('scratch')->assertExists('Laravel/backup.zip');
    }

    /**
     * Every local destination resolves to one folder, so two disk keys are not
     * two places. Moving between them would copy each object over itself and
     * then delete it.
     */
    public function test_two_destinations_sharing_a_location_are_detected(): void
    {
        $service = app(StorageDestinationService::class);

        config([
            'filesystems.disks.one' => (new StorageDestination(['driver' => 'local']))->toDiskConfig(),
            'filesystems.disks.two' => (new StorageDestination(['driver' => 'local']))->toDiskConfig(),
            'filesystems.disks.elsewhere' => ['driver' => 'local', 'root' => storage_path('app/somewhere-else')],
        ]);

        $this->assertTrue($service->sameUnderlyingLocation('one', 'two'));
        $this->assertFalse($service->sameUnderlyingLocation('one', 'elsewhere'));

        // A disk that cannot be resolved is not proof of difference; refusing
        // the move is the recoverable outcome.
        $this->assertTrue($service->sameUnderlyingLocation('one', 'does-not-exist'));
    }
}
