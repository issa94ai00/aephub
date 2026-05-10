<?php

namespace App\Domain\LiveSession\Listeners;

use App\Domain\LiveSession\Events\RecordingReady;
use App\Domain\LiveSession\Services\NotificationService;

class NotifyRecordingReady
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(RecordingReady $event): void
    {
        $this->notificationService->notifyRecordingReady($event->recording->session_id);
    }
}
