@php
    $editing = isset($slide) && $slide !== null;
@endphp

<div class="admin-card space-y-4 p-5">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="text-xs font-medium text-white/70">{{ __('admin.carousel.sort_order') }}</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $slide->sort_order ?? '') }}" min="0" max="99999"
                   class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
            <p class="mt-1 text-[11px] text-white/40">{{ __('admin.carousel.sort_hint') }}</p>
        </div>
        <div class="flex items-end pb-1">
            <label class="flex cursor-pointer items-center gap-2 text-sm text-white/80">
                <input type="hidden" name="is_active" value="0" />
                <input type="checkbox" name="is_active" value="1" class="rounded border-white/20 bg-[#0a0f0d] text-emerald-500"
                       @checked(old('is_active', ($editing && $slide->is_active) || ! $editing ? '1' : '0') === '1') />
                {{ __('admin.carousel.active') }}
            </label>
        </div>
    </div>

    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.carousel.slide_title') }}</label>
        <input name="title" value="{{ old('title', $slide->title ?? '') }}" required
               class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
    </div>
    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.carousel.slide_title_en') }}</label>
        <input name="title_en" value="{{ old('title_en', $slide->title_en ?? '') }}" dir="ltr"
               class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
    </div>
    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.carousel.slide_subtitle') }}</label>
        <textarea name="subtitle" rows="2" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white">{{ old('subtitle', $slide->subtitle ?? '') }}</textarea>
    </div>
    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.carousel.slide_subtitle_en') }}</label>
        <textarea name="subtitle_en" rows="2" dir="ltr" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white">{{ old('subtitle_en', $slide->subtitle_en ?? '') }}</textarea>
    </div>

    <div class="space-y-2">
        <label class="text-xs font-medium text-white/70">{{ __('admin.carousel.image_url') }}</label>
        <input name="image" value="{{ old('image', $slide->image ?? '') }}" dir="ltr"
               class="w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white"
               placeholder="https://... أو storage/..." />
        <label class="text-xs font-medium text-white/70">{{ __('admin.carousel.image_file') }}</label>
        <input type="file" name="image_upload" accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
               class="mt-1 block w-full cursor-pointer rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-xs text-white file:me-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-emerald-600 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-white" />
        <p class="text-[11px] text-white/40">{{ __('admin.carousel.image_hint') }}</p>
        @if($editing && trim((string) ($slide->image ?? '')) !== '')
            @php $preview = $slide->resolvedImageUrl(); @endphp
            @if($preview !== '')
                <div class="mt-2">
                    <p class="text-xs text-white/50">{{ __('admin.carousel.current_preview') }}</p>
                    <img src="{{ $preview }}" alt="" class="mt-1 max-h-40 max-w-full rounded-lg border border-white/10 object-contain" loading="lazy" decoding="async" />
                </div>
            @endif
        @endif
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="admin-btn rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500">
        {{ $editing ? __('admin.carousel.save') : __('admin.carousel.create_btn') }}
    </button>
    <a href="{{ route('admin.carousel.index') }}" class="rounded-xl border border-white/15 px-6 py-2.5 text-sm text-white/80 hover:bg-white/5">{{ __('admin.carousel.cancel') }}</a>
</div>
