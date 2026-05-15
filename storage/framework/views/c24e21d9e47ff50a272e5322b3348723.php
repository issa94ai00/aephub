

<?php $__env->startSection('title', __('admin.teachers.title')); ?>
<?php $__env->startSection('heading', __('admin.teachers.heading')); ?>
<?php $__env->startSection('subheading', __('admin.teachers.subheading')); ?>

<?php $__env->startSection('content'); ?>
    <section class="admin-card p-5">
        <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.teachers.pending_title')); ?></h2>
        <p class="mt-1 text-xs text-white/55"><?php echo e(__('admin.teachers.pending_hint')); ?></p>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-3 py-2 text-start"><?php echo e(__('admin.teachers.col_id')); ?></th>
                        <th class="px-3 py-2 text-start"><?php echo e(__('admin.teachers.col_name')); ?></th>
                        <th class="px-3 py-2 text-start"><?php echo e(__('admin.teachers.col_email')); ?></th>
                        <th class="px-3 py-2 text-start"><?php echo e(__('admin.teachers.col_university')); ?></th>
                        <th class="px-3 py-2 text-start"><?php echo e(__('admin.teachers.col_year_term')); ?></th>
                        <th class="px-3 py-2 text-end"><?php echo e(__('admin.teachers.col_actions')); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php $__empty_1 = true; $__currentLoopData = $pendingTeachers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $teacher): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-3 py-2 text-white/60"><?php echo e($teacher->id); ?></td>
                            <td class="px-3 py-2 text-white"><?php echo e($teacher->name); ?></td>
                            <td class="px-3 py-2 text-white/75"><?php echo e($teacher->email); ?></td>
                            <td class="px-3 py-2 text-white/70"><?php echo e($teacher->university ?: '—'); ?></td>
                            <td class="px-3 py-2 text-white/70"><?php echo e(trim(($teacher->study_year ?: '') . ' ' . ($teacher->study_term ?: '')) ?: '—'); ?></td>
                            <td class="px-3 py-2 text-end">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <form method="post" action="<?php echo e(route('admin.teachers.approve', $teacher)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="admin-btn rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500">
                                            <?php echo e(__('admin.teachers.approve')); ?>

                                        </button>
                                    </form>
                                    <form method="post" action="<?php echo e(route('admin.teachers.reject', $teacher)); ?>" onsubmit="return confirm(<?php echo json_encode(__('admin.teachers.confirm_reject'), 15, 512) ?>);">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="admin-btn rounded-lg border border-rose-400/40 bg-rose-500/10 px-3 py-1.5 text-xs font-semibold text-rose-100 hover:bg-rose-500/20">
                                            <?php echo e(__('admin.teachers.reject')); ?>

                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-white/55"><?php echo e(__('admin.teachers.no_pending')); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-card mt-6 p-5">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.teachers.courses_title')); ?></h2>
                <p class="mt-1 text-xs text-white/55"><?php echo e(__('admin.teachers.courses_hint')); ?></p>
            </div>
            <form method="get" class="flex items-center gap-2">
                <label class="text-xs text-white/60"><?php echo e(__('admin.teachers.filter_teacher')); ?></label>
                <select name="teacher_id" class="rounded-lg border border-white/10 bg-[#0a0f0d] px-2 py-1.5 text-xs text-white" onchange="this.form.submit()">
                    <option value=""><?php echo e(__('admin.courses.all')); ?></option>
                    <?php $__currentLoopData = $teacherOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $teacher): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($teacher->id); ?>" <?php if($selectedTeacherId === (int) $teacher->id): echo 'selected'; endif; ?>>
                            <?php echo e($teacher->name); ?> (<?php echo e($teacher->role === 'admin' ? __('admin.teachers.label_admin') : __('admin.teachers.label_teacher')); ?>)
                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </form>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-3 py-2 text-start"><?php echo e(__('admin.teachers.col_course')); ?></th>
                        <th class="px-3 py-2 text-start"><?php echo e(__('admin.teachers.col_current_teacher')); ?></th>
                        <th class="px-3 py-2 text-start"><?php echo e(__('admin.teachers.col_status')); ?></th>
                        <th class="px-3 py-2 text-end"><?php echo e(__('admin.teachers.col_change_teacher')); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php $__empty_1 = true; $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-3 py-2 text-white">
                                <div class="font-medium"><?php echo e($course->title); ?></div>
                                <div class="text-[11px] text-white/45">#<?php echo e($course->id); ?></div>
                            </td>
                            <td class="px-3 py-2 text-white/75">
                                <?php echo e($course->teacher->name ?? '—'); ?>

                                <?php if(($course->teacher->role ?? null) === 'teacher' && ($course->teacher->teacher_approval_status ?? null) !== 'approved'): ?>
                                    <span class="ms-1 rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] text-amber-200"><?php echo e(__('admin.teachers.not_approved_badge')); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 text-white/70"><?php echo e(trans()->has('admin.course_status.'.$course->status) ? __('admin.course_status.'.$course->status) : $course->status); ?></td>
                            <td class="px-3 py-2 text-end">
                                <form method="post" action="<?php echo e(route('admin.teachers.reassign-course', $course)); ?>" class="flex flex-wrap items-center justify-end gap-2">
                                    <?php echo csrf_field(); ?>
                                    <select name="teacher_id" class="rounded-lg border border-white/10 bg-[#0a0f0d] px-2 py-1.5 text-xs text-white">
                                        <?php $__currentLoopData = $teacherOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $teacher): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($teacher->id); ?>" <?php if((int) $course->teacher_id === (int) $teacher->id): echo 'selected'; endif; ?>>
                                                <?php echo e($teacher->name); ?> (<?php echo e($teacher->role === 'admin' ? __('admin.teachers.label_admin') : __('admin.teachers.label_teacher')); ?>)
                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <button type="submit" class="admin-btn rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white hover:bg-white/15">
                                        <?php echo e(__('admin.teachers.save')); ?>

                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-white/55"><?php echo e(__('admin.teachers.no_courses')); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($courses->hasPages()): ?>
            <div class="mt-4 border-t border-white/10 pt-3 text-xs text-white/60">
                <?php echo e($courses->links()); ?>

            </div>
        <?php endif; ?>
    </section>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('admin.spa-inner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/aephub.com/html/resources/views/admin/teachers/index.blade.php ENDPATH**/ ?>