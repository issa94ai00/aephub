

<?php $__env->startSection('title', __('admin.payments.title')); ?>
<?php $__env->startSection('heading', __('admin.payments.heading')); ?>
<?php $__env->startSection('subheading', __('admin.payments.subheading')); ?>

<?php $__env->startSection('content'); ?>
    <form method="get" class="admin-card mb-4 flex flex-wrap items-end gap-3 p-4 text-xs">
        <div>
            <label class="block text-white/60"><?php echo e(__('admin.payments.filter_status')); ?></label>
            <select name="status" class="mt-1 rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white">
                <option value=""><?php echo e(__('admin.payments.all')); ?></option>
                <option value="pending" <?php if(($filters['status'] ?? '') === 'pending'): echo 'selected'; endif; ?>><?php echo e(__('admin.payments.pending')); ?></option>
                <option value="approved" <?php if(($filters['status'] ?? '') === 'approved'): echo 'selected'; endif; ?>><?php echo e(__('admin.payments.approved')); ?></option>
                <option value="rejected" <?php if(($filters['status'] ?? '') === 'rejected'): echo 'selected'; endif; ?>><?php echo e(__('admin.payments.rejected')); ?></option>
            </select>
        </div>
        <div>
            <label class="block text-white/60"><?php echo e(__('admin.payments.filter_course_id')); ?></label>
            <input type="number" name="course_id" value="<?php echo e($filters['course_id'] ?? ''); ?>" class="mt-1 w-28 rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
        </div>
        <div>
            <label class="block text-white/60"><?php echo e(__('admin.payments.filter_user_id')); ?></label>
            <input type="number" name="user_id" value="<?php echo e($filters['user_id'] ?? ''); ?>" class="mt-1 w-28 rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
        </div>
        <button type="submit" class="admin-btn rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-900/20 hover:bg-emerald-500"><?php echo e(__('admin.payments.apply')); ?></button>
    </form>

    <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.payments.col_id')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.payments.col_user')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.payments.col_course')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.payments.col_status')); ?></th>
                        <th class="px-4 py-3 text-end"><?php echo e(__('admin.payments.details')); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php $__empty_1 = true; $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-white/60"><?php echo e($p->id); ?></td>
                            <td class="px-4 py-3 text-white"><?php echo e($p->user->name ?? '—'); ?></td>
                            <td class="px-4 py-3 text-white/80"><?php echo e($p->course->title ?? '—'); ?></td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-white/5 px-2 py-0.5 text-xs"><?php echo e(trans()->has('admin.payment_status.'.$p->status) ? __('admin.payment_status.'.$p->status) : $p->status); ?></span>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <a href="<?php echo e(route('admin.payments.show', $p)); ?>" class="text-emerald-200 hover:underline"><?php echo e(__('admin.payments.details')); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-white/55"><?php echo e(__('admin.payments.empty')); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($payments->hasPages()): ?>
            <div class="border-t border-white/10 px-4 py-3 text-xs text-white/50">
                <?php echo e($payments->links()); ?>

            </div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('admin.spa-inner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/aephub.com/html/resources/views/admin/payments/index.blade.php ENDPATH**/ ?>