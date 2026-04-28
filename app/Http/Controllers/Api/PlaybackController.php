<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseVideo;
use App\Models\PlaybackSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

class PlaybackController extends Controller
{
    /** Max value for MySQL UNSIGNED INT (column `playback_sessions.watermark_seed`). */
    private const WATERMARK_SEED_MAX = 4294967295;

    public function createSession(Request $request, CourseVideo $video): JsonResponse
    {
        $user = $request->user();
        $deviceId = (string) $request->header('X-Device-Id', '');
        if ($deviceId === '') {
            return response()->json(['message' => 'X-Device-Id header is required'], 400);
        }

        $this->authorize('view', $video);

        $watermarkText = $user->name.' • '.$user->id;

        $session = PlaybackSession::create([
            'user_id' => $user->id,
            'course_video_id' => $video->id,
            'device_id' => $deviceId,
            'status' => 'active',
            'issued_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addMinutes(5),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'watermark_text' => $watermarkText,
            'watermark_seed' => random_int(1, self::WATERMARK_SEED_MAX),
        ]);

        return response()->json([
            'session_id' => $session->id,
            'expires_at' => $session->expires_at,
            'watermark' => [
                'text' => $session->watermark_text,
                'seed' => $session->watermark_seed,
            ],
        ], 201);
    }

    public function getKeyForSession(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id' => ['required', 'integer', 'exists:playback_sessions,id'],
        ]);

        $user = $request->user();
        $deviceId = (string) $request->header('X-Device-Id', '');

        /** @var PlaybackSession $session */
        $session = PlaybackSession::query()->with('video')->findOrFail($data['session_id']);

        if ($session->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($deviceId === '' || !hash_equals($session->device_id, $deviceId)) {
            return response()->json(['message' => 'Device mismatch'], 423);
        }

        if ($session->status !== 'active' || $session->expires_at->isPast()) {
            return response()->json(['message' => 'Session expired'], 410);
        }

        $video = $session->video;
        $contentKeyBase64 = Crypt::decryptString($video->encrypted_content_key);
        $contentKeyBytes = base64_decode($contentKeyBase64, true);
        if ($contentKeyBytes === false || strlen($contentKeyBytes) !== 16) {
            return response()->json(['message' => 'Invalid stored content_key format'], 500);
        }

        $contentIvBase64 = (string) $video->content_iv;
        $contentIvBytes = base64_decode($contentIvBase64, true);
        if ($contentIvBytes === false || strlen($contentIvBytes) !== 16) {
            return response()->json(['message' => 'Invalid stored content_iv format'], 500);
        }

        return response()->json([
            'cipher' => $video->encryption_cipher,
            // Stored key/iv are already Base64 for 16-byte AES-128 inputs.
            // Do not base64-encode again, otherwise clients decode to 24 bytes.
            'content_key' => $contentKeyBase64,
            'content_key_base64' => $contentKeyBase64,
            'content_iv' => $contentIvBase64,
            'content_iv_base64' => $contentIvBase64,
            'expires_at' => $session->expires_at,
        ]);
    }
}
