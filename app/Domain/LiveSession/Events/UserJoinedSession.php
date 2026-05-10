<?php

namespace App\Domain\LiveSession\Events;

use App\Domain\LiveSession\Models\LiveSession;
use App\Domain\LiveSession\Models\LiveSessionParticipant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserJoinedSession
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly LiveSessionParticipant $participant,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("live-session.{$this->participant->session_id}"),
        ];
    }
}
