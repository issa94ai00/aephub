<?php

namespace App\Domain\LiveSession\Http\Controllers;

use App\Domain\LiveSession\Http\Requests\UploadAssetRequest;
use App\Domain\LiveSession\Http\Resources\AssetResource;
use App\Domain\LiveSession\Models\LiveSession;
use App\Domain\LiveSession\Models\LiveSessionAsset;
use App\Domain\LiveSession\Services\AssetService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct(
        private readonly AssetService $service,
    ) {}

    /**
     * Display a listing of assets for a session.
     */
    public function index(Request $request, LiveSession $liveSession): JsonResponse
    {
        $assets = $liveSession->assets()->with('uploader')->latest()->get();

        return response()->json([
            'data' => AssetResource::collection($assets),
        ]);
    }

    /**
     * Store a newly uploaded asset.
     */
    public function store(UploadAssetRequest $request, LiveSession $liveSession): JsonResponse
    {
        $asset = $this->service->upload(
            sessionId: $liveSession->id,
            file: $request->file('file'),
            uploadedBy: $request->user()->id,
        );

        return response()->json([
            'data' => new AssetResource($asset->load('uploader')),
        ], 201);
    }

    /**
     * Display the specified asset.
     */
    public function show(LiveSession $liveSession, LiveSessionAsset $asset): JsonResponse
    {
        return response()->json([
            'data' => new AssetResource($asset->load('uploader')),
        ]);
    }

    /**
     * Remove the specified asset.
     */
    public function destroy(LiveSession $liveSession, LiveSessionAsset $asset): JsonResponse
    {
        $this->service->delete($asset);

        return response()->json(null, 204);
    }
}
