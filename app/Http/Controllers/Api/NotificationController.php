<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use App\Support\ApiPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'unread_only' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 50);

        $q = UserNotification::query()
            ->where('user_id', $user->id)
            ->latest('id');

        if (!empty($data['unread_only'])) {
            $q->whereNull('read_at');
        }

        $p = $q->paginate($perPage);
        $items = $p->getCollection()->map(function (UserNotification $n) {
            return [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'data' => $n->data,
                'read_at' => optional($n->read_at)->toISOString(),
                'created_at' => optional($n->created_at)->toISOString(),
            ];
        });
        $p->setCollection($items);

        return response()->json(ApiPagination::format($p));
    }

    public function markRead(Request $request, UserNotification $notification): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ((int) $notification->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return response()->json([
            'notification' => [
                'id' => $notification->id,
                'read_at' => optional($notification->read_at)->toISOString(),
            ],
        ]);
    }
}

