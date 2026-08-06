<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Response;

class UserWebController extends Controller
{
    public function index(Request $request): Response
    {
        $role = $request->query('role');
        $frozen = $request->query('frozen');
        $q = User::query()->where('status', '!=', User::STATUS_DELETED)->latest('id');

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

    public function create(): Response
    {
        return AdminInertia::frame('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:student,teacher,admin'],
        ]);

        $data['teacher_approval_status'] = User::TEACHER_APPROVAL_APPROVED;

        User::query()->create($data);

        return redirect()->route('admin.users.index')->with('status', __('admin.flash.user_created'));
    }

    public function edit(User $user): Response
    {
        return AdminInertia::frame('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:student,teacher,admin'],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => __('admin.users.cannot_demote_self')]);
        }

        if ($data['role'] === 'teacher') {
            $data['teacher_approval_status'] = User::TEACHER_APPROVAL_APPROVED;
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->forceFill($data)->save();

        return back()->with('status', __('admin.flash.user_updated'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['delete' => __('admin.users.cannot_delete_self')]);
        }

        if ($user->role !== 'student') {
            return back()->withErrors(['delete' => __('admin.users.cannot_delete_non_student')]);
        }

        $this->markDeleted($user);

        return back()->with('status', __('admin.flash.user_deleted'));
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer'],
        ]);

        $count = User::query()
            ->whereIn('id', array_map('intval', $data['user_ids']))
            ->where('role', 'student')
            ->where('id', '!=', $request->user()->id)
            ->where('status', '!=', User::STATUS_DELETED)
            ->limit(500)
            ->get()
            ->each(function (User $user): void {
                $this->markDeleted($user);
            })
            ->count();

        if ($count === 0) {
            return back()->withErrors(['bulk' => __('admin.users.no_students_selected')]);
        }

        return back()->with('status', __('admin.flash.users_bulk_deleted', ['count' => $count]));
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

    private function markDeleted(User $user): void
    {
        $user->forceFill([
            'status' => User::STATUS_DELETED,
            'account_lock_id' => null,
            'frozen_at' => null,
        ])->save();
    }
}
