<?php

namespace App\Domain\LiveSession\Listeners;

use App\Domain\LiveSession\Events\SessionEnded;
use App\Domain\LiveSession\Services\RecordingService;

class StopLiveKitRecording
{
    public function __construct(
        private readonly RecordingService $recordingService,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(SessionEnded $event): void
    {
        $session = $event->session;

        // Get active recording for the session
        $recording = $this->recordingService->getLatestBySession($session->id);
        
        if ($recording && $recording->isProcessing()) {
            // Stop LiveKit recording
            // This would need the egress ID stored when starting
            // For now, this is a placeholder
        }
    }
}
