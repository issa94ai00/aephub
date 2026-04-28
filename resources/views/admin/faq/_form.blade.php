@php
    $editing = isset($faq) && $faq !== null;
@endphp

<div class="admin-card space-y-4 p-5">
    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.faq.sort_order') }}</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $faq->sort_order ?? '') }}" min="0" max="99999"
               class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
        <p class="mt-1 text-[11px] text-white/40">{{ __('admin.faq.sort_hint') }}</p>
    </div>

    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.faq.question_ar') }}</label>
        <input name="question" value="{{ old('question', $faq->question ?? '') }}" required
               class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
    </div>
    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.faq.question_en') }}</label>
        <input name="question_en" value="{{ old('question_en', $faq->question_en ?? '') }}" dir="ltr"
               class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white" />
    </div>
    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.faq.answer_ar') }}</label>
        <textarea name="answer" rows="5" required class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white">{{ old('answer', $faq->answer ?? '') }}</textarea>
    </div>
    <div>
        <label class="text-xs font-medium text-white/70">{{ __('admin.faq.answer_en') }}</label>
        <textarea name="answer_en" rows="5" dir="ltr" class="mt-1 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2 text-sm text-white">{{ old('answer_en', $faq->answer_en ?? '') }}</textarea>
    </div>

    <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="admin-btn rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500">
            {{ $editing ? __('admin.faq.save') : __('admin.faq.create_btn') }}
        </button>
        <a href="{{ route('admin.faqs.index') }}" class="admin-btn inline-flex items-center rounded-xl border border-white/10 bg-white/5 px-5 py-2.5 text-sm font-medium text-white/90 hover:bg-white/10">
            {{ __('admin.faq.cancel') }}
        </a>
    </div>
</div>
