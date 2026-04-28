@extends('admin.spa-inner')

@section('title', __('admin.payments.show_title', ['id' => $payment->id]))
@section('heading', __('admin.payments.show_heading', ['id' => $payment->id]))
@section('subheading', $payment->course->title ?? __('admin.payments.course_fallback'))

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.payments.index') }}" class="text-xs text-emerald-200 hover:underline">{{ __('admin.payments.back_list') }}</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-4">
            <div class="admin-card p-5">
                <h2 class="text-sm font-semibold text-white">{{ __('admin.payments.student_block') }}</h2>
                <dl class="mt-3 grid gap-2 text-sm text-white/80">
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50">{{ __('admin.payments.name') }}</dt><dd>{{ $payment->user->name }}</dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50">{{ __('admin.payments.email') }}</dt><dd>{{ $payment->user->email }}</dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50">{{ __('admin.payments.role') }}</dt><dd>{{ $payment->user->role }}</dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50">{{ __('admin.payments.university') }}</dt><dd>{{ $payment->user->university ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50">{{ __('admin.payments.year_term') }}</dt><dd>{{ $payment->user->study_year ?? '—' }} / {{ $payment->user->study_term ?? '—' }}</dd></div>
                </dl>
            </div>

            <div class="admin-card p-5">
                <h2 class="text-sm font-semibold text-white">{{ __('admin.payments.payment_block') }}</h2>
                <dl class="mt-3 grid gap-2 text-sm text-white/80">
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50">{{ __('admin.payments.course') }}</dt><dd>{{ $payment->course->title ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50">{{ __('admin.payments.university_request') }}</dt><dd>{{ $payment->university }}</dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50">{{ __('admin.payments.study_year') }}</dt><dd>{{ $payment->study_year }}</dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50">{{ __('admin.payments.term') }}</dt><dd>{{ $payment->study_term }}</dd></div>
                    <div class="flex justify-between gap-3 border-b border-white/5 pb-2"><dt class="text-white/50">{{ __('admin.payments.subject') }}</dt><dd>{{ $payment->subject_name }}</dd></div>
                    <div class="flex justify-between gap-3 pb-2"><dt class="text-white/50">{{ __('admin.payments.status') }}</dt><dd><span class="rounded-full bg-white/5 px-2 py-0.5 text-xs">{{ trans()->has('admin.payment_status.'.$payment->status) ? __('admin.payment_status.'.$payment->status) : $payment->status }}</span></dd></div>
                </dl>
                @if ($payment->review_note)
                    <p class="mt-3 text-xs text-white/55">{{ __('admin.payments.review_note_label') }} {{ $payment->review_note }}</p>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            @if ($payment->receipt_path)
                <div class="admin-card p-5">
                    <h2 class="text-sm font-semibold text-white">{{ __('admin.payments.receipt_block') }}</h2>
                    <a href="{{ route('admin.payments.receipt', $payment) }}" target="_blank" rel="noopener" class="admin-btn mt-3 inline-flex w-full justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500">
                        {{ __('admin.payments.view_receipt') }}
                    </a>
                </div>
            @endif

            @if ($payment->status === 'pending')
                <div class="admin-card p-5">
                    <h2 class="text-sm font-semibold text-white">{{ __('admin.payments.review_block') }}</h2>
                    <form method="post" action="{{ route('admin.payments.review', $payment) }}" class="mt-3 space-y-3">
                        @csrf
                        <div>
                            <label class="text-xs text-white/60">{{ __('admin.payments.decision') }}</label>
                            <select name="status" required class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white">
                                <option value="approved">{{ __('admin.payments.option_approve') }}</option>
                                <option value="rejected">{{ __('admin.payments.option_reject') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-white/60">{{ __('admin.payments.note_optional') }}</label>
                            <textarea name="note" rows="3" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white">{{ old('note') }}</textarea>
                        </div>
                        <button type="submit" class="admin-btn w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500">{{ __('admin.payments.save_review') }}</button>
                    </form>
                </div>
            @else
                <div class="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4 text-xs text-amber-100/90">
                    {{ __('admin.payments.already_processed') }}
                </div>
            @endif
        </div>
    </div>
@endsection

