<?php

namespace App\Domain\LiveSession\Jobs;

use App\Domain\LiveSession\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupOldEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds before the job should be available.
     */
    public int $delay = 60 * 60 * 24; // 24 hours

    public function __construct(
        public readonly int $daysThreshold = 90,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(EventRepositoryInterface $eventRepository): void
    {
        $sessions = \App\Domain\LiveSession\Models\LiveSession::where('status', 'ended')
            ->where('ended_at', '<', now()->subDays($this->daysThreshold))
            ->get();

        $deletedCount = 0;
        foreach ($sessions as $session) {
            if ($eventRepository->archiveBySession($session->id)) {
                $deletedCount++;
            }
        }

        Log::info('Old events cleanup completed', [
            'days_threshold' => $this->daysThreshold,
            'sessions_processed' => $sessions->count(),
            'deleted_count' => $deletedCount,
        ]);
    }
}
