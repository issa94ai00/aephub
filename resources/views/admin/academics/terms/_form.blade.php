@php
    /** @var \App\Models\University $university */
    /** @var \App\Models\Faculty $faculty */
    /** @var \App\Models\StudyYear $year */
    /** @var \App\Models\StudyTerm|null $term */
@endphp

<div class="mb-4">
    <a href="{{ route('admin.academics.universities.faculties.years.terms.index', [$university, $faculty, $year]) }}" class="text-xs text-emerald-200 hover:underline">← {{ app()->getLocale() === 'en' ? 'Back to terms' : 'العودة للفصول' }}</a>
</div>

<form method="post" action="{{ $action }}" class="max-w-2xl space-y-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="admin-card space-y-4 p-5">
        <div>
            <label class="block text-xs font-medium text-white/70">{{ app()->getLocale() === 'en' ? 'Term number' : 'رقم الفصل' }}</label>
            <input type="number" name="term_number" min="1" step="1" value="{{ old('term_number', $term->term_number ?? 1) }}" required
                   class="mt-1.5 w-48 rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white" />
        </div>
        <div>
            <label class="block text-xs font-medium text-white/70">{{ app()->getLocale() === 'en' ? 'Label (Arabic, optional)' : 'اسم/وصف (عربي، اختياري)' }}</label>
            <input name="name" value="{{ old('name', $term->name ?? '') }}"
                   class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white" />
        </div>
        <div>
            <label class="block text-xs font-medium text-white/70">{{ app()->getLocale() === 'en' ? 'Label (English, optional)' : 'اسم/وصف (إنجليزي، اختياري)' }}</label>
            <input name="name_en" value="{{ old('name_en', $term->name_en ?? '') }}"
                   class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white" dir="ltr" />
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="admin-btn rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500">
            {{ app()->getLocale() === 'en' ? 'Save' : 'حفظ' }}
        </button>
        <a href="{{ route('admin.academics.universities.faculties.years.terms.index', [$university, $faculty, $year]) }}" class="rounded-xl border border-white/15 px-5 py-2.5 text-sm text-white/80 hover:bg-white/5">{{ app()->getLocale() === 'en' ? 'Cancel' : 'إلغاء' }}</a>
    </div>
</form>

