@extends('admin.spa-inner')

@php
    use App\Services\StorageMaintenanceService;
    use App\Services\StorageUsageService;

    $displayName = $destination?->name ?? __('admin.storage_settings.provider_local');
@endphp

@section('title', __('admin.storage_settings.browse_title'))
@section('heading', __('admin.storage_settings.browse_heading', ['name' => $displayName]))
@section('subheading', __('admin.storage_settings.browse_subheading'))

@section('content')
    @php
        $totals = $listing['totals'];

        // Everything the scan found that is safe to delete — not just what is
        // on the page being shown, so the figure is the whole waste rather than
        // a tenth of it.
        $reclaimable = $listing['reclaimable'];
    @endphp

    {{-- Flash and validation messages come from the Inertia shell
         (Pages/Admin/Frame.vue), so they are not repeated here. --}}
    <div class="space-y-6">
        <div>
            <a href="{{ route('admin.storage-settings.index') }}" class="text-xs text-white/50 hover:text-white/80">
                ← {{ __('admin.storage_settings.browse_back') }}
            </a>
        </div>

        {{-- The destination could not be listed at all: rotated keys, a deleted
             bucket, a provider outage. The provider's own words are more use
             here than anything this screen could invent. --}}
        @if ($listing['error'])
            <section class="admin-card p-5">
                <p class="text-sm text-red-200">
                    {{ __('admin.storage_settings.browse_error', ['message' => $listing['error']]) }}
                </p>
            </section>
        @else
            <section class="admin-card p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2 text-[11px]">
                        <code class="rounded bg-white/5 px-2 py-0.5 text-white/60" dir="ltr">{{ $diskKey }}</code>

                        <span class="rounded-full border border-emerald-400/20 bg-emerald-500/10 px-2 py-0.5 font-semibold text-emerald-200">
                            {{ __('admin.storage_settings.kind_tracked') }} · {{ $totals['tracked'] }}
                        </span>
                        <span class="rounded-full border border-amber-400/20 bg-amber-500/10 px-2 py-0.5 font-semibold text-amber-200">
                            {{ __('admin.storage_settings.kind_orphan') }} · {{ $totals['orphan'] }}
                            <span class="font-normal opacity-70" dir="ltr">({{ StorageUsageService::humanBytes($totals['orphan_bytes']) }})</span>
                        </span>
                        <span class="rounded-full border border-white/15 bg-white/5 px-2 py-0.5 font-semibold text-white/60">
                            {{ __('admin.storage_settings.kind_incomplete') }} · {{ $totals['incomplete'] }}
                            <span class="font-normal opacity-70" dir="ltr">({{ StorageUsageService::humanBytes($totals['incomplete_bytes']) }})</span>
                        </span>
                    </div>
                </div>

                @include('admin.storage-settings.partials.usage-bar', ['usage' => $usage])

                {{-- Says out loud what the scan did and did not look at. An
                     admin reading "0 unreferenced" needs to know that is a
                     statement about one folder, not the whole bucket. --}}
                <p class="mt-3 text-[11px] leading-relaxed text-white/35">
                    {{ __('admin.storage_settings.browse_scope', ['prefix' => $scanPrefix.'/']) }}
                </p>

                <p class="mt-1 text-[11px] leading-relaxed text-white/35">{{ __('admin.storage_settings.kind_legend') }}</p>

                @if ($listing['truncated'])
                    <p class="mt-2 text-[11px] text-amber-200/80">
                        {{ __('admin.storage_settings.browse_truncated', ['count' => StorageMaintenanceService::MAX_SCAN_OBJECTS]) }}
                    </p>
                @endif
            </section>

            {{-- ── Reclaiming ──────────────────────────────────────────────
                 The paths are submitted as hidden fields, but they are only
                 candidates: the service re-derives what is safe at deletion
                 time, so this form can narrow the set and never widen it. --}}
            <section class="admin-card p-5">
                <h2 class="text-sm font-semibold text-white">{{ __('admin.storage_settings.reclaim_title') }}</h2>
                <p class="mt-1 text-xs text-white/50">
                    {{ __('admin.storage_settings.reclaim_hint', ['hours' => StorageMaintenanceService::ORPHAN_MIN_AGE_HOURS]) }}
                </p>

                @if ($reclaimable['count'] === 0)
                    <p class="mt-3 text-xs text-white/35">{{ __('admin.storage_settings.reclaim_none') }}</p>
                @else
                    <p class="mt-3 text-xs text-amber-200">
                        {{ __('admin.storage_settings.reclaim_summary', [
                            'count' => $reclaimable['count'],
                            'size' => StorageUsageService::humanBytes($reclaimable['bytes']),
                        ]) }}
                    </p>

                    <form method="post" action="{{ route('admin.storage-settings.cleanup', ['disk' => $diskKey]) }}" class="mt-3"
                          onsubmit="return confirm('{{ __('admin.storage_settings.reclaim_confirm') }}')">
                        @csrf
                        @method('DELETE')

                        @foreach ($reclaimable['paths'] as $path)
                            <input type="hidden" name="paths[]" value="{{ $path }}" />
                        @endforeach

                        <button class="admin-btn rounded-xl border border-red-400/30 bg-red-500/10 px-5 py-2 text-xs font-semibold text-red-100 hover:bg-red-500/15">
                            {{ __('admin.storage_settings.reclaim_button', [
                                'count' => $reclaimable['count'],
                                'size' => StorageUsageService::humanBytes($reclaimable['bytes']),
                            ]) }}
                        </button>
                    </form>
                @endif
            </section>

            <section class="admin-card p-5">
                @if ($listing['items'] === [])
                    <p class="text-xs text-white/35">{{ __('admin.storage_settings.browse_empty') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] text-start text-xs">
                            <thead>
                                <tr class="border-b border-white/10 text-[11px] uppercase tracking-wide text-white/40">
                                    <th class="px-2 py-2 text-start font-medium">{{ __('admin.storage_settings.col_path') }}</th>
                                    <th class="px-2 py-2 text-start font-medium">{{ __('admin.storage_settings.col_size') }}</th>
                                    <th class="px-2 py-2 text-start font-medium">{{ __('admin.storage_settings.col_modified') }}</th>
                                    <th class="px-2 py-2 text-start font-medium">{{ __('admin.storage_settings.col_kind') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($listing['items'] as $item)
                                    @php
                                        [$kindClass, $kindLabel] = match ($item['kind']) {
                                            'tracked' => ['text-emerald-200', __('admin.storage_settings.kind_tracked')],
                                            'orphan' => ['text-amber-200', __('admin.storage_settings.kind_orphan')],
                                            default => ['text-white/50', __('admin.storage_settings.kind_incomplete')],
                                        };
                                    @endphp
                                    <tr class="border-b border-white/5">
                                        <td class="max-w-[420px] truncate px-2 py-2 text-white/75" dir="ltr" title="{{ $item['path'] }}">
                                            {{ $item['path'] }}
                                        </td>
                                        <td class="whitespace-nowrap px-2 py-2 text-white/55" dir="ltr">
                                            {{ StorageUsageService::humanBytes($item['size']) }}
                                        </td>
                                        <td class="whitespace-nowrap px-2 py-2 text-white/40">
                                            {{ $item['last_modified'] ? \Illuminate\Support\Carbon::createFromTimestamp($item['last_modified'])->diffForHumans() : '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-2 py-2 {{ $kindClass }}">
                                            {{ $kindLabel }}
                                            {{-- Listed but not offered for deletion: too
                                                 recent to tell apart from an upload that
                                                 is still landing. --}}
                                            @if ($item['kind'] !== 'tracked' && ! $item['deletable'])
                                                <span class="text-white/25">·</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @php
                        $lastPage = (int) ceil($listing['total'] / $listing['per_page']);
                    @endphp

                    @if ($lastPage > 1)
                        <div class="mt-4 flex items-center justify-between text-xs text-white/50">
                            @if ($listing['page'] > 1)
                                <a href="{{ route('admin.storage-settings.browse', ['disk' => $diskKey, 'page' => $listing['page'] - 1]) }}"
                                   class="admin-btn rounded-lg border border-white/15 px-3 py-1.5">←</a>
                            @else
                                <span></span>
                            @endif

                            <span dir="ltr">{{ $listing['page'] }} / {{ $lastPage }}</span>

                            @if ($listing['page'] < $lastPage)
                                <a href="{{ route('admin.storage-settings.browse', ['disk' => $diskKey, 'page' => $listing['page'] + 1]) }}"
                                   class="admin-btn rounded-lg border border-white/15 px-3 py-1.5">→</a>
                            @else
                                <span></span>
                            @endif
                        </div>
                    @endif
                @endif
            </section>
        @endif
    </div>
@endsection
