<?php

namespace App\Domain\LiveSession\Http\Controllers;

use App\Domain\LiveSession\Http\Resources\ParticipantResource;
use App\Domain\LiveSession\Models\LiveSession;
use App\Domain\LiveSession\Services\LiveSessionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ParticipantController extends Controller
{
    public function __construct(
        private readonly LiveSessionService $sessionService,
    ) {}

    /**
     * Display a listing of participants for a session.
     */
    public function index(LiveSession $liveSession): JsonResponse
    {
        $participants = $liveSession->participants()->with('user')->latest()->get();

        return response()->json([
            'data' => ParticipantResource::collection($participants),
        ]);
    }

    /**
     * Get participant statistics for a session.
     */
    public function statistics(LiveSession $liveSession): JsonResponse
    {
        $stats = app(\App\Domain\LiveSession\Repositories\Contracts\ParticipantRepositoryInterface::class)
            ->getStatistics($liveSession->id);

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Remove a participant from a session.
     */
    public function destroy(LiveSession $liveSession, int $participantId): JsonResponse
    {
        $participant = $liveSession->participants()->findOrFail($participantId);
        
        // Remove from LiveKit room
        if ($liveSession->isLive()) {
            $this->sessionService->removeParticipant($liveSession, $participantId);
        }

        $participant->delete();

        return response()->json(null, 204);
    }
}
