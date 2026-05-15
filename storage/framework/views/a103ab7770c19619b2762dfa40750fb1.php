

<?php $__env->startSection('title', __('admin.device_change.title')); ?>
<?php $__env->startSection('heading', __('admin.device_change.heading')); ?>
<?php $__env->startSection('subheading', __('admin.device_change.subheading')); ?>

<?php $__env->startSection('content'); ?>
    <div class="mb-4 flex flex-wrap items-center gap-2 text-xs">
        <span class="text-white/60"><?php echo e(__('admin.device_change.filter_status')); ?></span>
        <a href="<?php echo e(route('admin.device-change-requests.index')); ?>" class="rounded-full px-3 py-1 <?php echo e(($status === null || $status === '') ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-white/70 hover:bg-white/10'); ?>"><?php echo e(__('admin.courses.all')); ?></a>
        <a href="<?php echo e(route('admin.device-change-requests.index', ['status' => 'pending'])); ?>" class="rounded-full px-3 py-1 <?php echo e($status === 'pending' ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-white/70 hover:bg-white/10'); ?>"><?php echo e(__('admin.payment_status.pending')); ?></a>
        <a href="<?php echo e(route('admin.device-change-requests.index', ['status' => 'approved'])); ?>" class="rounded-full px-3 py-1 <?php echo e($status === 'approved' ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-white/70 hover:bg-white/10'); ?>"><?php echo e(__('admin.payment_status.approved')); ?></a>
        <a href="<?php echo e(route('admin.device-change-requests.index', ['status' => 'rejected'])); ?>" class="rounded-full px-3 py-1 <?php echo e($status === 'rejected' ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-white/70 hover:bg-white/10'); ?>"><?php echo e(__('admin.payment_status.rejected')); ?></a>
    </div>

    <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.device_change.col_id')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.device_change.col_student')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.device_change.col_device_requested')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.device_change.col_status')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.device_change.col_reason')); ?></th>
                        <th class="px-4 py-3 text-end"><?php echo e(__('admin.device_change.col_actions')); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php $__empty_1 = true; $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-white/60"><?php echo e($r->id); ?></td>
                            <td class="px-4 py-3 text-white/90">
                                <?php if($r->user): ?>
                                    <div class="font-medium text-white"><?php echo e($r->user->name); ?></div>
                                    <div class="text-xs text-white/50" dir="ltr"><?php echo e($r->user->email); ?></div>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-xs text-white/60" dir="ltr">
                                <?php echo e($r->requested_device_id ?: '—'); ?>

                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-white/5 px-2 py-0.5 text-xs"><?php echo e(__('admin.payment_status.'.$r->status)); ?></span>
                            </td>
                            <td class="max-w-[220px] px-4 py-3 text-xs text-white/55"><?php echo e(Str::limit($r->reason ?? '—', 120)); ?></td>
                            <td class="px-4 py-3 text-end align-top">
                                <?php if($r->status === 'pending'): ?>
                                    <div class="flex flex-col items-end gap-2">
                                        <form method="post" action="<?php echo e(route('admin.device-change-requests.review', $r)); ?>" class="inline" onsubmit="return confirm(<?php echo json_encode(__('admin.device_change.confirm_approve'), 15, 512) ?>);">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="status" value="approved" />
                                            <input type="hidden" name="action" value="reset_lock" />
                                            <button type="submit" class="text-xs font-medium text-emerald-200 hover:underline"><?php echo e(__('admin.device_change.approve_reset')); ?></button>
                                        </form>
                                        <form method="post" action="<?php echo e(route('admin.device-change-requests.review', $r)); ?>" class="inline w-full max-w-[200px] space-y-1 text-start" onsubmit="return confirm(<?php echo json_encode(__('admin.device_change.confirm_reject'), 15, 512) ?>);">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="status" value="rejected" />
                                            <textarea name="note" rows="2" class="w-full rounded-lg border border-white/10 bg-[#0a0f0d] px-2 py-1 text-xs text-white" placeholder="<?php echo e(__('admin.device_change.reject_note_placeholder')); ?>"></textarea>
                                            <button type="submit" class="text-xs text-rose-300 hover:underline"><?php echo e(__('admin.device_change.reject')); ?></button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-white/40"><?php echo e($r->review_note ? Str::limit($r->review_note, 80) : '—'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-white/55"><?php echo e(__('admin.device_change.empty')); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($requests->hasPages()): ?>
            <div class="border-t border-white/10 px-4 py-3 text-xs text-white/50">
                <?php echo e($requests->links()); ?>

            </div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('admin.spa-inner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/aephub.com/html/resources/views/admin/device-change-requests/index.blade.php ENDPATH**/ ?>