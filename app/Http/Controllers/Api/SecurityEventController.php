<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use App\Support\ApiPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecurityEventController extends Controller
{
    private const DISPLAY_TITLES = [
        'screenshot_attempt' => 'محاولة لقطة شاشة',
        'screen_recording' => 'محاولة تسجيل شاشة',
        'screen_recording_apps' => 'برامج تسجيل فيديو',
        'multiple_login_attempts' => 'محاولات تسجيل دخول متعددة',
        'android_emulator' => 'محاكي أندرويد',
        'emulator' => 'محاكي أندرويد',
        'root' => 'وجود Root',
        'root_detected' => 'وجود Root',
        'login_failed' => 'محاولة تسجيل دخول فاشلة',
    ];

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'max:64'],
            'payload' => ['nullable', 'array'],
        ]);

        $event = SecurityEvent::create([
            'user_id' => $request->user()?->id,
            'device_id' => $request->header('X-Device-Id'),
            'type' => $data['type'],
            'payload' => $data['payload'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['event' => $event], 201);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['nullable', 'string', 'max:64'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 50);

        $q = SecurityEvent::query()
            ->with(['user:id,name,email'])
            ->latest('id');

        if (!empty($data['type'])) {
            $q->where('type', $data['type']);
        }
        if (!empty($data['user_id'])) {
            $q->where('user_id', $data['user_id']);
        }
        if (!empty($data['device_id'])) {
            $q->where('device_id', $data['device_id']);
        }
        if (!empty($data['from'])) {
            $q->where('created_at', '>=', $data['from']);
        }
        if (!empty($data['to'])) {
            $q->where('created_at', '<=', $data['to']);
        }

        $p = $q->paginate($perPage);
        $items = $p->getCollection()->map(function (SecurityEvent $e) {
            $title = self::DISPLAY_TITLES[$e->type] ?? $e->type;
            $subtitle = $e->user?->name ?? ($e->payload['email'] ?? null);
            return [
                'id' => $e->id,
                'type' => $e->type,
                'title' => $title,
                'subtitle' => $subtitle,
                'user' => $e->user ? [
                    'id' => $e->user->id,
                    'name' => $e->user->name,
                    'email' => $e->user->email,
                ] : null,
                'device_id' => $e->device_id,
                'payload' => $e->payload,
                'created_at' => optional($e->created_at)->toISOString(),
            ];
        });
        $p->setCollection($items);

        return response()->json(ApiPagination::format($p));
    }

    public function adminShow(SecurityEvent $event): JsonResponse
    {
        $title = self::DISPLAY_TITLES[$event->type] ?? $event->type;
        return response()->json([
            'event' => [
                'id' => $event->id,
                'type' => $event->type,
                'title' => $title,
                'payload' => $event->payload,
                'device_id' => $event->device_id,
                'created_at' => optional($event->created_at)->toISOString(),
            ],
        ]);
    }
}
