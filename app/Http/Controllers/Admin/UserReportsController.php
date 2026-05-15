<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceChangeRequest;
use App\Models\PaymentRequest;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\UserDevice;
use App\Support\AdminInertia;
use Illuminate\Support\Carbon;
use Inertia\Response;

class UserReportsController extends Controller
{
    private const ONLINE_WINDOW_MINUTES = 10;

    public function index(): Response
    {
        $totalUsers = User::count();
        $onlineThreshold = Carbon::now()->subMinutes(self::ONLINE_WINDOW_MINUTES);

        $activeDeviceQuery = UserDevice::query()
            ->where('is_active', true)
            ->where('last_seen_at', '>=', $onlineThreshold);

        $activeDeviceCount = $activeDeviceQuery->count();
        $connectedUsersCount = (clone $activeDeviceQuery)
            ->distinct('user_id')
            ->count('user_id');

        $connectedDevices = UserDevice::query()
            ->where('is_active', true)
            ->where('last_seen_at', '>=', $onlineThreshold)
            ->with('user:id,name,email,role')
            ->latest('last_seen_at')
            ->limit(30)
            ->get()
            ->map(function (UserDevice $device) {
                return [
                    'device_id' => $device->device_id,
                    'platform' => $device->platform,
                    'device_model' => $device->device_model,
                    'app_version' => $device->app_version,
                    'last_ip' => $device->last_ip,
                    'last_seen_at' => $device->last_seen_at?->toDateTimeString(),
                    'user_name' => $device->user->name ?? 'N/A',
                    'user_email' => $device->user->email ?? 'N/A',
                    'user_role' => $device->user->role ?? 'N/A',
                ];
            });

        $activePlaybackSessions = PlaybackSession::where('status', 'active')
            ->where('expires_at', '>', now())
            ->count();

        $requests = [
            'pending_payments' => PaymentRequest::where('status', 'pending')->count(),
            'pending_device_changes' => DeviceChangeRequest::where('status', 'pending')->count(),
            'pending_teacher_approvals' => User::where('role', 'teacher')
                ->where('teacher_approval_status', 'pending')
                ->count(),
            'active_playback_sessions' => $activePlaybackSessions,
        ];

        return AdminInertia::frame('admin.user-reports.index', compact(
            'totalUsers',
            'connectedUsersCount',
            'activeDeviceCount',
            'connectedDevices',
            'requests'
        ));
    }
}
