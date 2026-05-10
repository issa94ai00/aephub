<?php

namespace App\Domain\LiveSession\Listeners;

use App\Domain\LiveSession\Events\SessionStarted;
use App\Domain\LiveSession\Services\RecordingService;

class StartLiveKitRecording
{
    public function __construct(
        private readonly RecordingService $recordingService,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(SessionStarted $event): void
    {
        $session = $event->session;

        // Check if recording is enabled
        if ($session->settings['recording_enabled'] ?? false) {
            $outputPath = $this->generateRecordingPath($session->id);
            
            $this->recordingService->startRecording(
                sessionId: $session->id,
                roomName: $session->livekit_room_id,
                outputPath: $outputPath,
            );
        }
    }

    /**
     * Generate recording path for a session.
     */
    private function generateRecordingPath(int $sessionId): string
    {
        $basePath = config('live-session.storage.recordings_path', 'live-sessions/recordings');
        return sprintf('%s/%d/%s', $basePath, $sessionId, now()->format('Y-m-d_His'));
    }
}
