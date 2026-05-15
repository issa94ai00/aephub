

<?php ($catalogMode = $catalogMode ?? false); ?>

<?php $__env->startSection('title', $catalogMode ? __('admin.courses.title_student') : __('admin.courses.title')); ?>
<?php $__env->startSection('heading', $catalogMode ? __('admin.courses.heading_student') : __('admin.courses.heading')); ?>
<?php $__env->startSection('subheading', $catalogMode ? __('admin.courses.subheading_student') : __('admin.courses.subheading')); ?>

<?php $__env->startSection('content'); ?>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <?php if($catalogMode): ?>
            <p class="text-xs text-white/55"><?php echo e(__('admin.courses.student_catalog_hint')); ?></p>
            <a href="<?php echo e(route('admin.courses.index')); ?>" class="text-xs text-emerald-200 hover:underline"><?php echo e(__('admin.courses.link_full_management')); ?></a>
        <?php else: ?>
            <form method="get" class="flex flex-wrap items-center gap-2 text-xs">
                <label class="text-white/60"><?php echo e(__('admin.courses.filter_status')); ?></label>
                <select name="status" class="rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-white" onchange="this.form.submit()">
                    <option value=""><?php echo e(__('admin.courses.all')); ?></option>
                    <option value="draft" <?php if($status === 'draft'): echo 'selected'; endif; ?>><?php echo e(__('admin.course_status.draft')); ?></option>
                    <option value="published" <?php if($status === 'published'): echo 'selected'; endif; ?>><?php echo e(__('admin.course_status.published')); ?></option>
                    <option value="archived" <?php if($status === 'archived'): echo 'selected'; endif; ?>><?php echo e(__('admin.course_status.archived')); ?></option>
                </select>
            </form>
            <div class="flex flex-wrap items-center gap-2">
                <a href="<?php echo e(route('admin.courses.student-catalog')); ?>" class="rounded-xl border border-white/15 px-4 py-2 text-xs font-medium text-white/85 hover:bg-white/5"><?php echo e(__('admin.nav.student_courses')); ?></a>
                <a href="<?php echo e(route('admin.courses.create')); ?>" class="admin-btn inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-900/25 hover:bg-emerald-500">
                    + <?php echo e(__('admin.courses.new')); ?>

                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.courses.col_id')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.courses.col_title')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.courses.col_teacher')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.courses.col_status')); ?></th>
                        <th class="px-4 py-3 text-start"><?php echo e(__('admin.courses.col_price')); ?></th>
                        <th class="px-4 py-3 text-end"><?php echo e(__('admin.courses.col_actions')); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php $__empty_1 = true; $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-white/60"><?php echo e($course->id); ?></td>
                            <td class="px-4 py-3 font-medium text-white"><?php echo e($course->title); ?></td>
                            <td class="px-4 py-3 text-white/70"><?php echo e($course->teacher->name ?? '—'); ?></td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-white/5 px-2 py-0.5 text-xs"><?php echo e(trans()->has('admin.course_status.'.$course->status) ? __('admin.course_status.'.$course->status) : $course->status); ?></span>
                            </td>
                            <td class="px-4 py-3 text-white/80">
                                <?php echo e(number_format(($course->price_cents ?? 0) / 100, 2)); ?> <?php echo e($course->currency ?? 'SYP'); ?>

                            </td>
                            <td class="px-4 py-3 text-end whitespace-nowrap">
                                <a href="<?php echo e(route('admin.courses.edit', $course)); ?>" class="text-emerald-200 hover:underline"><?php echo e(__('admin.courses.edit')); ?></a>
                                <?php if(!$catalogMode): ?>
                                    <form action="<?php echo e(route('admin.courses.destroy', $course)); ?>" method="post" class="inline ms-2" onsubmit="return confirm(<?php echo json_encode(__('admin.courses.confirm_delete'), 15, 512) ?>);">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="text-rose-300 hover:underline"><?php echo e(__('admin.courses.delete')); ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-white/55"><?php echo e(__('admin.courses.empty')); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($courses->hasPages()): ?>
            <div class="border-t border-white/10 px-4 py-3 text-xs text-white/50">
                <?php echo e($courses->links()); ?>

            </div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('admin.spa-inner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/aephub.com/html/resources/views/admin/courses/index.blade.php ENDPATH**/ ?>