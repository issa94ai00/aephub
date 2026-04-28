@extends('admin.spa-inner')

@section('title', __('admin.settings.title'))
@section('heading', __('admin.settings.heading'))
@section('subheading', __('admin.settings.subheading'))

@php
    $logoFieldValue = old('site_logo', $settings['site_logo'] ?? '');
@endphp

@section('content')
    <form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="admin-card p-5">
                <h2 class="text-sm font-semibold text-white">{{ __('admin.settings.general') }}</h2>
                <p class="mt-1 text-xs text-white/50">{{ __('admin.settings.general_hint') }}</p>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="text-xs font-medium text-white/70">{{ __('admin.settings.site_name_ar') }}</label>
                        <input name="site_name" value="{{ old('site_name', $settings['site_name'] ?? '') }}" required
                               class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-white/70">{{ __('admin.settings.site_name_en') }}</label>
                        <input name="site_name_en" value="{{ old('site_name_en', $settings['site_name_en'] ?? '') }}"
                               class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr"
                               placeholder="English site name" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-white/70">{{ __('admin.settings.timezone') }}</label>
                        <select name="timezone" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white">
                            @foreach ($timezones as $tz)
                                <option value="{{ $tz }}" @selected(old('timezone', $settings['timezone'] ?? 'UTC') === $tz)>{{ $tz }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-white/70">{{ __('admin.settings.default_locale') }}</label>
                        <select name="locale" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white">
                            <option value="ar" @selected(old('locale', $settings['locale'] ?? 'ar') === 'ar')>{{ __('admin.settings.locale_ar') }}</option>
                            <option value="en" @selected(old('locale', $settings['locale'] ?? 'ar') === 'en')>{{ __('admin.settings.locale_en') }}</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="admin-card p-5">
                <h2 class="text-sm font-semibold text-white">{{ __('admin.settings.env_readonly') }}</h2>
                <dl class="mt-4 space-y-2 text-sm text-white/80">
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50">{{ __('admin.settings.env_label') }}</dt><dd>{{ $app['env'] }}</dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50">{{ __('admin.settings.debug_label') }}</dt><dd>{{ $app['debug'] ? __('admin.settings.debug_on') : __('admin.settings.debug_off') }}</dd></div>
                    <div class="flex justify-between gap-3 pb-2"><dt class="text-white/50">{{ __('admin.settings.app_url') }}</dt><dd class="truncate max-w-[55%] text-end" dir="ltr">{{ $app['url'] }}</dd></div>
                </dl>
            </section>
        </div>

        <section class="admin-card p-5">
            <h2 class="text-sm font-semibold text-white">{{ __('admin.settings.appearance') }}</h2>
            <p class="mt-1 text-xs text-white/50">{{ __('admin.settings.appearance_hint') }}</p>
            <div class="mt-4 space-y-3">
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-white/70">{{ __('admin.settings.logo_file') }}</label>
                        <input id="admin-site-logo-file" type="file" name="site_logo_upload" accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
                               class="mt-1 block w-full cursor-pointer rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-xs text-white file:me-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-emerald-600 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-white hover:file:bg-emerald-500" />
                        <p class="mt-1 text-[11px] text-white/40">{{ __('admin.settings.logo_file_hint') }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-white/70">{{ __('admin.settings.logo_url') }}</label>
                        <input id="admin-site-logo-input" name="site_logo" value="{{ $logoFieldValue }}"
                               class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" autocomplete="off"
                               placeholder="{{ __('admin.settings.placeholder_site_logo') }}" />
                    </div>
                    <label class="flex cursor-pointer items-center gap-2 text-xs text-white/75">
                        <input type="hidden" name="remove_site_logo" value="0" />
                        <input type="checkbox" name="remove_site_logo" value="1" id="admin-site-logo-remove"
                               class="rounded border-white/20 bg-[#0a0f0d] text-emerald-500" />
                        {{ __('admin.settings.remove_logo') }}
                    </label>
                    <p class="text-xs text-white/45">{{ __('admin.settings.logo_preview') }} <span class="text-white/35">{{ __('admin.settings.logo_preview_live_hint') }}</span></p>
                    <img id="admin-site-logo-preview" src="" alt="" decoding="async"
                         class="mt-1 hidden h-14 w-auto max-w-[220px] rounded-lg border border-white/10 bg-white/[0.04] object-contain object-start p-1" />
                    <p id="admin-site-logo-preview-error" class="mt-1 hidden text-xs text-amber-200/90">{{ __('admin.settings.logo_preview_error') }}</p>
                </div>
                <div>
                    <label class="text-xs font-medium text-white/70">{{ __('admin.settings.bg_url') }}</label>
                    <input name="site_background_fixed" value="{{ old('site_background_fixed', $settings['site_background_fixed'] ?? '') }}"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr"
                           placeholder="{{ __('admin.settings.placeholder_site_bg') }}" />
                    <p class="mt-1 text-[11px] text-white/40">{{ __('admin.settings.bg_hint') }}</p>
                </div>
                <div class="flex items-end pb-0.5">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-white/80">
                        <input type="checkbox" name="site_fixed_bg_enabled" value="1"
                               class="rounded border-white/20 bg-[#0a0f0d] text-emerald-500"
                               @checked(old('site_fixed_bg_enabled', ($settings['site_fixed_bg_enabled'] ?? '0') === '1')) />
                        {{ __('admin.settings.bg_enable') }}
                    </label>
                </div>
            </div>
        </section>

        <section class="admin-card p-5">
            <h2 class="text-sm font-semibold text-white">{{ __('admin.settings.contact') }}</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-medium text-white/70">{{ __('admin.settings.phone') }}</label>
                    <input name="contact_phone" value="{{ old('contact_phone', $settings['contact_phone'] ?? '') }}"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" placeholder="+963..." />
                </div>
                <div>
                    <label class="text-xs font-medium text-white/70">{{ __('admin.settings.contact_email') }}</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email'] ?? '') }}"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" />
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-medium text-white/70">{{ __('admin.settings.facebook') }}</label>
                    <input name="facebook_url" value="{{ old('facebook_url', $settings['facebook_url'] ?? '') }}"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" placeholder="https://facebook.com/..." />
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-medium text-white/70">{{ __('admin.settings.telegram') }}</label>
                    <input name="telegram_url" value="{{ old('telegram_url', $settings['telegram_url'] ?? '') }}"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" placeholder="https://t.me/..." />
                </div>
            </div>
        </section>

        <section class="admin-card p-5">
            <h2 class="text-sm font-semibold text-white">{{ __('admin.settings.whatsapp_section') }}</h2>
            <p class="mt-1 text-xs text-white/50">{{ __('admin.settings.whatsapp_hint') }}</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-medium text-white/70">{{ __('admin.settings.whatsapp_number') }}</label>
                    <input name="whatsapp_number" value="{{ old('whatsapp_number', $settings['whatsapp_number'] ?? '') }}"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" placeholder="9639xxxxxxxx" />
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-white/80">
                        <input type="checkbox" name="whatsapp_float_enabled" value="1"
                               class="rounded border-white/20 bg-[#0a0f0d] text-emerald-500"
                               @checked(old('whatsapp_float_enabled', ($settings['whatsapp_float_enabled'] ?? '0') === '1')) />
                        {{ __('admin.settings.whatsapp_float') }}
                    </label>
                </div>
            </div>
        </section>

        <section class="admin-card p-5">
            <h2 class="text-sm font-semibold text-white">{{ __('admin.settings.seo_section') }}</h2>
            <p class="mt-1 text-xs text-white/50">{{ __('admin.settings.seo_hint') }}</p>
            <div class="mt-4 space-y-3">
                <div>
                    <label class="text-xs font-medium text-white/70">{{ __('admin.settings.meta_title_ar') }}</label>
                    <input name="seo_meta_title" value="{{ old('seo_meta_title', $settings['seo_meta_title'] ?? '') }}"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" placeholder="{{ __('admin.settings.meta_title_placeholder') }}" />
                </div>
                <div>
                    <label class="text-xs font-medium text-white/70">{{ __('admin.settings.meta_title_en') }}</label>
                    <input name="seo_meta_title_en" value="{{ old('seo_meta_title_en', $settings['seo_meta_title_en'] ?? '') }}"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" />
                </div>
                <div>
                    <label class="text-xs font-medium text-white/70">{{ __('admin.settings.meta_desc_ar') }}</label>
                    <textarea name="seo_meta_description" rows="3"
                              class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white">{{ old('seo_meta_description', $settings['seo_meta_description'] ?? '') }}</textarea>
                </div>
                <div>
                    <label class="text-xs font-medium text-white/70">{{ __('admin.settings.meta_desc_en') }}</label>
                    <textarea name="seo_meta_description_en" rows="3" dir="ltr"
                              class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white">{{ old('seo_meta_description_en', $settings['seo_meta_description_en'] ?? '') }}</textarea>
                </div>
                <div>
                    <label class="text-xs font-medium text-white/70">{{ __('admin.settings.keywords_ar') }}</label>
                    <input name="seo_keywords" value="{{ old('seo_keywords', $settings['seo_keywords'] ?? '') }}"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
                </div>
                <div>
                    <label class="text-xs font-medium text-white/70">{{ __('admin.settings.keywords_en') }}</label>
                    <input name="seo_keywords_en" value="{{ old('seo_keywords_en', $settings['seo_keywords_en'] ?? '') }}"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" />
                </div>
                <div>
                    <label class="text-xs font-medium text-white/70">{{ __('admin.settings.og_image') }}</label>
                    <input name="seo_og_image" value="{{ old('seo_og_image', $settings['seo_og_image'] ?? '') }}"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" placeholder="https://..." />
                </div>
            </div>
        </section>

        <section class="admin-card p-5">
            <h2 class="text-sm font-semibold text-white">{{ __('admin.settings.api_section') }}</h2>
            <p class="mt-1 text-xs text-white/50">{{ __('admin.settings.api_hint') }}</p>
            <div class="mt-4 space-y-3">
                <div>
                    <label class="text-xs font-medium text-white/70">{{ __('admin.settings.score_degree') }}</label>
                    <input name="score_degree" value="{{ old('score_degree', $settings['score_degree'] ?? '0') }}" required
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" maxlength="64" />
                </div>
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="admin-btn rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500">
                {{ __('admin.settings.save') }}
            </button>
        </div>
    </form>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="admin-card p-5">
            <h2 class="text-sm font-semibold text-white">{{ __('admin.settings.maintenance') }}</h2>
            <p class="mt-2 text-xs text-white/55">{{ __('admin.settings.maintenance_hint') }}</p>
            <form method="post" action="{{ route('admin.settings.clear-cache') }}" class="mt-4 space-y-3">
                @csrf
                <label class="flex items-start gap-2 text-xs text-white/70">
                    <input type="checkbox" name="clear_cache" value="1" required class="mt-0.5 rounded border-white/20 bg-[#0a0f0d] text-emerald-500" />
                    {{ __('admin.settings.confirm_optimize') }} <span dir="ltr" class="text-white/90">optimize:clear</span>.
                </label>
                <button type="submit" class="admin-btn rounded-xl border border-amber-400/30 bg-amber-500/10 px-4 py-2.5 text-sm font-semibold text-amber-100 hover:bg-amber-500/15">
                    {{ __('admin.settings.clear_cache') }}
                </button>
            </form>
        </section>
    </div>

    <script>
        (function () {
            var input = document.getElementById('admin-site-logo-input');
            var fileInput = document.getElementById('admin-site-logo-file');
            var removeCb = document.getElementById('admin-site-logo-remove');
            var img = document.getElementById('admin-site-logo-preview');
            var err = document.getElementById('admin-site-logo-preview-error');
            if (!input || !img) return;
            var objectUrl = null;
            var resolvedInitial = @json(!empty($settings['site_logo_url'] ?? '') ? $settings['site_logo_url'] : '');
            function revokeObject() {
                if (objectUrl) {
                    URL.revokeObjectURL(objectUrl);
                    objectUrl = null;
                }
            }
            function resolvePreviewSrc(v) {
                if (!v) return '';
                if (/^https?:\/\//i.test(v) || v.indexOf('//') === 0) return v;
                if (v.indexOf('/') === 0) return v;
                return '/' + v.replace(/^\//, '');
            }
            function apply() {
                err.classList.add('hidden');
                if (removeCb && removeCb.checked) {
                    revokeObject();
                    img.removeAttribute('src');
                    img.classList.add('hidden');
                    return;
                }
                if (fileInput && fileInput.files && fileInput.files[0]) {
                    revokeObject();
                    objectUrl = URL.createObjectURL(fileInput.files[0]);
                    img.onerror = function () {
                        img.classList.add('hidden');
                        err.classList.remove('hidden');
                    };
                    img.onload = function () {
                        img.classList.remove('hidden');
                        err.classList.add('hidden');
                    };
                    img.src = objectUrl;
                    return;
                }
                var v = (input.value || '').trim();
                img.onerror = function () {
                    img.classList.add('hidden');
                    if (v !== '') err.classList.remove('hidden');
                };
                img.onload = function () {
                    img.classList.remove('hidden');
                    err.classList.add('hidden');
                };
                if (!v) {
                    if (resolvedInitial) {
                        img.src = resolvedInitial;
                    } else {
                        img.removeAttribute('src');
                        img.classList.add('hidden');
                    }
                    return;
                }
                img.src = resolvePreviewSrc(v);
            }
            apply();
            input.addEventListener('input', apply);
            input.addEventListener('change', apply);
            if (fileInput) fileInput.addEventListener('change', apply);
            if (removeCb) removeCb.addEventListener('change', apply);
        })();
    </script>
@endsection

