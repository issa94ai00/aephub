

<?php $__env->startSection('title', __('admin.dashboard.title')); ?>
<?php $__env->startSection('heading', __('admin.dashboard.heading')); ?>
<?php $__env->startSection('subheading', __('admin.dashboard.subheading')); ?>

<?php $__env->startSection('content'); ?>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="admin-card p-4">
            <div class="text-xs text-white/55"><?php echo e(__('admin.dashboard.courses_total')); ?></div>
            <div class="mt-2 text-2xl font-bold text-white"><?php echo e($stats['courses_total']); ?></div>
            <div class="mt-1 text-[11px] text-emerald-200/80"><?php echo e(__('admin.dashboard.published')); ?>: <?php echo e($stats['courses_published']); ?></div>
        </div>
        <div class="admin-card p-4">
            <div class="text-xs text-white/55"><?php echo e(__('admin.dashboard.users')); ?></div>
            <div class="mt-2 text-2xl font-bold text-white"><?php echo e($stats['users_total']); ?></div>
            <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-white/55">
                <?php $__currentLoopData = $stats['users_by_role']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r => $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span class="rounded-full bg-white/5 px-2 py-0.5"><?php echo e($r); ?>: <?php echo e($c); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <div class="admin-card p-4">
            <div class="text-xs text-white/55"><?php echo e(__('admin.dashboard.enrollments_pending')); ?></div>
            <div class="mt-2 text-2xl font-bold text-amber-200"><?php echo e($stats['enrollments_pending']); ?></div>
        </div>
        <div class="admin-card p-4">
            <div class="text-xs text-white/55"><?php echo e(__('admin.dashboard.payments_pending')); ?></div>
            <div class="mt-2 text-2xl font-bold text-amber-200"><?php echo e($stats['payments_pending']); ?></div>
            <a href="<?php echo e(route('admin.payments.index', ['status' => 'pending'])); ?>" class="mt-2 inline-block text-[11px] text-emerald-200 hover:underline"><?php echo e(__('admin.dashboard.view_requests')); ?></a>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="admin-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.dashboard.recent_courses')); ?></h2>
                <a href="<?php echo e(route('admin.courses.index')); ?>" class="text-xs text-emerald-200 hover:underline"><?php echo e(__('admin.dashboard.all_courses')); ?></a>
            </div>
            <ul class="mt-4 space-y-3 text-sm">
                <?php $__empty_1 = true; $__currentLoopData = $recentCourses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="flex items-start justify-between gap-3 border-b border-white/5 pb-3 last:border-0 last:pb-0">
                        <div>
                            <div class="font-medium text-white"><?php echo e($c->title); ?></div>
                            <div class="mt-0.5 text-xs text-white/50"><?php echo e($c->teacher->name ?? '—'); ?> · <?php echo e(trans()->has('admin.course_status.'.$c->status) ? __('admin.course_status.'.$c->status) : $c->status); ?></div>
                        </div>
                        <a href="<?php echo e(route('admin.courses.edit', $c)); ?>" class="shrink-0 text-xs text-emerald-200 hover:underline"><?php echo e(__('admin.dashboard.edit')); ?></a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="text-white/55"><?php echo e(__('admin.dashboard.no_courses')); ?></li>
                <?php endif; ?>
            </ul>
        </section>

        <section class="admin-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.dashboard.recent_payments')); ?></h2>
                <a href="<?php echo e(route('admin.payments.index')); ?>" class="text-xs text-emerald-200 hover:underline"><?php echo e(__('admin.dashboard.all_payments')); ?></a>
            </div>
            <ul class="mt-4 space-y-3 text-sm">
                <?php $__empty_1 = true; $__currentLoopData = $recentPayments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="flex items-start justify-between gap-3 border-b border-white/5 pb-3 last:border-0 last:pb-0">
                        <div>
                            <div class="font-medium text-white">#<?php echo e($p->id); ?> · <?php echo e($p->course->title ?? __('admin.dashboard.course_fallback')); ?></div>
                            <div class="mt-0.5 text-xs text-white/50"><?php echo e($p->user->name ?? '—'); ?> · <span class="text-white/70"><?php echo e(trans()->has('admin.payment_status.'.$p->status) ? __('admin.payment_status.'.$p->status) : $p->status); ?></span></div>
                        </div>
                        <a href="<?php echo e(route('admin.payments.show', $p)); ?>" class="shrink-0 text-xs text-emerald-200 hover:underline"><?php echo e(__('admin.dashboard.review')); ?></a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="text-white/55"><?php echo e(__('admin.dashboard.no_payments')); ?></li>
                <?php endif; ?>
            </ul>
        </section>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('admin.spa-inner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/aephub.com/html/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>