@extends('admin.spa-inner')

@section('title', __('admin.security.title'))
@section('heading', __('admin.security.heading'))
@section('subheading', __('admin.security.subheading'))

@section('content')
    <form method="get" class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4 text-xs">
        <div>
            <label class="block text-white/50">{{ __('admin.security.filter_type') }}</label>
            <input type="text" name="type" value="{{ $filters['type'] ?? '' }}" class="mt-1 rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" placeholder="screenshot_attempt" />
        </div>
        <div>
            <label class="block text-white/50">{{ __('admin.security.filter_user_id') }}</label>
            <input type="number" name="user_id" value="{{ $filters['user_id'] ?? '' }}" class="mt-1 w-28 rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" min="1" />
        </div>
        <div>
            <label class="block text-white/50">{{ __('admin.security.filter_from') }}</label>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
        </div>
        <div>
            <label class="block text-white/50">{{ __('admin.security.filter_to') }}</label>
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
        </div>
        <button type="submit" class="admin-btn rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">{{ __('admin.payments.apply') }}</button>
        <a href="{{ route('admin.security-events.index') }}" class="rounded-xl border border-white/15 px-4 py-2 text-sm text-white/80 hover:bg-white/5">{{ __('admin.courses.all') }}</a>
    </form>

    <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-4 py-3 text-start">{{ __('admin.security.col_id') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.security.col_type') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.security.col_user') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.security.col_device') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.security.col_time') }}</th>
                        <th class="px-4 py-3 text-end">{{ __('admin.security.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse ($events as $e)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-white/60">{{ $e->id }}</td>
                            <td class="px-4 py-3 text-white/90">{{ $e->displayTypeLabel() }}</td>
                            <td class="px-4 py-3 text-xs text-white/70">
                                @if ($e->user)
                                    {{ $e->user->name }}<span class="block text-white/45" dir="ltr">{{ $e->user->email }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="max-w-[140px] truncate px-4 py-3 text-xs text-white/50" dir="ltr" title="{{ $e->device_id }}">{{ $e->device_id ?: '—' }}</td>
                            <td class="px-4 py-3 text-xs text-white/55">{{ optional($e->created_at)->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-end">
                                <a href="{{ route('admin.security-events.show', $e) }}" class="text-emerald-200 hover:underline">{{ __('admin.security.details') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-white/55">{{ __('admin.security.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($events->hasPages())
            <div class="border-t border-white/10 px-4 py-3 text-xs text-white/50">
                {{ $events->links() }}
            </div>
        @endif
    </div>
@endsection

