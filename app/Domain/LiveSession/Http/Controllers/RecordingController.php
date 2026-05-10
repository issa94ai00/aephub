<?php

namespace App\Domain\LiveSession\Http\Controllers;

use App\Domain\LiveSession\Http\Resources\RecordingResource;
use App\Domain\LiveSession\Models\LiveSession;
use App\Domain\LiveSession\Models\LiveSessionRecording;
use App\Domain\LiveSession\Services\RecordingService;
use Illuminate\Http\JsonResponse;

class RecordingController extends Controller
{
    public function __construct(
        private readonly RecordingService $service,
    ) {}

    /**
     * Display a listing of recordings for a session.
     */
    public function index(LiveSession $liveSession): JsonResponse
    {
        $recordings = $this->service->getBySession($liveSession->id);

        return response()->json([
            'data' => RecordingResource::collection($recordings),
        ]);
    }

    /**
     * Display the specified recording.
     */
    public function show(LiveSession $liveSession, LiveSessionRecording $recording): JsonResponse
    {
        return response()->json([
            'data' => new RecordingResource($recording),
        ]);
    }

    /**
     * Delete the specified recording.
     */
    public function destroy(LiveSession $liveSession, LiveSessionRecording $recording): JsonResponse
    {
        $this->service->delete($recording);

        return response()->json(null, 204);
    }
}
