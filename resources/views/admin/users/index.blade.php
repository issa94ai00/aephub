@extends('admin.spa-inner')

@section('title', __('admin.users.title'))
@section('heading', __('admin.users.heading'))
@section('subheading', __('admin.users.subheading'))

@section('content')
    <div class="mb-4 flex flex-wrap items-center gap-2 text-xs">
        <span class="text-white/60">{{ __('admin.users.filter_role') }}</span>
        <a href="{{ route('admin.users.index') }}" class="rounded-full px-3 py-1 {{ $role === null || $role === '' ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-white/70 hover:bg-white/10' }}">{{ __('admin.courses.all') }}</a>
        <a href="{{ route('admin.users.index', ['role' => 'student']) }}" class="rounded-full px-3 py-1 {{ $role === 'student' ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-white/70 hover:bg-white/10' }}">{{ __('admin.users.role_student') }}</a>
        <a href="{{ route('admin.users.index', ['role' => 'teacher']) }}" class="rounded-full px-3 py-1 {{ $role === 'teacher' ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-white/70 hover:bg-white/10' }}">{{ __('admin.users.role_teacher') }}</a>
        <a href="{{ route('admin.users.index', ['role' => 'teacher_pending']) }}" class="rounded-full px-3 py-1 {{ $role === 'teacher_pending' ? 'bg-amber-500/20 text-amber-100' : 'bg-white/5 text-white/70 hover:bg-white/10' }}">{{ __('admin.users.teachers_pending') }}</a>
        <a href="{{ route('admin.users.index', ['role' => 'admin']) }}" class="rounded-full px-3 py-1 {{ $role === 'admin' ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-white/70 hover:bg-white/10' }}">{{ __('admin.users.role_admin') }}</a>
        <a href="{{ route('admin.users.index', ['frozen' => 1]) }}" class="rounded-full px-3 py-1 {{ ($frozen ?? '') === '1' ? 'bg-rose-500/20 text-rose-100' : 'bg-white/5 text-white/70 hover:bg-white/10' }}">{{ __('admin.users.filter_frozen') }}</a>
    </div>

    <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-4 py-3 text-start">{{ __('admin.users.col_id') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.users.col_name') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.users.col_email') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.users.col_role') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.users.col_teacher_status') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.users.col_device_lock') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.users.col_account') }}</th>
                        <th class="px-4 py-3 text-end">{{ __('admin.users.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse ($users as $u)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-white/60">{{ $u->id }}</td>
                            <td class="px-4 py-3 font-medium text-white">{{ $u->name }}</td>
                            <td class="px-4 py-3 text-white/70">{{ $u->email }}</td>
                            <td class="px-4 py-3">
                                <form method="post" action="{{ route('admin.users.role', $u) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" class="rounded-lg border border-white/10 bg-[#0a0f0d] px-2 py-1 text-xs text-white" onchange="this.form.submit()">
                                        @foreach (['student' => __('admin.users.role_student'), 'teacher' => __('admin.users.role_teacher'), 'admin' => __('admin.users.role_admin')] as $val => $label)
                                            <option value="{{ $val }}" @selected($u->role === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-xs text-white/70">
                                @if ($u->role === 'teacher')
                                    @if (($u->teacher_approval_status ?? 'approved') === 'approved')
                                        <span class="rounded-full bg-emerald-500/15 px-2 py-1 text-emerald-100">{{ __('admin.users.teacher_approved') }}</span>
                                    @elseif (($u->teacher_approval_status ?? '') === 'pending')
                                        <span class="rounded-full bg-amber-500/15 px-2 py-1 text-amber-100">{{ __('admin.users.teacher_pending') }}</span>
                                    @else
                                        <span class="rounded-full bg-rose-500/15 px-2 py-1 text-rose-100">{{ __('admin.users.teacher_rejected') }}</span>
                                    @endif
                                @else
                                    <span class="text-white/45">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-white/60">
                                @if ($u->device_lock_enabled)
                                    <span class="text-amber-200/90">{{ __('admin.users.device_on') }}</span>
                                    @if ($u->locked_device_id)
                                        <span class="block max-w-[140px] truncate text-white/45" title="{{ $u->locked_device_id }}">{{ $u->locked_device_id }}</span>
                                    @endif
                                @else
                                    <span class="text-white/45">{{ __('admin.users.device_off') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if (($u->status ?? 'active') === 'frozen')
                                    <span class="rounded-full bg-rose-500/15 px-2 py-0.5 text-rose-100">{{ __('admin.users.account_frozen') }}</span>
                                @else
                                    <span class="text-white/50">{{ __('admin.users.account_active') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-end">
                                <div class="flex flex-col items-end gap-1">
                                    @if ($u->id !== auth()->id())
                                        @if (($u->status ?? 'active') === 'frozen')
                                            <form method="post" action="{{ route('admin.users.unfreeze', $u) }}" onsubmit="return confirm(@json(__('admin.users.confirm_unfreeze')));">
                                                @csrf
                                                <button type="submit" class="text-xs text-emerald-200 hover:underline">{{ __('admin.users.unfreeze') }}</button>
                                            </form>
                                        @else
                                            <form method="post" action="{{ route('admin.users.freeze', $u) }}" onsubmit="return confirm(@json(__('admin.users.confirm_freeze')));">
                                                @csrf
                                                <button type="submit" class="text-xs text-rose-200 hover:underline">{{ __('admin.users.freeze') }}</button>
                                            </form>
                                        @endif
                                    @endif
                                    @if ($u->locked_device_id || $u->device_lock_enabled)
                                        <form method="post" action="{{ route('admin.users.reset-device', $u) }}" onsubmit="return confirm(@json(__('admin.users.confirm_reset_lock')));">
                                            @csrf
                                            <button type="submit" class="text-xs text-emerald-200 hover:underline">{{ __('admin.users.reset_lock') }}</button>
                                        </form>
                                    @elseif ($u->id === auth()->id())
                                        <span class="text-white/35">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-white/55">{{ __('admin.users.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="border-t border-white/10 px-4 py-3 text-xs text-white/50">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection

