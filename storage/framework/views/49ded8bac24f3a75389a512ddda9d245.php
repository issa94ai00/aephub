

<?php $__env->startSection('title', __('admin.payments.show_title', ['id' => $payment->id])); ?>
<?php $__env->startSection('heading', __('admin.payments.show_heading', ['id' => $payment->id])); ?>
<?php $__env->startSection('subheading', $payment->course->title ?? __('admin.payments.course_fallback')); ?>

<?php $__env->startSection('content'); ?>
    <div class="mb-4">
        <a href="<?php echo e(route('admin.payments.index')); ?>" class="text-xs text-emerald-200 hover:underline"><?php echo e(__('admin.payments.back_list')); ?></a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-4">
            <div class="admin-card p-5">
                <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.payments.student_block')); ?></h2>
                <dl class="mt-3 grid gap-2 text-sm text-white/80">
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50"><?php echo e(__('admin.payments.name')); ?></dt><dd><?php echo e($payment->user->name); ?></dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50"><?php echo e(__('admin.payments.email')); ?></dt><dd><?php echo e($payment->user->email); ?></dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50"><?php echo e(__('admin.payments.role')); ?></dt><dd><?php echo e($payment->user->role); ?></dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50"><?php echo e(__('admin.payments.university')); ?></dt><dd><?php echo e($payment->user->university ?? '—'); ?></dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50"><?php echo e(__('admin.payments.year_term')); ?></dt><dd><?php echo e($payment->user->study_year ?? '—'); ?> / <?php echo e($payment->user->study_term ?? '—'); ?></dd></div>
                </dl>
            </div>

            <div class="admin-card p-5">
                <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.payments.payment_block')); ?></h2>
                <dl class="mt-3 grid gap-2 text-sm text-white/80">
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50"><?php echo e(__('admin.payments.course')); ?></dt><dd><?php echo e($payment->course->title ?? '—'); ?></dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50"><?php echo e(__('admin.payments.university_request')); ?></dt><dd><?php echo e($payment->university); ?></dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50"><?php echo e(__('admin.payments.study_year')); ?></dt><dd><?php echo e($payment->study_year); ?></dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50"><?php echo e(__('admin.payments.term')); ?></dt><dd><?php echo e($payment->study_term); ?></dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50"><?php echo e(__('admin.payments.subject')); ?></dt><dd><?php echo e($payment->subject_name); ?></dd></div>
                    <div class="flex justify-between gap-3 pb-2"><dt class="text-white/50"><?php echo e(__('admin.payments.status')); ?></dt><dd><span class="rounded-full bg-white/5 px-2 py-0.5 text-xs"><?php echo e(trans()->has('admin.payment_status.'.$payment->status) ? __('admin.payment_status.'.$payment->status) : $payment->status); ?></span></dd></div>
                </dl>
                <?php if($payment->review_note): ?>
                    <p class="mt-3 text-xs text-white/55"><?php echo e(__('admin.payments.review_note_label')); ?> <?php echo e($payment->review_note); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="space-y-4">
            <?php if($payment->receipt_path): ?>
                <div class="admin-card p-5">
                    <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.payments.receipt_block')); ?></h2>
                    <a href="<?php echo e(route('admin.payments.receipt', $payment)); ?>" target="_blank" rel="noopener" class="admin-btn mt-3 inline-flex w-full justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500">
                        <?php echo e(__('admin.payments.view_receipt')); ?>

                    </a>
                </div>
            <?php endif; ?>

            <?php if($payment->status === 'pending'): ?>
                <div class="admin-card p-5">
                    <h2 class="text-sm font-semibold text-white"><?php echo e(__('admin.payments.review_block')); ?></h2>
                    <form method="post" action="<?php echo e(route('admin.payments.review', $payment)); ?>" class="mt-3 space-y-3">
                        <?php echo csrf_field(); ?>
                        <div>
                            <label class="text-xs text-white/60"><?php echo e(__('admin.payments.decision')); ?></label>
                            <select name="status" required class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white">
                                <option value="approved"><?php echo e(__('admin.payments.option_approve')); ?></option>
                                <option value="rejected"><?php echo e(__('admin.payments.option_reject')); ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-white/60"><?php echo e(__('admin.payments.note_optional')); ?></label>
                            <textarea name="note" rows="3" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white"><?php echo e(old('note')); ?></textarea>
                        </div>
                        <button type="submit" class="admin-btn w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500"><?php echo e(__('admin.payments.save_review')); ?></button>
                    </form>
                </div>
            <?php else: ?>
                <div class="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4 text-xs text-amber-100/90">
                    <?php echo e(__('admin.payments.already_processed')); ?>

                </div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('admin.spa-inner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/aephub.com/html/resources/views/admin/payments/show.blade.php ENDPATH**/ ?>