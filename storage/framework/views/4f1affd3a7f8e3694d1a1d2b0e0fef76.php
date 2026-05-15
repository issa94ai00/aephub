

<?php $__env->startSection('title', __('admin.users.title')); ?>
<?php $__env->startSection('heading', __('admin.users.heading')); ?>
<?php $__env->startSection('subheading', __('admin.users.subheading')); ?>

<?php $__env->startSection('content'); ?>
    <div class="mb-4 flex flex-wrap items-center gap-2 text-xs">
        <span class="text-white/60"><?php echo e(__('admin.users.filter_role')); ?></span>
        <a href="<?php echo e(route('admin.users.index')); ?>" class="rounded-full px-3 py-1 <?php echo e($role === null || $role === '' ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-white/70 hover:bg-white/10'); ?>"><?php echo e(__('admin.courses.all')); ?></a>
        <a href="<?php echo e(route('admin.users.index', ['role' => 'student'])); ?>" class="rounded-full px-3 py-1 <?php echo e($role === 'student' ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-white/70 hover:bg-white/10'); ?>"><?php echo e(__('admin.users.role_student')); ?></a>
        <a href="<?php echo e(route('admin.users.index', ['role' => 'teacher'])); ?>" class="rounded-full px-3 py-1 <?php echo e($role === 'teacher' ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-white/70 hover:bg-white/10'); ?>"><?php echo e(__('admin.users.role_teacher')); ?></a>
        <a href="<?php echo e(route('admin.users.index', ['role' => 'teacher_pending'])); ?>" class="rounded-full px-3 py-1 <?php echo e($role === 'teacher_pending' ? 'bg-amber-500/20 text-amber-100' : 'bg-white/5 text-white/70 hover:bg-white/10'); ?>"><?php echo e(__('admin.users.teachers_pending')); ?></a>
        <a href="<?php echo e(route('admin.users.index', ['role' => 'admin'])); ?>" class="rounded-full px-3 py-1 <?php echo e($role === 'admin' ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-white/70 hover:bg-white/10'); ?>"><?php echo e(__('admin.users.role_admin')); ?></a>
        <a href="<?php echo e(route('admin.users.index', ['frozen' => 1])); ?>" class="rounded-full px-3 py-1 <?php echo e(($frozen ?? '') === '1' ? 'bg-rose-500/20 text-rose-100' : 'bg-white/5 text-white/70 hover:bg-white/10'); ?>"><?php echo e(__('admin.users.filter_frozen')); ?></a>
    </div>

    <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.users.col_id')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.users.col_name')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.users.col_email')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.users.col_role')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.users.col_teacher_status')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.users.col_device_lock')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.users.col_account')); ?></th>
                        <th class="px-4 py-3 text-end"><?php echo e(__('admin.users.col_actions')); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-white/60"><?php echo e($u->id); ?></td>
                            <td class="px-4 py-3 font-medium text-white"><?php echo e($u->name); ?></td>
                            <td class="px-4 py-3 text-white/70"><?php echo e($u->email); ?></td>
                            <td class="px-4 py-3">
                                <form method="post" action="<?php echo e(route('admin.users.role', $u)); ?>" class="flex flex-wrap items-center gap-2">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <select name="role" class="rounded-lg border border-white/10 bg-[#0a0f0d] px-2 py-1 text-xs text-white" onchange="this.form.submit()">
                                        <?php $__currentLoopData = ['student' => __('admin.users.role_student'), 'teacher' => __('admin.users.role_teacher'), 'admin' => __('admin.users.role_admin')]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($val); ?>" <?php if($u->role === $val): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-xs text-white/70">
                                <?php if($u->role === 'teacher'): ?>
                                    <?php if(($u->teacher_approval_status ?? 'approved') === 'approved'): ?>
                                        <span class="rounded-full bg-emerald-500/15 px-2 py-1 text-emerald-100"><?php echo e(__('admin.users.teacher_approved')); ?></span>
                                    <?php elseif(($u->teacher_approval_status ?? '') === 'pending'): ?>
                                        <span class="rounded-full bg-amber-500/15 px-2 py-1 text-amber-100"><?php echo e(__('admin.users.teacher_pending')); ?></span>
                                    <?php else: ?>
                                        <span class="rounded-full bg-rose-500/15 px-2 py-1 text-rose-100"><?php echo e(__('admin.users.teacher_rejected')); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-white/45">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-xs text-white/60">
                                <?php if($u->device_lock_enabled): ?>
                                    <span class="text-amber-200/90"><?php echo e(__('admin.users.device_on')); ?></span>
                                    <?php if($u->locked_device_id): ?>
                                        <span class="block max-w-[140px] truncate text-white/45" title="<?php echo e($u->locked_device_id); ?>"><?php echo e($u->locked_device_id); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-white/45"><?php echo e(__('admin.users.device_off')); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <?php if(($u->status ?? 'active') === 'frozen'): ?>
                                    <span class="rounded-full bg-rose-500/15 px-2 py-0.5 text-rose-100"><?php echo e(__('admin.users.account_frozen')); ?></span>
                                <?php else: ?>
                                    <span class="text-white/50"><?php echo e(__('admin.users.account_active')); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <div class="flex flex-col items-end gap-1">
                                    <?php if($u->id !== auth()->id()): ?>
                                        <?php if(($u->status ?? 'active') === 'frozen'): ?>
                                            <form method="post" action="<?php echo e(route('admin.users.unfreeze', $u)); ?>" onsubmit="return confirm(<?php echo json_encode(__('admin.users.confirm_unfreeze'), 15, 512) ?>);">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="text-xs text-emerald-200 hover:underline"><?php echo e(__('admin.users.unfreeze')); ?></button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="<?php echo e(route('admin.users.freeze', $u)); ?>" onsubmit="return confirm(<?php echo json_encode(__('admin.users.confirm_freeze'), 15, 512) ?>);">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="text-xs text-rose-200 hover:underline"><?php echo e(__('admin.users.freeze')); ?></button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if($u->locked_device_id || $u->device_lock_enabled): ?>
                                        <form method="post" action="<?php echo e(route('admin.users.reset-device', $u)); ?>" onsubmit="return confirm(<?php echo json_encode(__('admin.users.confirm_reset_lock'), 15, 512) ?>);">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="text-xs text-emerald-200 hover:underline"><?php echo e(__('admin.users.reset_lock')); ?></button>
                                        </form>
                                    <?php elseif($u->id === auth()->id()): ?>
                                        <span class="text-white/35">—</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-white/55"><?php echo e(__('admin.users.empty')); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($users->hasPages()): ?>
            <div class="border-t border-white/10 px-4 py-3 text-xs text-white/50">
                <?php echo e($users->links()); ?>

            </div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('admin.spa-inner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/aephub.com/html/resources/views/admin/users/index.blade.php ENDPATH**/ ?>