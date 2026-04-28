<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UserDeviceAdminController extends Controller
{
    public function index(User $user): JsonResponse
    {
        $devices = UserDevice::query()
            ->where('user_id', $user->id)
            ->latest('last_seen_at')
            ->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'device_lock_enabled' => $user->device_lock_enabled,
                'locked_device_id' => $user->locked_device_id,
                'locked_device_at' => $user->locked_device_at,
            ],
            'devices' => $devices,
        ]);
    }

    public function deactivate(User $user, UserDevice $userDevice): JsonResponse
    {
        abort_unless((int) $userDevice->user_id === (int) $user->id, 404);

        $userDevice->forceFill([
            'is_active' => false,
            'last_seen_at' => $userDevice->last_seen_at ?? Carbon::now(),
        ])->save();

        return response()->json(['device' => $userDevice]);
    }

    public function activate(User $user, UserDevice $userDevice): JsonResponse
    {
        abort_unless((int) $userDevice->user_id === (int) $user->id, 404);

        $userDevice->forceFill([
            'is_active' => true,
            'last_seen_at' => Carbon::now(),
        ])->save();

        return response()->json(['device' => $userDevice]);
    }

    public function lockToDevice(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string', 'max:255'],
            'enable_lock' => ['nullable', 'boolean'],
        ]);

        $exists = UserDevice::where('user_id', $user->id)
            ->where('device_id', $data['device_id'])
            ->exists();

        if (!$exists) {
            return response()->json(['message' => 'Device not found for user'], 404);
        }

        $user->forceFill([
            'device_lock_enabled' => $data['enable_lock'] ?? true,
            'locked_device_id' => $data['device_id'],
            'locked_device_at' => Carbon::now(),
        ])->save();

        return response()->json(['message' => 'User locked to device', 'user' => $user]);
    }
}
