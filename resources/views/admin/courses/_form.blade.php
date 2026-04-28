@php
    $isEdit = $course !== null;
    $action = $isEdit ? route('admin.courses.update', $course) : route('admin.courses.store');
@endphp

@if ($teachers->isEmpty())
    <div class="mb-4 rounded-2xl border border-amber-500/25 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
        {{ __('admin.course_form.no_teacher_warning') }}
    </div>
@endif

<form method="post" action="{{ $action }}" enctype="multipart/form-data" class="max-w-3xl space-y-5">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="admin-card space-y-4 p-5">
        <div>
            <label class="block text-xs font-medium text-white/70">{{ __('admin.course_form.teacher') }}</label>
            <select name="teacher_id" required @disabled($teachers->isEmpty()) class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white disabled:opacity-50">
                @foreach ($teachers as $t)
                    <option value="{{ $t->id }}" @selected(old('teacher_id', $course->teacher_id ?? null) == $t->id)>
                        {{ $t->name }} ({{ $t->role }}) — {{ $t->email }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-white/70">{{ __('admin.course_form.title_ar') }}</label>
            <input name="title" value="{{ old('title', $course->title ?? '') }}" required
                   class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white" />
        </div>
        <div>
            <label class="block text-xs font-medium text-white/70">{{ __('admin.course_form.title_en') }}</label>
            <input name="title_en" value="{{ old('title_en', $course->title_en ?? '') }}"
                   class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white" dir="ltr" />
        </div>

        <div>
            <label class="block text-xs font-medium text-white/70">{{ __('admin.course_form.desc_ar') }}</label>
            <textarea name="description" rows="4" class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white">{{ old('description', $course->description ?? '') }}</textarea>
        </div>
        <div>
            <label class="block text-xs font-medium text-white/70">{{ __('admin.course_form.desc_en') }}</label>
            <textarea name="description_en" rows="4" dir="ltr" class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white">{{ old('description_en', $course->description_en ?? '') }}</textarea>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-medium text-white/70">{{ __('admin.course_form.price_cents') }}</label>
                <input type="number" name="price_cents" min="0" step="1" required
                       value="{{ old('price_cents', $course->price_cents ?? 0) }}"
                       class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white" />
                <p class="mt-1 text-[11px] text-white/45">{{ __('admin.course_form.price_hint') }}</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-white/70">{{ __('admin.course_form.currency') }}</label>
                <input name="currency" value="{{ old('currency', $course->currency ?? 'SYP') }}" maxlength="16" required
                       class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white" />
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-white/70">{{ __('admin.course_form.status') }}</label>
            <select name="status" class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white">
                @foreach (['draft', 'published', 'archived'] as $val)
                    <option value="{{ $val }}" @selected(old('status', $course->status ?? 'draft') === $val)>{{ __('admin.course_status.'.$val) }}</option>
                @endforeach
            </select>
        </div>

        @if(isset($terms))
            <div>
                <label class="block text-xs font-medium text-white/70">الفصول الدراسية المرتبطة (اختياري)</label>
                <select name="study_term_ids[]" multiple class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white">
                    @foreach($terms as $id => $label)
                        @php
                            $currentIds = old('study_term_ids', $selectedTermIds ?? []);
                        @endphp
                        <option value="{{ $id }}" @selected(in_array((int)$id, array_map('intval', $currentIds ?? []), true))>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-white/45">يمكن ربط الدورة بأكثر من فصل دراسي.</p>
            </div>
        @endif

        <div>
            <label class="block text-xs font-medium text-white/70">{{ __('admin.course_form.cover') }}</label>
            <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp"
                   class="mt-1.5 w-full rounded-xl border border-white/10 bg-[#0a0f0d] px-3 py-2.5 text-sm text-white file:me-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:px-3 file:py-1.5 file:text-xs file:text-white" />
            <p class="mt-1 text-[11px] text-white/45">{{ __('admin.course_form.cover_hint') }}</p>
            @if ($isEdit && $course->cover_image_url)
                <p class="mt-2 text-xs text-white/60">{{ __('admin.course_form.current_image') }}</p>
                <img src="{{ $course->cover_image_url }}" alt="" class="mt-1 max-h-32 rounded-lg border border-white/10 object-cover" />
            @endif
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="admin-btn rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500">
            {{ $isEdit ? __('admin.course_form.submit_update') : __('admin.course_form.submit_create') }}
        </button>
        <a href="{{ route('admin.courses.index') }}" class="rounded-xl border border-white/15 px-5 py-2.5 text-sm text-white/80 hover:bg-white/5">{{ __('admin.course_form.cancel') }}</a>
    </div>
</form>
