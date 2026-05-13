<?php

namespace App\Domain\LiveSession\Http\Controllers;

use App\Domain\LiveSession\DTOs\CreateLiveSessionDTO;
use App\Domain\LiveSession\DTOs\EndSessionDTO;
use App\Domain\LiveSession\DTOs\StartSessionDTO;
use App\Domain\LiveSession\DTOs\UpdateLiveSessionDTO;
use App\Domain\LiveSession\Http\Requests\CreateLiveSessionRequest;
use App\Domain\LiveSession\Http\Requests\EndSessionRequest;
use App\Domain\LiveSession\Http\Requests\StartSessionRequest;
use App\Domain\LiveSession\Http\Requests\UpdateLiveSessionRequest;
use App\Domain\LiveSession\Http\Resources\LiveSessionCollection;
use App\Domain\LiveSession\Http\Resources\LiveSessionResource;
use App\Domain\LiveSession\Models\LiveSession;
use App\Domain\LiveSession\Services\LiveSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveSessionController extends Controller
{
    public function __construct(
        private readonly LiveSessionService $service,
    ) {}

    /**
     * Display a listing of live sessions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = LiveSession::query();

        if ($request->has('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Optimize query with eager loading and select only needed columns
        $sessions = $query
            ->with(['teacher:id,name,email'])
            ->select(['id', 'course_id', 'course_session_id', 'teacher_id', 'title', 'scheduled_at', 'status', 'created_at'])
            ->latest('scheduled_at')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => LiveSessionResource::collection($sessions),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
                'last_page' => $sessions->lastPage(),
            ],
            'links' => [
                'first' => $sessions->url(1),
                'last' => $sessions->url($sessions->lastPage()),
                'prev' => $sessions->previousPageUrl(),
                'next' => $sessions->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Store a newly created live session.
     */
    public function store(CreateLiveSessionRequest $request): JsonResponse
    {
        $dto = CreateLiveSessionDTO::fromRequest($request);
        $session = $this->service->create($dto);

        return response()->json([
            'data' => new LiveSessionResource($session->load('teacher')),
        ], 201);
    }

    /**
     * Display the specified live session.
     */
    public function show(LiveSession $liveSession): JsonResponse
    {
        $liveSession->load(['teacher', 'assets', 'recordings']);

        return response()->json([
            'data' => new LiveSessionResource($liveSession),
        ]);
    }

    /**
     * Update the specified live session.
     */
    public function update(UpdateLiveSessionRequest $request, LiveSession $liveSession): JsonResponse
    {
        $dto = UpdateLiveSessionDTO::fromRequest($request);
        $session = $this->service->update($liveSession, $dto);

        return response()->json([
            'data' => new LiveSessionResource($session->load('teacher')),
        ]);
    }

    /**
     * Remove the specified live session.
     */
    public function destroy(LiveSession $liveSession): JsonResponse
    {
        $this->service->delete($liveSession);

        return response()->json(null, 204);
    }

    /**
     * Start a live session.
     */
    public function start(StartSessionRequest $request, LiveSession $liveSession): JsonResponse
    {
        $dto = StartSessionDTO::fromRequest($request);
        $result = $this->service->start($liveSession, $dto);

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * End a live session.
     */
    public function end(EndSessionRequest $request, LiveSession $liveSession): JsonResponse
    {
        $dto = EndSessionDTO::fromRequest($request);
        $result = $this->service->end($liveSession, $dto);

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Cancel a scheduled session.
     */
    public function cancel(LiveSession $liveSession): JsonResponse
    {
        $session = $this->service->cancel($liveSession);

        return response()->json([
            'data' => new LiveSessionResource($session->load('teacher')),
        ]);
    }

    /**
     * Get a participant token for a session.
     */
    public function token(Request $request, LiveSession $liveSession): JsonResponse
    {
        $role = $request->input('role', 'student');
        $result = $this->service->getParticipantToken($liveSession, $request->user()->id, $role);

        return response()->json([
            'data' => $result,
        ]);
    }
}
