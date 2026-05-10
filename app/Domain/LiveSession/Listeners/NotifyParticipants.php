<?php

namespace App\Domain\LiveSession\Listeners;

use App\Domain\LiveSession\Events\SessionEnded;
use App\Domain\LiveSession\Events\SessionStarted;
use App\Domain\LiveSession\Services\NotificationService;

class NotifyParticipants
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Handle the session started event.
     */
    public function handleSessionStarted(SessionStarted $event): void
    {
        $this->notificationService->notifySessionStarting($event->session);
    }

    /**
     * Handle the session ended event.
     */
    public function handleSessionEnded(SessionEnded $event): void
    {
        $this->notificationService->notifySessionEnded($event->session);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            SessionStarted::class => 'handleSessionStarted',
            SessionEnded::class => 'handleSessionEnded',
        ];
    }
}
