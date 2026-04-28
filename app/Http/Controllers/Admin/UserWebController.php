<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Response;

class UserWebController extends Controller
{
    public function index(Request $request): Response
    {
        $role = $request->query('role');
        $frozen = $request->query('frozen');
        $q = User::query()->latest('id');

        if ($role === 'teacher_pending') {
            $q->where('role', 'teacher')->where('teacher_approval_status', 'pending');
        } elseif (is_string($role) && $role !== '') {
            $q->where('role', $role);
        }

        if ($frozen === '1' || $frozen === 'true') {
            $q->where('status', 'frozen');
        }

        $users = $q->paginate(30)->withQueryString();

        return AdminInertia::frame('admin.users.index', compact('users', 'role', 'frozen'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:student,teacher,admin'],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'لا يمكنك إزالة صلاحية المدير عن حسابك الحالي.']);
        }

        $update = ['role' => $data['role']];
        if ($data['role'] === 'teacher') {
            $update['teacher_approval_status'] = 'approved';
        }

        $user->forceFill($update)->save();

        return back()->with('status', __('admin.flash.user_role_updated'));
    }

    public function freeze(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['freeze' => __('admin.users.cannot_freeze_self')]);
        }

        if (($user->status ?? 'active') === 'frozen') {
            return back()->withErrors(['freeze' => __('admin.users.already_frozen')]);
        }

        $user->forceFill([
            'status' => 'frozen',
            'account_lock_id' => 'web-'.Str::lower(Str::random(24)),
            'frozen_at' => Carbon::now(),
        ])->save();

        return back()->with('status', __('admin.flash.user_frozen'));
    }

    public function unfreeze(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['freeze' => __('admin.users.cannot_unfreeze_self')]);
        }

        $user->forceFill([
            'status' => 'active',
            'account_lock_id' => null,
            'frozen_at' => null,
        ])->save();

        return back()->with('status', __('admin.flash.user_unfrozen'));
    }
}
