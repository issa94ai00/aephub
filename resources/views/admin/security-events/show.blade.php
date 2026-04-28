@extends('admin.spa-inner')

@section('title', __('admin.security.show_title', ['id' => $securityEvent->id]))
@section('heading', __('admin.security.show_heading', ['id' => $securityEvent->id]))
@section('subheading', $securityEvent->displayTypeLabel())

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.security-events.index') }}" class="text-xs text-emerald-200 hover:underline">← {{ __('admin.security.back_list') }}</a>
    </div>

    <div class="admin-card space-y-4 p-5 text-sm text-white/85">
        <dl class="grid gap-3 sm:grid-cols-2">
            <div><dt class="text-xs text-white/45">{{ __('admin.security.col_type') }}</dt><dd class="mt-0.5">{{ $securityEvent->displayTypeLabel() }}</dd></div>
            <div><dt class="text-xs text-white/45">{{ __('admin.security.col_time') }}</dt><dd class="mt-0.5">{{ optional($securityEvent->created_at)->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-xs text-white/45">{{ __('admin.security.col_device') }}</dt><dd class="mt-0.5 font-mono text-xs" dir="ltr">{{ $securityEvent->device_id ?: '—' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-xs text-white/45">IP</dt><dd class="mt-0.5 font-mono text-xs" dir="ltr">{{ $securityEvent->ip ?: '—' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-xs text-white/45">User-Agent</dt><dd class="mt-0.5 break-all text-xs text-white/70" dir="ltr">{{ $securityEvent->user_agent ?: '—' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-xs text-white/45">{{ __('admin.security.col_user') }}</dt>
                <dd class="mt-0.5">
                    @if ($securityEvent->user)
                        {{ $securityEvent->user->name }} ({{ $securityEvent->user->email }})
                    @else
                        —
                    @endif
                </dd>
            </div>
        </dl>
        <div>
            <h3 class="text-xs font-medium text-white/50">{{ __('admin.security.payload') }}</h3>
            <pre class="mt-2 max-h-[420px] overflow-auto rounded-xl border border-white/10 bg-[#050807] p-4 text-xs text-emerald-100/90" dir="ltr">{{ json_encode($securityEvent->payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>
@endsection

