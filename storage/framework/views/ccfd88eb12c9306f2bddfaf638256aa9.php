

<?php $__env->startSection('title', __('admin.user_reports.title')); ?>
<?php $__env->startSection('heading', __('admin.user_reports.heading')); ?>
<?php $__env->startSection('subheading', __('admin.user_reports.subheading')); ?>

<?php $__env->startSection('content'); ?>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="admin-card p-4">
            <div class="text-xs text-white/55"><?php echo e(__('admin.user_reports.total_users')); ?></div>
            <div class="mt-2 text-2xl font-bold text-white"><?php echo e($totalUsers); ?></div>
        </div>

        <div class="admin-card p-4">
            <div class="text-xs text-white/55"><?php echo e(__('admin.user_reports.connected_users')); ?></div>
            <div class="mt-2 text-2xl font-bold text-white"><?php echo e($connectedUsersCount); ?></div>
            <div class="mt-1 text-[11px] text-emerald-200/80"><?php echo e(__('admin.user_reports.active_devices')); ?>: <?php echo e($activeDeviceCount); ?></div>
            <div class="mt-1 text-[10px] text-white/50"><?php echo e(__('admin.user_reports.online_within')); ?></div>
        </div>

        <div class="admin-card p-4">
            <div class="text-xs text-white/55"><?php echo e(__('admin.user_reports.pending_payments')); ?></div>
            <div class="mt-2 text-2xl font-bold text-amber-200"><?php echo e($requests['pending_payments']); ?></div>
        </div>

        <div class="admin-card p-4">
            <div class="text-xs text-white/55"><?php echo e(__('admin.user_reports.pending_device_changes')); ?></div>
            <div class="mt-2 text-2xl font-bold text-amber-200"><?php echo e($requests['pending_device_changes']); ?></div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="admin-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.user_reports.requests_heading')); ?></h2>
            </div>

            <ul class="mt-4 space-y-3 text-sm">
                <li class="flex items-center justify-between rounded-lg bg-white/5 p-3">
                    <span><?php echo e(__('admin.user_reports.pending_payments')); ?></span>
                    <span class="font-semibold"><?php echo e($requests['pending_payments']); ?></span>
                </li>
                <li class="flex items-center justify-between rounded-lg bg-white/5 p-3">
                    <span><?php echo e(__('admin.user_reports.pending_device_changes')); ?></span>
                    <span class="font-semibold"><?php echo e($requests['pending_device_changes']); ?></span>
                </li>
                <li class="flex items-center justify-between rounded-lg bg-white/5 p-3">
                    <span><?php echo e(__('admin.user_reports.pending_teacher_approvals')); ?></span>
                    <span class="font-semibold"><?php echo e($requests['pending_teacher_approvals']); ?></span>
                </li>
                <li class="flex items-center justify-between rounded-lg bg-white/5 p-3">
                    <span><?php echo e(__('admin.user_reports.active_playback_sessions')); ?></span>
                    <span class="font-semibold"><?php echo e($requests['active_playback_sessions']); ?></span>
                </li>
            </ul>
        </section>

        <section class="admin-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.user_reports.connected_user_devices')); ?></h2>
                <span class="text-xs text-white/50"><?php echo e(__('admin.user_reports.latest_connected_devices')); ?></span>
            </div>
            <div class="mt-4 overflow-x-auto text-sm">
                <table class="min-w-full text-left text-white/80">
                    <thead class="border-b border-white/10 text-xs uppercase text-white/50">
                        <tr>
                            <th class="px-3 py-2"><?php echo e(__('admin.user_reports.col_user')); ?></th>
                            <th class="px-3 py-2"><?php echo e(__('admin.user_reports.col_platform')); ?></th>
                            <th class="px-3 py-2"><?php echo e(__('admin.user_reports.col_device_model')); ?></th>
                            <th class="px-3 py-2"><?php echo e(__('admin.user_reports.col_last_seen')); ?></th>
                            <th class="px-3 py-2"><?php echo e(__('admin.user_reports.col_ip')); ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php $__empty_1 = true; $__currentLoopData = $connectedDevices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $device): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="px-3 py-3">
                                    <div class="font-medium text-white"><?php echo e($device['user_name']); ?></div>
                                    <div class="text-xs text-white/50"><?php echo e($device['user_email']); ?> · <?php echo e($device['user_role']); ?></div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="font-medium text-white"><?php echo e($device['platform'] ?? '—'); ?></div>
                                    <div class="text-xs text-white/50"><?php echo e($device['app_version'] ?? '—'); ?></div>
                                </td>
                                <td class="px-3 py-3"><?php echo e($device['device_model'] ?? '—'); ?></td>
                                <td class="px-3 py-3"><?php echo e($device['last_seen_at'] ?? '—'); ?></td>
                                <td class="px-3 py-3"><?php echo e($device['last_ip'] ?? '—'); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td class="px-3 py-6 text-center text-white/55" colspan="5"><?php echo e(__('admin.user_reports.no_connected_users')); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.spa-inner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Damatech\Desktop\AEP\resources\views/admin/user-reports/index.blade.php ENDPATH**/ ?>