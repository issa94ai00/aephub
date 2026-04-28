<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceChangeRequest;
use App\Models\User;
use App\Support\AdminNotifier;
use App\Support\ApiPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DeviceChangeRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:5000'],
            'requested_device_id' => ['nullable', 'string', 'max:255'],
        ]);

        $dcr = DeviceChangeRequest::create([
            'user_id' => $request->user()->id,
            'status' => 'pending',
            'reason' => $data['reason'] ?? null,
            'requested_device_id' => $data['requested_device_id'] ?? null,
        ]);

        AdminNotifier::notify(
            type: 'device_change_request_created',
            title: 'طلب تغيير جهاز جديد',
            body: $request->user()->name.' ('.$request->user()->email.')',
            data: ['device_change_request_id' => $dcr->id, 'user_id' => $dcr->user_id]
        );

        return response()->json(['request' => $dcr], 201);
    }

    /**
     * Create device change request without JWT (email/password only).
     * Used when the app is locked to another device.
     */
    public function storeUnauth(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:500'],
            'reason' => ['nullable', 'string', 'max:5000'],
            'requested_device_id' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], (string) $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!$user->device_lock_enabled || ($user->locked_device_id ?? null) === null) {
            return response()->json([
                'message' => 'Device lock is not enabled for this account',
            ], 409);
        }

        $existing = DeviceChangeRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if ($existing) {
            return response()->json(['request' => $existing], 200);
        }

        $dcr = DeviceChangeRequest::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'reason' => $data['reason'] ?? null,
            'requested_device_id' => $data['requested_device_id'] ?? null,
        ]);

        AdminNotifier::notify(
            type: 'device_change_request_created',
            title: 'طلب تغيير جهاز جديد',
            body: $user->name.' ('.$user->email.')',
            data: ['device_change_request_id' => $dcr->id, 'user_id' => $dcr->user_id]
        );

        return response()->json(['request' => $dcr], 201);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $perPage = (int) ($data['per_page'] ?? 20);

        $q = DeviceChangeRequest::query()
            ->with(['user:id,name,email'])
            ->latest('id');

        if (!empty($data['status'])) {
            $q->where('status', $data['status']);
        }
        if (!empty($data['user_id'])) {
            $q->where('user_id', $data['user_id']);
        }

        $p = $q->paginate($perPage);

        $items = $p->getCollection()->map(function (DeviceChangeRequest $r) {
            return [
                'id' => $r->id,
                'student' => $r->user
                    ? ['id' => $r->user->id, 'name' => $r->user->name, 'email' => $r->user->email]
                    : null,
                'status' => $r->status,
                'reason' => $r->reason,
                'created_at' => optional($r->created_at)->toISOString(),
            ];
        });

        $p->setCollection($items);

        return response()->json(ApiPagination::format($p));
    }

    public function adminReview(Request $request, DeviceChangeRequest $deviceChangeRequest): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'note' => ['nullable', 'string', 'max:5000'],
            'action' => ['nullable', 'in:reset_lock,set_lock_device,none'],
            'device_id' => ['nullable', 'string', 'max:255'],
        ]);

        if ($deviceChangeRequest->status !== 'pending') {
            return response()->json(['message' => 'Request already reviewed'], 409);
        }

        $deviceChangeRequest->forceFill([
            'status' => $data['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => Carbon::now(),
            'review_note' => $data['note'] ?? null,
        ])->save();

        $user = User::find($deviceChangeRequest->user_id);
        if ($user && $data['status'] === 'approved') {
            $action = $data['action'] ?? 'reset_lock';
            if ($action === 'reset_lock') {
                $user->forceFill([
                    'locked_device_id' => null,
                    'locked_device_at' => null,
                ])->save();
            } elseif ($action === 'set_lock_device') {
                $deviceId = $data['device_id'] ?? null;
                if (!$deviceId) {
                    return response()->json(['message' => 'device_id is required when action=set_lock_device'], 422);
                }
                $user->forceFill([
                    'device_lock_enabled' => true,
                    'locked_device_id' => $deviceId,
                    'locked_device_at' => Carbon::now(),
                ])->save();
            }
        }

        return response()->json([
            'request' => [
                'id' => $deviceChangeRequest->id,
                'status' => $deviceChangeRequest->status,
            ],
            'user' => $user ? [
                'id' => $user->id,
                'locked_device_id' => $user->locked_device_id,
            ] : null,
        ]);
    }
}

