<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserDeviceWebController extends Controller
{
    public function resetDevice(User $user): RedirectResponse
    {
        $user->forceFill([
            'locked_device_id' => null,
            'locked_device_at' => null,
        ])->save();

        return back()->with('status', __('admin.flash.device_lock_reset'));
    }
}
