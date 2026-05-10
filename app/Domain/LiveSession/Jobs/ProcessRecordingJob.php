<?php

namespace App\Domain\LiveSession\Jobs;

use App\Domain\LiveSession\Services\RecordingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessRecordingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = [10, 30, 60];

    public function __construct(
        public readonly int $recordingId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(RecordingService $recordingService): void
    {
        $recordingService->processRecording($this->recordingId);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Recording processing failed', [
            'recording_id' => $this->recordingId,
            'error' => $exception->getMessage(),
        ]);
    }
}
