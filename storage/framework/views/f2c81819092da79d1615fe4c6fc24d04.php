

<?php $__env->startSection('title', __('admin.settings.title')); ?>
<?php $__env->startSection('heading', __('admin.settings.heading')); ?>
<?php $__env->startSection('subheading', __('admin.settings.subheading')); ?>

<?php
    $logoFieldValue = old('site_logo', $settings['site_logo'] ?? '');
?>

<?php $__env->startSection('content'); ?>
    <form method="post" action="<?php echo e(route('admin.settings.update')); ?>" enctype="multipart/form-data" class="space-y-6">
        <?php echo csrf_field(); ?>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="admin-card p-5">
                <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.settings.general')); ?></h2>
                <p class="mt-1 text-xs text-white/50"><?php echo e(__('admin.settings.general_hint')); ?></p>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.site_name_ar')); ?></label>
                        <input name="site_name" value="<?php echo e(old('site_name', $settings['site_name'] ?? '')); ?>" required
                               class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.site_name_en')); ?></label>
                        <input name="site_name_en" value="<?php echo e(old('site_name_en', $settings['site_name_en'] ?? '')); ?>"
                               class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr"
                               placeholder="English site name" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.timezone')); ?></label>
                        <select name="timezone" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white">
                            <?php $__currentLoopData = $timezones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($tz); ?>" <?php if(old('timezone', $settings['timezone'] ?? 'UTC') === $tz): echo 'selected'; endif; ?>><?php echo e($tz); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.default_locale')); ?></label>
                        <select name="locale" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white">
                            <option value="ar" <?php if(old('locale', $settings['locale'] ?? 'ar') === 'ar'): echo 'selected'; endif; ?>><?php echo e(__('admin.settings.locale_ar')); ?></option>
                            <option value="en" <?php if(old('locale', $settings['locale'] ?? 'ar') === 'en'): echo 'selected'; endif; ?>><?php echo e(__('admin.settings.locale_en')); ?></option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="admin-card p-5">
                <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.settings.env_readonly')); ?></h2>
                <dl class="mt-4 space-y-2 text-sm text-white/80">
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50"><?php echo e(__('admin.settings.env_label')); ?></dt><dd><?php echo e($app['env']); ?></dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50"><?php echo e(__('admin.settings.debug_label')); ?></dt><dd><?php echo e($app['debug'] ? __('admin.settings.debug_on') : __('admin.settings.debug_off')); ?></dd></div>
                    <div class="flex justify-between gap-3 pb-2"><dt class="text-white/50"><?php echo e(__('admin.settings.app_url')); ?></dt><dd class="truncate max-w-[55%] text-end" dir="ltr"><?php echo e($app['url']); ?></dd></div>
                </dl>
            </section>
        </div>

        <section class="admin-card p-5">
            <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.settings.appearance')); ?></h2>
            <p class="mt-1 text-xs text-white/50"><?php echo e(__('admin.settings.appearance_hint')); ?></p>
            <div class="mt-4 space-y-3">
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.logo_file')); ?></label>
                        <input id="admin-site-logo-file" type="file" name="site_logo_upload" accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
                               class="mt-1 block w-full cursor-pointer rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-xs text-white file:me-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-emerald-600 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-white hover:file:bg-emerald-500" />
                        <p class="mt-1 text-[11px] text-white/40"><?php echo e(__('admin.settings.logo_file_hint')); ?></p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.logo_url')); ?></label>
                        <input id="admin-site-logo-input" name="site_logo" value="<?php echo e($logoFieldValue); ?>"
                               class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" autocomplete="off"
                               placeholder="<?php echo e(__('admin.settings.placeholder_site_logo')); ?>" />
                    </div>
                    <label class="flex cursor-pointer items-center gap-2 text-xs text-white/75">
                        <input type="hidden" name="remove_site_logo" value="0" />
                        <input type="checkbox" name="remove_site_logo" value="1" id="admin-site-logo-remove"
                               class="rounded border-white/20 bg-[#0a0f0d] text-emerald-500" />
                        <?php echo e(__('admin.settings.remove_logo')); ?>

                    </label>
                    <p class="text-xs text-white/45"><?php echo e(__('admin.settings.logo_preview')); ?> <span class="text-white/35"><?php echo e(__('admin.settings.logo_preview_live_hint')); ?></span></p>
                    <img id="admin-site-logo-preview" src="" alt="" decoding="async"
                         class="mt-1 hidden h-14 w-auto max-w-[220px] rounded-lg border border-white/10 bg-white/[0.04] object-contain object-start p-1" />
                    <p id="admin-site-logo-preview-error" class="mt-1 hidden text-xs text-amber-200/90"><?php echo e(__('admin.settings.logo_preview_error')); ?></p>
                </div>
                <div>
                    <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.bg_url')); ?></label>
                    <input name="site_background_fixed" value="<?php echo e(old('site_background_fixed', $settings['site_background_fixed'] ?? '')); ?>"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr"
                           placeholder="<?php echo e(__('admin.settings.placeholder_site_bg')); ?>" />
                    <p class="mt-1 text-[11px] text-white/40"><?php echo e(__('admin.settings.bg_hint')); ?></p>
                </div>
                <div class="flex items-end pb-0.5">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-white/80">
                        <input type="checkbox" name="site_fixed_bg_enabled" value="1"
                               class="rounded border-white/20 bg-[#0a0f0d] text-emerald-500"
                               <?php if(old('site_fixed_bg_enabled', ($settings['site_fixed_bg_enabled'] ?? '0') === '1')): echo 'checked'; endif; ?> />
                        <?php echo e(__('admin.settings.bg_enable')); ?>

                    </label>
                </div>
            </div>
        </section>

        <section class="admin-card p-5">
            <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.settings.contact')); ?></h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.phone')); ?></label>
                    <input name="contact_phone" value="<?php echo e(old('contact_phone', $settings['contact_phone'] ?? '')); ?>"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" placeholder="+963..." />
                </div>
                <div>
                    <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.contact_email')); ?></label>
                    <input type="email" name="contact_email" value="<?php echo e(old('contact_email', $settings['contact_email'] ?? '')); ?>"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" />
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.facebook')); ?></label>
                    <input name="facebook_url" value="<?php echo e(old('facebook_url', $settings['facebook_url'] ?? '')); ?>"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" placeholder="https://facebook.com/..." />
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.telegram')); ?></label>
                    <input name="telegram_url" value="<?php echo e(old('telegram_url', $settings['telegram_url'] ?? '')); ?>"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" placeholder="https://t.me/..." />
                </div>
            </div>
        </section>

        <section class="admin-card p-5">
            <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.settings.whatsapp_section')); ?></h2>
            <p class="mt-1 text-xs text-white/50"><?php echo e(__('admin.settings.whatsapp_hint')); ?></p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.whatsapp_number')); ?></label>
                    <input name="whatsapp_number" value="<?php echo e(old('whatsapp_number', $settings['whatsapp_number'] ?? '')); ?>"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" placeholder="9639xxxxxxxx" />
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-white/80">
                        <input type="checkbox" name="whatsapp_float_enabled" value="1"
                               class="rounded border-white/20 bg-[#0a0f0d] text-emerald-500"
                               <?php if(old('whatsapp_float_enabled', ($settings['whatsapp_float_enabled'] ?? '0') === '1')): echo 'checked'; endif; ?> />
                        <?php echo e(__('admin.settings.whatsapp_float')); ?>

                    </label>
                </div>
            </div>
        </section>

        <section class="admin-card p-5">
            <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.settings.seo_section')); ?></h2>
            <p class="mt-1 text-xs text-white/50"><?php echo e(__('admin.settings.seo_hint')); ?></p>
            <div class="mt-4 space-y-3">
                <div>
                    <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.meta_title_ar')); ?></label>
                    <input name="seo_meta_title" value="<?php echo e(old('seo_meta_title', $settings['seo_meta_title'] ?? '')); ?>"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" placeholder="<?php echo e(__('admin.settings.meta_title_placeholder')); ?>" />
                </div>
                <div>
                    <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.meta_title_en')); ?></label>
                    <input name="seo_meta_title_en" value="<?php echo e(old('seo_meta_title_en', $settings['seo_meta_title_en'] ?? '')); ?>"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" />
                </div>
                <div>
                    <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.meta_desc_ar')); ?></label>
                    <textarea name="seo_meta_description" rows="3"
                              class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white"><?php echo e(old('seo_meta_description', $settings['seo_meta_description'] ?? '')); ?></textarea>
                </div>
                <div>
                    <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.meta_desc_en')); ?></label>
                    <textarea name="seo_meta_description_en" rows="3" dir="ltr"
                              class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white"><?php echo e(old('seo_meta_description_en', $settings['seo_meta_description_en'] ?? '')); ?></textarea>
                </div>
                <div>
                    <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.keywords_ar')); ?></label>
                    <input name="seo_keywords" value="<?php echo e(old('seo_keywords', $settings['seo_keywords'] ?? '')); ?>"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
                </div>
                <div>
                    <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.keywords_en')); ?></label>
                    <input name="seo_keywords_en" value="<?php echo e(old('seo_keywords_en', $settings['seo_keywords_en'] ?? '')); ?>"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" />
                </div>
                <div>
                    <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.og_image')); ?></label>
                    <input name="seo_og_image" value="<?php echo e(old('seo_og_image', $settings['seo_og_image'] ?? '')); ?>"
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" placeholder="https://..." />
                </div>
            </div>
        </section>

        <section class="admin-card p-5">
            <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.settings.api_section')); ?></h2>
            <p class="mt-1 text-xs text-white/50"><?php echo e(__('admin.settings.api_hint')); ?></p>
            <div class="mt-4 space-y-3">
                <div>
                    <label class="text-xs font-medium text-white/70"><?php echo e(__('admin.settings.score_degree')); ?></label>
                    <input name="score_degree" value="<?php echo e(old('score_degree', $settings['score_degree'] ?? '0')); ?>" required
                           class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" maxlength="64" />
                </div>
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="admin-btn rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500">
                <?php echo e(__('admin.settings.save')); ?>

            </button>
        </div>
    </form>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="admin-card p-5">
            <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.settings.maintenance')); ?></h2>
            <p class="mt-2 text-xs text-white/55"><?php echo e(__('admin.settings.maintenance_hint')); ?></p>
            <form method="post" action="<?php echo e(route('admin.settings.clear-cache')); ?>" class="mt-4 space-y-3">
                <?php echo csrf_field(); ?>
                <label class="flex items-start gap-2 text-xs text-white/70">
                    <input type="checkbox" name="clear_cache" value="1" required class="mt-0.5 rounded border-white/20 bg-[#0a0f0d] text-emerald-500" />
                    <?php echo e(__('admin.settings.confirm_optimize')); ?> <span dir="ltr" class="text-white/90">optimize:clear</span>.
                </label>
                <button type="submit" class="admin-btn rounded-xl border border-amber-400/30 bg-amber-500/10 px-4 py-2.5 text-sm font-semibold text-amber-100 hover:bg-amber-500/15">
                    <?php echo e(__('admin.settings.clear_cache')); ?>

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
            var resolvedInitial = <?php echo json_encode(!empty($settings['site_logo_url'] ?? '') ? $settings['site_logo_url'] : '', 15, 512) ?>;
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
<?php $__env->stopSection(); ?>


<?php echo $__env->make('admin.spa-inner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/aephub.com/html/resources/views/admin/settings/index.blade.php ENDPATH**/ ?>