@php
    /** @var \App\Models\Course $course */
    /** @var \App\Models\CourseSession|null $session */
@endphp

<div class="mb-4">
    <a href="{{ route('admin.courses.sessions.index', $course) }}" class="text-xs text-emerald-200 hover:underline">← العودة للجلسات</a>
</div>

<form method="post" action="{{ $action }}" class="max-w-2xl space-y-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="admin-card space-y-4 p-5">
        <div>
            <label class="block text-xs font-medium text-white/70">العنوان (عربي)</label>
            <input name="title" value="{{ old('title', $session->title ?? '') }}" required
                   class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white" />
        </div>
        <div>
            <label class="block text-xs font-medium text-white/70">العنوان (إنجليزي، اختياري)</label>
            <input name="title_en" value="{{ old('title_en', $session->title_en ?? '') }}"
                   class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white" dir="ltr" />
        </div>
        <div>
            <label class="block text-xs font-medium text-white/70">الترتيب (Sort order)</label>
            <input type="number" name="sort_order" min="0" step="1" value="{{ old('sort_order', $session->sort_order ?? 0) }}"
                   class="mt-1.5 w-48 rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white" />
            <p class="mt-1 text-[11px] text-white/45">كلما كان الرقم أصغر تظهر الجلسة أولاً.</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="admin-btn rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500">
            حفظ
        </button>
        <a href="{{ route('admin.courses.sessions.index', $course) }}" class="rounded-xl border border-white/15 px-5 py-2.5 text-sm text-white/80 hover:bg-white/5">إلغاء</a>
    </div>
</form>

