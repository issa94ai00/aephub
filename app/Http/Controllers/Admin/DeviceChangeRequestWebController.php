<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceChangeRequest;
use App\Models\User;
use App\Support\AdminInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Response;

class DeviceChangeRequestWebController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $q = DeviceChangeRequest::query()
            ->with(['user:id,name,email'])
            ->latest('id');

        if (is_string($status) && $status !== '') {
            $q->where('status', $status);
        }

        $requests = $q->paginate(25)->withQueryString();

        return AdminInertia::frame('admin.device-change-requests.index', compact('requests', 'status'));
    }

    public function review(Request $request, DeviceChangeRequest $deviceChangeRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'note' => ['nullable', 'string', 'max:5000'],
            'action' => ['nullable', 'in:reset_lock,set_lock_device,none'],
            'device_id' => ['nullable', 'string', 'max:255'],
        ]);

        if ($deviceChangeRequest->status !== 'pending') {
            return back()->withErrors(['review' => __('admin.flash.device_change_already_reviewed')]);
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
                if (! $deviceId) {
                    return back()->withErrors(['device_id' => __('admin.device_change.device_id_required')]);
                }
                $user->forceFill([
                    'device_lock_enabled' => true,
                    'locked_device_id' => $deviceId,
                    'locked_device_at' => Carbon::now(),
                ])->save();
            }
        }

        return back()->with('status', __('admin.flash.device_change_reviewed'));
    }
}
