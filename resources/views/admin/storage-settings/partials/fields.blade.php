{{--
    Shared field set for adding and editing a destination.

    Three things vary:

    - `isNew` — the disk key is set once and then locked (it is written into
      every stored file's row), and credentials are required on creation but
      optional on edit, where blank means "keep what is stored" because the
      form cannot display a secret it has encrypted.
    - the storage **type** — a local destination has no bucket, endpoint or
      keys, and only it has a ceiling on a single request body.
    - the option floors — S3 refuses a multipart part under 5 MiB, so the
      `min` differs by type rather than being one number for both.

    Field groups are shown and hidden by type with a little plain JS below;
    the server validates the same way regardless of what the browser did.
--}}
@php
    $inputClass = 'mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white';
    $type = $row->driver ?? old('driver', \App\Models\StorageDestination::TYPE_S3);
    $options = $row?->effectiveOptions() ?? \App\Models\StorageDestination::defaultOptionsFor($type);
    $formId = 'sd-'.($row->id ?? 'new');
@endphp

<div class="lg:col-span-2">
    <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.type') }}</label>
    @if ($isNew)
        <select name="driver" class="{{ $inputClass }}" data-storage-type="{{ $formId }}">
            <option value="s3" @selected($type === 's3')>{{ __('admin.storage_settings.type_s3') }}</option>
            <option value="r2" @selected($type === 'r2')>{{ __('admin.storage_settings.type_r2') }}</option>
            <option value="local" @selected($type === 'local')>{{ __('admin.storage_settings.type_local') }}</option>
        </select>
        <p class="mt-1 text-[11px] text-white/40">{{ __('admin.storage_settings.type_hint') }}</p>
    @else
        {{-- Changing the type would change which credentials the stored files
             need to be read back with, so it is fixed once created. --}}
        <input value="{{ __('admin.storage_settings.type_'.$type) }}" class="{{ $inputClass }} opacity-60" disabled />
    @endif
</div>

<div>
    <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.name_label') }}</label>
    <input name="name" required value="{{ old('name', $row->name ?? '') }}" class="{{ $inputClass }}" />
</div>

<div>
    <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.disk_key') }}</label>
    @if ($isNew)
        <input name="disk_key" required value="{{ old('disk_key') }}" class="{{ $inputClass }}" dir="ltr" placeholder="wasabi-main" />
        <p class="mt-1 text-[11px] text-white/40">{{ __('admin.storage_settings.disk_key_hint') }}</p>
    @else
        <input value="{{ $row->disk_key ?? '' }}" class="{{ $inputClass }} opacity-60" dir="ltr" disabled />
    @endif
</div>

{{-- ── Object storage only ──────────────────────────────────────────── --}}
<div class="contents" data-storage-group="object" data-form="{{ $formId }}">
    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.bucket') }}</label>
        <input name="bucket" value="{{ old('bucket', $row->bucket ?? '') }}" class="{{ $inputClass }}" dir="ltr" />
    </div>

    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.endpoint') }}</label>
        <input name="endpoint" value="{{ old('endpoint', $row->endpoint ?? '') }}" class="{{ $inputClass }}" dir="ltr" placeholder="https://s3.eu-central-1.wasabisys.com" />
    </div>

    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.region') }}</label>
        <input name="region" value="{{ old('region', $row->region ?? '') }}" class="{{ $inputClass }}" dir="ltr" placeholder="us-east-1" />
        <p class="mt-1 text-[11px] text-white/40">{{ __('admin.storage_settings.region_hint') }}</p>
    </div>

    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.access_key') }}</label>
        <input name="access_key" autocomplete="off" class="{{ $inputClass }}" dir="ltr" />
        @unless ($isNew)
            <p class="mt-1 text-[11px] text-white/40">{{ __('admin.storage_settings.secret_keep_hint') }}</p>
        @endunless
    </div>

    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.secret_key') }}</label>
        <input name="secret_key" type="password" autocomplete="new-password" class="{{ $inputClass }}" dir="ltr" />
        @unless ($isNew)
            <p class="mt-1 text-[11px] text-white/40">{{ __('admin.storage_settings.secret_keep_hint') }}</p>
        @endunless
    </div>

    <label class="flex items-end gap-2 pb-2 text-sm text-white/80">
        <input type="checkbox" name="use_path_style" value="1" class="rounded border-white/20 bg-[#0a0f0d] text-emerald-500"
               @checked(old('use_path_style', $row->use_path_style ?? false)) />
        {{ __('admin.storage_settings.use_path_style') }}
    </label>
</div>

{{-- ── Upload options, tuned per destination ────────────────────────── --}}
<div class="lg:col-span-2 mt-2 rounded-2xl border border-white/10 bg-white/[0.02] p-4">
    <h3 class="text-xs font-semibold text-white/80">{{ __('admin.storage_settings.options_title') }}</h3>
    <p class="mt-1 text-[11px] text-white/40">{{ __('admin.storage_settings.options_hint') }}</p>

    <div class="mt-4 grid gap-3 lg:grid-cols-3">
        <div class="lg:col-span-3">
            <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.opt_path_prefix') }}</label>
            <input name="options[path_prefix]" value="{{ old('options.path_prefix', $options['path_prefix']) }}" class="{{ $inputClass }}" dir="ltr" />
            <p class="mt-1 text-[11px] text-white/40">{{ __('admin.storage_settings.opt_path_prefix_hint') }}</p>
        </div>

        <div>
            <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.opt_part_size') }}</label>
            <input type="number" name="options[part_size_mb]" min="{{ $type === 'local' ? 1 : 5 }}" max="512"
                   value="{{ old('options.part_size_mb', $options['part_size_mb']) }}" class="{{ $inputClass }}" dir="ltr" />
            <p class="mt-1 text-[11px] text-white/40" data-storage-group="object" data-form="{{ $formId }}">
                {{ __('admin.storage_settings.opt_part_size_s3_hint') }}
            </p>
        </div>

        <div>
            <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.opt_recommended_part_size') }}</label>
            <input type="number" name="options[recommended_part_size_mb]" min="{{ $type === 'local' ? 1 : 5 }}" max="512"
                   value="{{ old('options.recommended_part_size_mb', $options['recommended_part_size_mb']) }}" class="{{ $inputClass }}" dir="ltr" />
        </div>

        <div>
            <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.opt_parallel') }}</label>
            <input type="number" name="options[recommended_parallel_parts]" min="1" max="16"
                   value="{{ old('options.recommended_parallel_parts', $options['recommended_parallel_parts']) }}" class="{{ $inputClass }}" dir="ltr" />
        </div>

        {{-- Only the local backend assembles parts in PHP, so only it has a
             ceiling on one request body. --}}
        <div data-storage-group="local" data-form="{{ $formId }}">
            <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.opt_max_part') }}</label>
            <input type="number" name="options[max_part_mb]" min="1" max="2048"
                   value="{{ old('options.max_part_mb', $options['max_part_mb'] ?? 100) }}" class="{{ $inputClass }}" dir="ltr" />
            <p class="mt-1 text-[11px] text-white/40">{{ __('admin.storage_settings.opt_max_part_hint') }}</p>
        </div>

        <div>
            <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.opt_signed_ttl') }}</label>
            <input type="number" name="options[signed_url_ttl_minutes]" min="1" max="1440"
                   value="{{ old('options.signed_url_ttl_minutes', $options['signed_url_ttl_minutes']) }}" class="{{ $inputClass }}" dir="ltr" />
            <p class="mt-1 text-[11px] text-white/40">{{ __('admin.storage_settings.opt_signed_ttl_hint') }}</p>
        </div>

        <div>
            <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.opt_token_ttl') }}</label>
            <input type="number" name="options[multipart_token_ttl_hours]" min="1" max="72"
                   value="{{ old('options.multipart_token_ttl_hours', $options['multipart_token_ttl_hours']) }}" class="{{ $inputClass }}" dir="ltr" />
            <p class="mt-1 text-[11px] text-white/40">{{ __('admin.storage_settings.opt_token_ttl_hint') }}</p>
        </div>
    </div>
</div>

{{-- Capacity. In gigabytes because that is the unit storage is sold in; the
     controller converts once on save. Blank means no ceiling, which is what
     every destination created before quotas existed still has. --}}
<div class="lg:col-span-2">
    <label class="text-xs font-medium text-white/70">{{ __('admin.storage_settings.quota_label') }}</label>
    @php
        $quotaGb = ($row?->quota_bytes ?? null)
            ? round($row->quota_bytes / (1024 ** 3), 2)
            : null;
    @endphp
    <input type="number" name="quota_gb" min="0" step="0.01" value="{{ old('quota_gb', $quotaGb) }}"
           class="{{ $inputClass }}" dir="ltr" placeholder="{{ __('admin.storage_settings.quota_unlimited') }}" />
    <p class="mt-1 text-[11px] text-white/40">{{ __('admin.storage_settings.quota_hint') }}</p>
</div>

<label class="flex items-center gap-2 text-sm text-white/80">
    <input type="checkbox" name="is_active" value="1" class="rounded border-white/20 bg-[#0a0f0d] text-emerald-500"
           @checked(old('is_active', $row->is_active ?? true)) />
    {{ __('admin.storage_settings.is_active') }}
</label>

<label class="flex items-center gap-2 text-sm text-white/80">
    <input type="checkbox" name="make_default" value="1" class="rounded border-white/20 bg-[#0a0f0d] text-emerald-500" />
    {{ __('admin.storage_settings.make_default_now') }}
</label>
