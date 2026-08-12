{{--
    How full one destination is.

    Takes `$usage` from StorageUsageService::summaryFor(). A destination with no
    quota gets a figure and no bar — a bar with no ceiling is a bar that is
    always nearly empty, which reads as reassurance the platform cannot give.

    Colour tracks the pressure bands rather than the exact number: what an admin
    needs off a glance is "do I have to act", not the second decimal place.
--}}
@php
    use App\Services\StorageUsageService;

    $barColour = match ($usage['state']) {
        'full' => 'bg-red-500',
        'critical' => 'bg-orange-500',
        'warning' => 'bg-amber-400',
        default => 'bg-emerald-500',
    };

    $stateLabel = match ($usage['state']) {
        'full' => __('admin.storage_settings.usage_state_full'),
        'critical' => __('admin.storage_settings.usage_state_critical'),
        'warning' => __('admin.storage_settings.usage_state_warning'),
        default => null,
    };
@endphp

<div class="mt-3">
    <div class="flex flex-wrap items-baseline justify-between gap-2 text-[11px]">
        <span class="text-white/60">
            {{ __('admin.storage_settings.usage_used') }}
            <span class="font-semibold text-white/85" dir="ltr">{{ StorageUsageService::humanBytes($usage['bytes']) }}</span>

            @if ($usage['quota_bytes'] !== null)
                <span class="text-white/40">
                    {{ __('admin.storage_settings.usage_of') }}
                    <span dir="ltr">{{ StorageUsageService::humanBytes($usage['quota_bytes']) }}</span>
                </span>
            @else
                <span class="text-white/35">· {{ __('admin.storage_settings.quota_unlimited') }}</span>
            @endif
        </span>

        <span class="text-white/40">
            {{ __('admin.storage_settings.usage_breakdown', [
                'files' => $usage['files'],
                'videos' => $usage['videos'],
                'other' => $usage['other'],
            ]) }}
        </span>
    </div>

    @if ($usage['percent'] !== null)
        <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-white/10">
            {{-- Capped at 100 so an over-quota destination does not paint past
                 the track; the label below is what says it went over. --}}
            <div class="h-full rounded-full {{ $barColour }} transition-all duration-300"
                 style="width: {{ min(100, max(1.5, $usage['percent'])) }}%"></div>
        </div>

        <div class="mt-1 flex flex-wrap items-center justify-between gap-2 text-[11px]">
            <span class="{{ $usage['state'] === 'ok' ? 'text-white/40' : 'text-amber-200' }}" dir="ltr">
                {{ $usage['percent'] }}%
            </span>

            @if ($stateLabel)
                <span class="{{ $usage['state'] === 'full' ? 'text-red-200' : 'text-amber-200' }}">{{ $stateLabel }}</span>
            @elseif ($usage['remaining_bytes'] !== null)
                <span class="text-white/40">
                    {{ __('admin.storage_settings.usage_remaining', ['size' => StorageUsageService::humanBytes($usage['remaining_bytes'])]) }}
                </span>
            @endif
        </div>
    @endif
</div>
