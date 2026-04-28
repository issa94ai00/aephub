@extends('admin.spa-inner')

@section('title', 'فيديوهات الجلسة')
@section('heading', 'فيديوهات الجلسة')
@section('subheading', $course->title . ' • ' . $session->title)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm text-white/70">
            <span class="text-white/50">الجلسة:</span> <span class="font-semibold text-white">{{ $session->title }}</span>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.courses.sessions.index', $course) }}" class="admin-btn rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/90 hover:bg-white/10">← الجلسات</a>
            <a href="{{ route('admin.courses.edit', $course) }}" class="admin-btn rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/90 hover:bg-white/10">تعديل الدورة</a>
        </div>
    </div>

    <form method="post" action="{{ route('admin.courses.sessions.videos.sync', [$course, $session]) }}" class="space-y-4">
        @csrf

        <div class="admin-card p-5">
            <p class="text-xs text-white/55">
                اختر فيديوهات هذه الجلسة ثم حدد ترتيبها داخل الجلسة. (الترتيب الأصغر يظهر أولاً).
            </p>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10 text-sm">
                    <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                        <tr>
                            <th class="px-3 py-2 text-start">تفعيل</th>
                            <th class="px-3 py-2 text-start">#</th>
                            <th class="px-3 py-2 text-start">عنوان الفيديو</th>
                            <th class="px-3 py-2 text-start">ترتيب داخل الجلسة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($videos as $v)
                            @php
                                $isChecked = $current->has($v->id);
                                $pivotOrder = $isChecked ? (int) ($current->get($v->id)->pivot->sort_order ?? 0) : 0;
                            @endphp
                            <tr class="hover:bg-white/[0.02]">
                                <td class="px-3 py-2">
                                    <input type="checkbox" name="video_ids[]" value="{{ $v->id }}"
                                           class="rounded border-white/20 bg-[#0a0f0d] text-emerald-500"
                                           @checked(old('video_ids') ? in_array($v->id, old('video_ids', [])) : $isChecked) />
                                </td>
                                <td class="px-3 py-2 text-white/60">{{ $v->id }}</td>
                                <td class="px-3 py-2 text-white">
                                    <div class="font-medium">{{ $v->title }}</div>
                                    @if(!empty($v->title_en))
                                        <div class="mt-0.5 text-xs text-white/45" dir="ltr">{{ $v->title_en }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" min="0" step="1" name="sort_order[{{ $v->id }}]"
                                           value="{{ old('sort_order.'.$v->id, $pivotOrder) }}"
                                           class="w-28 rounded-lg border border-white/10 bg-[#0a0f0d] px-2 py-1.5 text-xs text-white" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-white/55">لا توجد فيديوهات لهذه الدورة بعد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="admin-btn rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/25 hover:bg-emerald-500">
                حفظ فيديوهات الجلسة
            </button>
        </div>
    </form>
@endsection


