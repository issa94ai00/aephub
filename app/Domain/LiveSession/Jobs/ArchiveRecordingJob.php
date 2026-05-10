<?php

namespace App\Domain\LiveSession\Jobs;

use App\Domain\LiveSession\Repositories\Contracts\RecordingRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ArchiveRecordingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds before the job should be available.
     */
    public int $delay = 60 * 60 * 24 * 30; // 30 days

    public function __construct(
        public readonly int $daysThreshold = 180,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(RecordingRepositoryInterface $recordingRepository): void
    {
        $recordings = $recordingRepository->getOlderThan(now()->subDays($this->daysThreshold));

        foreach ($recordings as $recording) {
            $this->archiveRecording($recording);
        }
    }

    /**
     * Archive a recording to cold storage.
     */
    private function archiveRecording($recording): void
    {
        $coldStorageDisk = config('live-session.storage.cold_storage_disk', 's3-archive');
        $coldStoragePath = sprintf('archived/live-sessions/recordings/%d/', $recording->session_id);

        // Copy audio to cold storage
        Storage::disk($coldStorageDisk)->put(
            $coldStoragePath . basename($recording->audio_path),
            Storage::disk($recording->storage_disk)->get($recording->audio_path)
        );

        // Copy events to cold storage
        Storage::disk($coldStorageDisk)->put(
            $coldStoragePath . basename($recording->events_path),
            Storage::disk($recording->storage_disk)->get($recording->events_path)
        );

        // Delete from warm storage
        Storage::disk($recording->storage_disk)->delete($recording->audio_path);
        Storage::disk($recording->storage_disk)->delete($recording->events_path);

        // Update recording with new storage info
        $recording->update([
            'storage_disk' => $coldStorageDisk,
            'audio_path' => $coldStoragePath . basename($recording->audio_path),
            'events_path' => $coldStoragePath . basename($recording->events_path),
        ]);
    }
}
