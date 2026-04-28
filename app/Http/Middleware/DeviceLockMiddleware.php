<?php

namespace App\Http\Middleware;

use App\Models\UserDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DeviceLockMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }

        $deviceId = (string) $request->header('X-Device-Id', '');
        if ($deviceId === '') {
            return new JsonResponse(['message' => 'X-Device-Id header is required'], 400);
        }

        UserDevice::updateOrCreate(
            ['user_id' => $user->id, 'device_id' => $deviceId],
            [
                'platform' => $request->header('X-Platform'),
                'device_model' => $request->header('X-Device-Model'),
                'os_version' => $request->header('X-OS-Version'),
                'app_version' => $request->header('X-App-Version'),
                'last_ip' => $request->ip(),
                'last_seen_at' => Carbon::now(),
                'is_active' => true,
            ]
        );

        if (!$user->device_lock_enabled) {
            return $next($request);
        }

        if ($user->locked_device_id === null) {
            $user->forceFill([
                'locked_device_id' => $deviceId,
                'locked_device_at' => Carbon::now(),
            ])->save();

            return $next($request);
        }

        if (!hash_equals($user->locked_device_id, $deviceId)) {
            return new JsonResponse(['message' => 'Device locked to another device'], 423);
        }

        return $next($request);
    }
}
