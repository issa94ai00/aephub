<?php

namespace App\Domain\LiveSession\Http\Controllers;

use App\Domain\LiveSession\DTOs\EventDTO;
use App\Domain\LiveSession\Http\Requests\CreateEventRequest;
use App\Domain\LiveSession\Http\Resources\EventResource;
use App\Domain\LiveSession\Models\LiveSession;
use App\Domain\LiveSession\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(
        private readonly EventService $service,
    ) {}

    /**
     * Display a listing of events for a session.
     */
    public function index(Request $request, LiveSession $liveSession): JsonResponse
    {
        // Use cache for frequently accessed event data
        $cacheService = app(\App\Domain\LiveSession\Services\LiveSessionCacheService::class);
        
        $events = $cacheService->getEventBuffer($liveSession->id);
        
        if (empty($events)) {
            $events = $this->service->getBySession($liveSession->id);

            if ($request->has('from') && $request->has('to')) {
                $events = $this->service->getBySessionAndTimestampRange(
                    $liveSession->id,
                    (int) $request->from,
                    (int) $request->to,
                );
            }

            $limit = min($request->input('limit', 1000), 10000);
            $events = $events->take($limit);
        }

        return response()->json([
            'data' => EventResource::collection($events),
            'meta' => [
                'from' => $request->from,
                'to' => $request->to,
                'total_count' => $this->service->countBySession($liveSession->id),
                'cached' => !empty($events),
            ],
        ]);
    }

    /**
     * Store a newly created event.
     */
    public function store(CreateEventRequest $request, LiveSession $liveSession): JsonResponse
    {
        $dto = EventDTO::fromRequest($request);
        $dto = new EventDTO(
            type: $dto->type,
            data: $dto->data,
            timestampMs: $dto->timestampMs,
            userId: $request->user()->id,
        );
        $event = $this->service->create($dto);

        return response()->json([
            'data' => new EventResource($event),
        ], 201);
    }

    /**
     * Display the specified event.
     */
    public function show(LiveSession $liveSession, int $eventId): JsonResponse
    {
        $event = $liveSession->events()->findOrFail($eventId);

        return response()->json([
            'data' => new EventResource($event),
        ]);
    }
}
