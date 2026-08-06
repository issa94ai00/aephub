@php
    $editing = isset($user) && $user !== null;
    $action = $editing ? route('admin.users.update', $user) : route('admin.users.store');
@endphp

<div class="admin-card space-y-4 p-5">
    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.users.form_name') }}</label>
        <input name="name" value="{{ old('name', $user->name ?? '') }}" required
               class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
    </div>

    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.users.form_email') }}</label>
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required dir="ltr"
               class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
    </div>

    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.users.form_phone') }}</label>
        <input name="phone" value="{{ old('phone', $user->phone ?? '') }}" dir="ltr"
               class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
    </div>

    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.users.form_password') }}</label>
        <input type="password" name="password" autocomplete="new-password" @if (!$editing) required @endif dir="ltr"
               class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
        <p class="mt-1 text-[11px] text-white/40">
            {{ $editing ? __('admin.users.form_password_keep') : __('admin.users.form_password_required') }}
        </p>
    </div>

    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.users.form_role') }}</label>
        <select name="role" @disabled($editing && auth()->id() === (int) $user->id)
                class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white disabled:opacity-50">
            @foreach (['student' => __('admin.users.role_student'), 'teacher' => __('admin.users.role_teacher'), 'admin' => __('admin.users.role_admin')] as $val => $label)
                <option value="{{ $val }}" @selected(old('role', $user->role ?? 'student') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        @if ($editing && auth()->id() === (int) $user->id)
            <p class="mt-1 text-[11px] text-white/40">{{ __('admin.users.form_role_self') }}</p>
        @endif
    </div>

    <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="admin-btn rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500">
            {{ $editing ? __('admin.users.save') : __('admin.users.create_btn') }}
        </button>
        <a href="{{ route('admin.users.index') }}" class="admin-btn inline-flex items-center rounded-xl border border-white/10 bg-white/5 px-5 py-2.5 text-sm font-medium text-white/90 hover:bg-white/10">
            {{ __('admin.users.cancel') }}
        </a>
    </div>
</div>
