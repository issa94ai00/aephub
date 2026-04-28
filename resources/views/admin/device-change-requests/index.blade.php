@extends('admin.spa-inner')

@section('title', __('admin.device_change.title'))
@section('heading', __('admin.device_change.heading'))
@section('subheading', __('admin.device_change.subheading'))

@section('content')
    <div class="mb-4 flex flex-wrap items-center gap-2 text-xs">
        <span class="text-white/60">{{ __('admin.device_change.filter_status') }}</span>
        <a href="{{ route('admin.device-change-requests.index') }}" class="rounded-full px-3 py-1 {{ ($status === null || $status === '') ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-white/70 hover:bg-white/10' }}">{{ __('admin.courses.all') }}</a>
        <a href="{{ route('admin.device-change-requests.index', ['status' => 'pending']) }}" class="rounded-full px-3 py-1 {{ $status === 'pending' ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-white/70 hover:bg-white/10' }}">{{ __('admin.payment_status.pending') }}</a>
        <a href="{{ route('admin.device-change-requests.index', ['status' => 'approved']) }}" class="rounded-full px-3 py-1 {{ $status === 'approved' ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-white/70 hover:bg-white/10' }}">{{ __('admin.payment_status.approved') }}</a>
        <a href="{{ route('admin.device-change-requests.index', ['status' => 'rejected']) }}" class="rounded-full px-3 py-1 {{ $status === 'rejected' ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-white/70 hover:bg-white/10' }}">{{ __('admin.payment_status.rejected') }}</a>
    </div>

    <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-4 py-3 text-start">{{ __('admin.device_change.col_id') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.device_change.col_student') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.device_change.col_device_requested') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.device_change.col_status') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.device_change.col_reason') }}</th>
                        <th class="px-4 py-3 text-end">{{ __('admin.device_change.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse ($requests as $r)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-white/60">{{ $r->id }}</td>
                            <td class="px-4 py-3 text-white/90">
                                @if ($r->user)
                                    <div class="font-medium text-white">{{ $r->user->name }}</div>
                                    <div class="text-xs text-white/50" dir="ltr">{{ $r->user->email }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-white/60" dir="ltr">
                                {{ $r->requested_device_id ?: '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-white/5 px-2 py-0.5 text-xs">{{ __('admin.payment_status.'.$r->status) }}</span>
                            </td>
                            <td class="max-w-[220px] px-4 py-3 text-xs text-white/55">{{ Str::limit($r->reason ?? '—', 120) }}</td>
                            <td class="px-4 py-3 text-end align-top">
                                @if ($r->status === 'pending')
                                    <div class="flex flex-col items-end gap-2">
                                        <form method="post" action="{{ route('admin.device-change-requests.review', $r) }}" class="inline" onsubmit="return confirm(@json(__('admin.device_change.confirm_approve')));">
                                            @csrf
                                            <input type="hidden" name="status" value="approved" />
                                            <input type="hidden" name="action" value="reset_lock" />
                                            <button type="submit" class="text-xs font-medium text-emerald-200 hover:underline">{{ __('admin.device_change.approve_reset') }}</button>
                                        </form>
                                        <form method="post" action="{{ route('admin.device-change-requests.review', $r) }}" class="inline w-full max-w-[200px] space-y-1 text-start" onsubmit="return confirm(@json(__('admin.device_change.confirm_reject')));">
                                            @csrf
                                            <input type="hidden" name="status" value="rejected" />
                                            <textarea name="note" rows="2" class="w-full rounded-lg border border-white/10 bg-[#0a0f0d] px-2 py-1 text-xs text-white" placeholder="{{ __('admin.device_change.reject_note_placeholder') }}"></textarea>
                                            <button type="submit" class="text-xs text-rose-300 hover:underline">{{ __('admin.device_change.reject') }}</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-white/40">{{ $r->review_note ? Str::limit($r->review_note, 80) : '—' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-white/55">{{ __('admin.device_change.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())
            <div class="border-t border-white/10 px-4 py-3 text-xs text-white/50">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
@endsection

