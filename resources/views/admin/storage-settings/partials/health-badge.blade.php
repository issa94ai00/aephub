{{--
    Whether a destination still answers.

    Four states, and "unknown" is deliberately not green. A destination nobody
    has tested is not a healthy one — it is one the platform has no evidence
    about — and colouring it as passing is the kind of reassurance that gets
    found out during an upload.

    Expects `$option` from the controller's decorated options array.
--}}
@php
    $health = $option['health'] ?? 'unknown';

    [$badgeClass, $label] = match ($health) {
        'ok' => ['border-emerald-400/25 bg-emerald-500/10 text-emerald-200', __('admin.storage_settings.health_ok')],
        'failing' => ['border-red-400/30 bg-red-500/10 text-red-200', __('admin.storage_settings.health_failing')],
        'stale' => ['border-amber-400/25 bg-amber-500/10 text-amber-200', __('admin.storage_settings.health_stale')],
        'builtin' => ['border-white/10 bg-white/5 text-white/50', __('admin.storage_settings.health_builtin')],
        default => ['border-white/15 bg-white/5 text-white/55', __('admin.storage_settings.health_unknown')],
    };

    $checkedAt = $option['last_checked_at'] ?? null;
    $latency = $option['latency_ms'] ?? null;
@endphp

<span class="inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $badgeClass }}"
      @if (! empty($option['last_check_message'])) title="{{ $option['last_check_message'] }}" @endif>
    <span class="h-1.5 w-1.5 rounded-full {{ $health === 'ok' ? 'bg-emerald-400' : ($health === 'failing' ? 'bg-red-400' : ($health === 'stale' ? 'bg-amber-400' : 'bg-white/40')) }}"></span>
    {{ $label }}

    {{-- A destination that answers slowly will make every part of a multipart
         upload crawl, so the round-trip is shown beside the verdict. --}}
    @if ($latency !== null)
        <span class="font-normal opacity-70" dir="ltr">· {{ __('admin.storage_settings.health_latency', ['ms' => $latency]) }}</span>
    @endif
</span>

@if ($checkedAt)
    <span class="text-[11px] text-white/35">
        {{ __('admin.storage_settings.health_checked_at', ['time' => $checkedAt->diffForHumans()]) }}
    </span>
@endif
