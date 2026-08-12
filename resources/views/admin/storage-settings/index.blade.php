@extends('admin.spa-inner')

@section('title', __('admin.storage_settings.title'))
@section('heading', __('admin.storage_settings.heading'))
@section('subheading', __('admin.storage_settings.subheading'))

@section('content')
    @php
        use App\Models\StorageTransfer;
        use App\Services\StorageUsageService;

        $uploadTarget = collect($options)->firstWhere('is_default', true);
        $totalStored = collect($options)->sum(fn ($o) => $o['usage']['bytes']);
    @endphp

    {{-- Flash messages and validation errors are rendered by the Inertia shell
         (Pages/Admin/Frame.vue) above this content, so repeating them here
         would show every message twice. --}}
    <div class="space-y-6">
        {{-- The two numbers worth seeing before anything else: where uploads are
             going right now, and how much the platform is holding in total. --}}
        <section class="admin-card p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="grid gap-4 sm:grid-cols-2 lg:flex lg:items-center lg:gap-8">
                    <div>
                        <div class="text-[11px] uppercase tracking-wide text-white/40">{{ __('admin.storage_settings.upload_target') }}</div>
                        <div class="mt-1 text-sm font-semibold text-emerald-200" dir="ltr">{{ $uploadTarget['name'] ?? 'local' }}</div>
                    </div>

                    <div>
                        <div class="text-[11px] uppercase tracking-wide text-white/40">{{ __('admin.storage_settings.usage_total_stored') }}</div>
                        <div class="mt-1 text-sm font-semibold text-white" dir="ltr">{{ StorageUsageService::humanBytes($totalStored) }}</div>
                    </div>

                    {{-- Local is the one destination with a limit the platform can
                         read for itself, and filling it takes the database down
                         with the uploads. --}}
                    @if ($localSpace)
                        <div>
                            <div class="text-[11px] uppercase tracking-wide text-white/40">{{ __('admin.storage_settings.local_free_space_hint') }}</div>
                            <div class="mt-1 text-sm font-semibold {{ $localSpace['percent'] >= 90 ? 'text-red-200' : 'text-white' }}" dir="ltr">
                                {{ __('admin.storage_settings.local_free_space', [
                                    'free' => StorageUsageService::humanBytes($localSpace['free']),
                                    'total' => StorageUsageService::humanBytes($localSpace['total']),
                                ]) }}
                            </div>
                        </div>
                    @endif
                </div>

                <form method="post" action="{{ route('admin.storage-settings.check-all') }}">
                    @csrf
                    <button class="admin-btn rounded-xl border border-white/15 px-4 py-2 text-xs font-semibold text-white/80 hover:bg-white/5">
                        {{ __('admin.storage_settings.check_all') }}
                    </button>
                </form>
            </div>
        </section>

        <section class="admin-card p-5">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-white">{{ __('admin.storage_settings.destinations_title') }}</h2>
                    <p class="mt-1 text-xs text-white/50">{{ __('admin.storage_settings.destinations_hint') }}</p>
                </div>
            </div>

            <div class="mt-5 space-y-3">
                @foreach ($options as $option)
                    <div class="rounded-2xl border border-white/10 bg-[#0a0f0d] p-4">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <strong class="text-sm text-white">{{ $option['name'] }}</strong>
                                    <code class="rounded bg-white/5 px-2 py-0.5 text-[11px] text-white/60" dir="ltr">{{ $option['disk_key'] }}</code>

                                    @if ($option['is_default'])
                                        <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[11px] font-semibold text-emerald-200">
                                            {{ __('admin.storage_settings.status_default') }}
                                        </span>
                                    @endif

                                    @if (! $option['is_configured'])
                                        <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[11px] font-semibold text-amber-200">
                                            {{ __('admin.storage_settings.status_incomplete') }}
                                        </span>
                                    @elseif (! $option['is_active'])
                                        <span class="rounded-full bg-white/10 px-2 py-0.5 text-[11px] font-semibold text-white/60">
                                            {{ __('admin.storage_settings.status_inactive') }}
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-1 truncate text-[11px] text-white/40" dir="ltr">
                                    @if ($option['is_builtin'])
                                        {{ __('admin.storage_settings.builtin_local') }}
                                    @else
                                        {{ $option['bucket'] }} · {{ $option['endpoint'] }}
                                    @endif
                                </p>

                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    @include('admin.storage-settings.partials.health-badge', ['option' => $option])
                                </div>

                                @include('admin.storage-settings.partials.usage-bar', ['usage' => $option['usage']])
                            </div>

                            <div class="flex flex-wrap items-center gap-2 md:justify-end">
                                {{-- Available for the built-in local destination too:
                                     it is the one most likely to accumulate parts of
                                     uploads nobody finished. --}}
                                <a href="{{ route('admin.storage-settings.browse', ['disk' => $option['disk_key']]) }}"
                                   class="admin-btn rounded-lg border border-white/15 px-3 py-1.5 text-xs text-white/70">
                                    {{ __('admin.storage_settings.browse') }}
                                </a>

                                @unless ($option['is_builtin'])
                                    @unless ($option['is_default'])
                                        <form method="post" action="{{ route('admin.storage-settings.default', $option['id']) }}">
                                            @csrf
                                            <button class="admin-btn rounded-lg border border-emerald-400/30 px-3 py-1.5 text-xs text-emerald-200">
                                                {{ __('admin.storage_settings.make_default') }}
                                            </button>
                                        </form>
                                    @endunless

                                    <form method="post" action="{{ route('admin.storage-settings.test', $option['id']) }}">
                                        @csrf
                                        <button class="admin-btn rounded-lg border border-white/15 px-3 py-1.5 text-xs text-white/70">
                                            {{ __('admin.storage_settings.test_connection') }}
                                        </button>
                                    </form>

                                    <form method="post" action="{{ route('admin.storage-settings.destroy', $option['id']) }}"
                                          onsubmit="return confirm('{{ __('admin.storage_settings.delete_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="admin-btn rounded-lg border border-red-400/30 px-3 py-1.5 text-xs text-red-200">
                                            {{ __('admin.storage_settings.delete') }}
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        </div>

                        {{-- Editing lives inline: a destination has a handful of
                             fields and a separate page for them would be a page
                             that exists only to hold a handful of fields. --}}
                        @unless ($option['is_builtin'])
                            @php $row = $rows->firstWhere('id', $option['id']); @endphp
                            <details class="mt-3">
                                <summary class="cursor-pointer text-xs text-white/50">{{ __('admin.storage_settings.edit_destination') }}</summary>
                                <form method="post" action="{{ route('admin.storage-settings.update', $option['id']) }}" class="mt-3 grid gap-3 lg:grid-cols-2">
                                    @csrf
                                    @method('PUT')
                                    @include('admin.storage-settings.partials.fields', ['row' => $row, 'isNew' => false])
                                    <div class="lg:col-span-2">
                                        <button class="admin-btn rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white">
                                            {{ __('admin.storage_settings.save') }}
                                        </button>
                                    </div>
                                </form>
                            </details>
                        @endunless
                    </div>
                @endforeach

                @if ($rows->isEmpty())
                    <p class="text-xs text-white/40">{{ __('admin.storage_settings.no_destinations') }}</p>
                @endif
            </div>
        </section>

        {{-- ── Retiring a destination ─────────────────────────────────────
             A destination cannot be deleted while anything references it, and
             nothing else rewrites those references. This is the way off. --}}
        <section class="admin-card p-5">
            <h2 class="text-sm font-semibold text-white">{{ __('admin.storage_settings.transfer_title') }}</h2>
            <p class="mt-1 text-xs text-white/50">{{ __('admin.storage_settings.transfer_hint') }}</p>

            @if ($activeTransfer)
                @php $percent = $activeTransfer->percent(); @endphp
                {{-- The marker the shell watches to know it should re-fetch this
                     page while a worker is moving objects. --}}
                <div data-storage-transfer-active
                     class="mt-4 rounded-2xl border border-emerald-400/20 bg-emerald-500/[0.06] p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="text-sm font-semibold text-emerald-100">
                            {{ __('admin.storage_settings.transfer_running') }}
                            <span class="font-normal text-white/60" dir="ltr">
                                {{ $activeTransfer->from_disk }} → {{ $activeTransfer->to_disk }}
                            </span>
                        </div>

                        <form method="post" action="{{ route('admin.storage-settings.transfers.cancel', $activeTransfer) }}"
                              onsubmit="return confirm('{{ __('admin.storage_settings.transfer_cancel_confirm') }}')">
                            @csrf
                            <button class="admin-btn rounded-lg border border-white/20 px-3 py-1.5 text-xs text-white/70"
                                    @disabled($activeTransfer->cancel_requested)>
                                {{ __('admin.storage_settings.transfer_cancel') }}
                            </button>
                        </form>
                    </div>

                    <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-white/10">
                        <div class="h-full rounded-full bg-emerald-500 transition-all duration-300" style="width: {{ max(1.5, $percent) }}%"></div>
                    </div>

                    <div class="mt-1.5 flex flex-wrap justify-between gap-2 text-[11px] text-white/50">
                        <span>
                            {{ __('admin.storage_settings.transfer_progress', [
                                'moved' => $activeTransfer->moved_items,
                                'total' => $activeTransfer->total_items,
                                'size' => StorageUsageService::humanBytes($activeTransfer->moved_bytes),
                            ]) }}
                        </span>
                        <span dir="ltr">{{ $percent }}%</span>
                    </div>

                    @if ($activeTransfer->failed_items > 0)
                        <p class="mt-2 text-[11px] text-amber-200">
                            {{ __('admin.storage_settings.transfer_failed_items', ['count' => $activeTransfer->failed_items]) }}
                        </p>
                    @endif

                    @if ($activeTransfer->status === StorageTransfer::STATUS_PENDING)
                        <p class="mt-2 text-[11px] text-white/40">{{ __('admin.storage_settings.transfer_needs_worker') }}</p>
                    @endif
                </div>
            @else
                <form method="post" action="{{ route('admin.storage-settings.transfers.start') }}"
                      class="mt-4 grid gap-3 lg:grid-cols-2"
                      onsubmit="return confirm('{{ __('admin.storage_settings.transfer_confirm') }}')">
                    @csrf

                    @php
                        $selectClass = 'mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white';
                    @endphp

                    <div>
                        <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.transfer_from') }}</label>
                        <select name="from_disk" class="{{ $selectClass }}" dir="ltr" required>
                            @foreach ($options as $option)
                                <option value="{{ $option['disk_key'] }}">
                                    {{ $option['name'] }} ({{ $option['disk_key'] }}) — {{ StorageUsageService::humanBytes($option['usage']['bytes']) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.transfer_to') }}</label>
                        <select name="to_disk" class="{{ $selectClass }}" dir="ltr" required>
                            @foreach ($options as $option)
                                {{-- An incomplete destination is offered but marked:
                                     the server refuses it, and hiding it entirely
                                     leaves an admin wondering where it went. --}}
                                <option value="{{ $option['disk_key'] }}" @disabled(! $option['is_configured'])>
                                    {{ $option['name'] }} ({{ $option['disk_key'] }})
                                    @unless ($option['is_configured']) — {{ __('admin.storage_settings.status_incomplete') }} @endunless
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <label class="lg:col-span-2 flex items-start gap-2 text-sm text-white/80">
                        <input type="checkbox" name="delete_source" value="1" class="mt-1 rounded border-white/20 bg-[#0a0f0d] text-emerald-500" />
                        <span>
                            {{ __('admin.storage_settings.transfer_delete_source') }}
                            <span class="mt-0.5 block text-[11px] text-white/40">{{ __('admin.storage_settings.transfer_delete_source_hint') }}</span>
                        </span>
                    </label>

                    <div class="lg:col-span-2">
                        <button class="admin-btn rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                            {{ __('admin.storage_settings.transfer_start') }}
                        </button>
                    </div>
                </form>
            @endif

            <div class="mt-5 border-t border-white/10 pt-4">
                <h3 class="text-xs font-semibold text-white/70">{{ __('admin.storage_settings.transfer_recent') }}</h3>

                @if ($recentTransfers->isEmpty())
                    <p class="mt-2 text-[11px] text-white/35">{{ __('admin.storage_settings.transfer_none') }}</p>
                @else
                    <ul class="mt-2 space-y-1.5">
                        @foreach ($recentTransfers as $transfer)
                            @php
                                $statusClass = match ($transfer->status) {
                                    StorageTransfer::STATUS_COMPLETED => 'text-emerald-200',
                                    StorageTransfer::STATUS_FAILED => 'text-red-200',
                                    default => 'text-white/50',
                                };
                            @endphp
                            <li class="flex flex-wrap items-center gap-2 text-[11px]">
                                <span class="{{ $statusClass }} font-semibold">
                                    {{ __('admin.storage_settings.transfer_status_'.$transfer->status) }}
                                </span>
                                <span class="text-white/50" dir="ltr">{{ $transfer->from_disk }} → {{ $transfer->to_disk }}</span>
                                <span class="text-white/35" dir="ltr">
                                    {{ $transfer->moved_items }}/{{ $transfer->total_items }} ·
                                    {{ StorageUsageService::humanBytes($transfer->moved_bytes) }}
                                </span>
                                <span class="text-white/25">{{ $transfer->finished_at?->diffForHumans() }}</span>
                                @if ($transfer->message)
                                    <span class="text-amber-200/70" title="{{ $transfer->message }}">{{ Str::limit($transfer->message, 80) }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <section class="admin-card p-5">
            <h2 class="text-sm font-semibold text-white">{{ __('admin.storage_settings.add_destination') }}</h2>
            <p class="mt-1 text-xs text-white/50">{{ __('admin.storage_settings.provider_guidance') }}</p>

            <form method="post" action="{{ route('admin.storage-settings.store') }}" class="mt-5 grid gap-3 lg:grid-cols-2">
                @csrf
                @include('admin.storage-settings.partials.fields', ['row' => null, 'isNew' => true])

                <div class="lg:col-span-2">
                    <button class="admin-btn rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500">
                        {{ __('admin.storage_settings.add_destination') }}
                    </button>
                </div>
            </form>
        </section>
    </div>

    {{-- The type-dependent field toggling and the progress refresh used to be
         inline <script> tags here. This page is injected into the Inertia shell
         with v-html, which does not execute scripts, so both now live in
         `resources/js/admin/admin-dom.js` and run from the shell's boot hook —
         the same place the other admin DOM behaviours are wired up. The markup
         only carries the data attributes they look for. --}}
@endsection
