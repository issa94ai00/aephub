

<?php $__env->startSection('title', __('admin.security.title')); ?>
<?php $__env->startSection('heading', __('admin.security.heading')); ?>
<?php $__env->startSection('subheading', __('admin.security.subheading')); ?>

<?php $__env->startSection('content'); ?>
    <form method="get" class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4 text-xs">
        <div>
            <label class="block text-white/50"><?php echo e(__('admin.security.filter_type')); ?></label>
            <input type="text" name="type" value="<?php echo e($filters['type'] ?? ''); ?>" class="mt-1 rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" dir="ltr" placeholder="screenshot_attempt" />
        </div>
        <div>
            <label class="block text-white/50"><?php echo e(__('admin.security.filter_user_id')); ?></label>
            <input type="number" name="user_id" value="<?php echo e($filters['user_id'] ?? ''); ?>" class="mt-1 w-28 rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" min="1" />
        </div>
        <div>
            <label class="block text-white/50"><?php echo e(__('admin.security.filter_from')); ?></label>
            <input type="date" name="from" value="<?php echo e($filters['from'] ?? ''); ?>" class="mt-1 rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
        </div>
        <div>
            <label class="block text-white/50"><?php echo e(__('admin.security.filter_to')); ?></label>
            <input type="date" name="to" value="<?php echo e($filters['to'] ?? ''); ?>" class="mt-1 rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
        </div>
        <button type="submit" class="admin-btn rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"><?php echo e(__('admin.payments.apply')); ?></button>
        <a href="<?php echo e(route('admin.security-events.index')); ?>" class="rounded-xl border border-white/15 px-4 py-2 text-sm text-white/80 hover:bg-white/5"><?php echo e(__('admin.courses.all')); ?></a>
    </form>

    <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.security.col_id')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.security.col_type')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.security.col_user')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.security.col_device')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.security.col_time')); ?></th>
                        <th class="px-4 py-3 text-end"><?php echo e(__('admin.security.col_actions')); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php $__empty_1 = true; $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-white/60"><?php echo e($e->id); ?></td>
                            <td class="px-4 py-3 text-white/90"><?php echo e($e->displayTypeLabel()); ?></td>
                            <td class="px-4 py-3 text-xs text-white/70">
                                <?php if($e->user): ?>
                                    <?php echo e($e->user->name); ?><span class="block text-white/45" dir="ltr"><?php echo e($e->user->email); ?></span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="max-w-[140px] truncate px-4 py-3 text-xs text-white/50" dir="ltr" title="<?php echo e($e->device_id); ?>"><?php echo e($e->device_id ?: '—'); ?></td>
                            <td class="px-4 py-3 text-xs text-white/55"><?php echo e(optional($e->created_at)->timezone(config('app.timezone'))->format('Y-m-d H:i')); ?></td>
                            <td class="px-4 py-3 text-end">
                                <a href="<?php echo e(route('admin.security-events.show', $e)); ?>" class="text-emerald-200 hover:underline"><?php echo e(__('admin.security.details')); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-white/55"><?php echo e(__('admin.security.empty')); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($events->hasPages()): ?>
            <div class="border-t border-white/10 px-4 py-3 text-xs text-white/50">
                <?php echo e($events->links()); ?>

            </div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('admin.spa-inner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/aephub.com/html/resources/views/admin/security-events/index.blade.php ENDPATH**/ ?>