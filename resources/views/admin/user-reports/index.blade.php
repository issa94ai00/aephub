@extends('admin.spa-inner')

@section('title', __('admin.user_reports.title'))
@section('heading', __('admin.user_reports.heading'))
@section('subheading', __('admin.user_reports.subheading'))

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="admin-card p-4">
            <div class="text-xs text-white/55">{{ __('admin.user_reports.total_users') }}</div>
            <div class="mt-2 text-2xl font-bold text-white">{{ $totalUsers }}</div>
        </div>

        <div class="admin-card p-4">
            <div class="text-xs text-white/55">{{ __('admin.user_reports.connected_users') }}</div>
            <div class="mt-2 text-2xl font-bold text-white">{{ $connectedUsersCount }}</div>
            <div class="mt-1 text-[11px] text-emerald-200/80">{{ __('admin.user_reports.active_devices') }}: {{ $activeDeviceCount }}</div>
            <div class="mt-1 text-[10px] text-white/50">{{ __('admin.user_reports.online_within') }}</div>
        </div>

        <div class="admin-card p-4">
            <div class="text-xs text-white/55">{{ __('admin.user_reports.pending_payments') }}</div>
            <div class="mt-2 text-2xl font-bold text-amber-200">{{ $requests['pending_payments'] }}</div>
        </div>

        <div class="admin-card p-4">
            <div class="text-xs text-white/55">{{ __('admin.user_reports.pending_device_changes') }}</div>
            <div class="mt-2 text-2xl font-bold text-amber-200">{{ $requests['pending_device_changes'] }}</div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="admin-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-white">{{ __('admin.user_reports.requests_heading') }}</h2>
            </div>

            <ul class="mt-4 space-y-3 text-sm">
                <li class="flex items-center justify-between rounded-lg bg-white/5 p-3">
                    <span>{{ __('admin.user_reports.pending_payments') }}</span>
                    <span class="font-semibold">{{ $requests['pending_payments'] }}</span>
                </li>
                <li class="flex items-center justify-between rounded-lg bg-white/5 p-3">
                    <span>{{ __('admin.user_reports.pending_device_changes') }}</span>
                    <span class="font-semibold">{{ $requests['pending_device_changes'] }}</span>
                </li>
                <li class="flex items-center justify-between rounded-lg bg-white/5 p-3">
                    <span>{{ __('admin.user_reports.pending_teacher_approvals') }}</span>
                    <span class="font-semibold">{{ $requests['pending_teacher_approvals'] }}</span>
                </li>
                <li class="flex items-center justify-between rounded-lg bg-white/5 p-3">
                    <span>{{ __('admin.user_reports.active_playback_sessions') }}</span>
                    <span class="font-semibold">{{ $requests['active_playback_sessions'] }}</span>
                </li>
            </ul>
        </section>

        <section class="admin-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-white">{{ __('admin.user_reports.connected_user_devices') }}</h2>
                <span class="text-xs text-white/50">{{ __('admin.user_reports.latest_connected_devices') }}</span>
            </div>
            <div class="mt-4 overflow-x-auto text-sm">
                <table class="min-w-full text-left text-white/80">
                    <thead class="border-b border-white/10 text-xs uppercase text-white/50">
                        <tr>
                            <th class="px-3 py-2">{{ __('admin.user_reports.col_user') }}</th>
                            <th class="px-3 py-2">{{ __('admin.user_reports.col_platform') }}</th>
                            <th class="px-3 py-2">{{ __('admin.user_reports.col_device_model') }}</th>
                            <th class="px-3 py-2">{{ __('admin.user_reports.col_last_seen') }}</th>
                            <th class="px-3 py-2">{{ __('admin.user_reports.col_ip') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($connectedDevices as $device)
                            <tr>
                                <td class="px-3 py-3">
                                    <div class="font-medium text-white">{{ $device['user_name'] }}</div>
                                    <div class="text-xs text-white/50">{{ $device['user_email'] }} · {{ $device['user_role'] }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="font-medium text-white">{{ $device['platform'] ?? '—' }}</div>
                                    <div class="text-xs text-white/50">{{ $device['app_version'] ?? '—' }}</div>
                                </td>
                                <td class="px-3 py-3">{{ $device['device_model'] ?? '—' }}</td>
                                <td class="px-3 py-3">{{ $device['last_seen_at'] ?? '—' }}</td>
                                <td class="px-3 py-3">{{ $device['last_ip'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-6 text-center text-white/55" colspan="5">{{ __('admin.user_reports.no_connected_users') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
