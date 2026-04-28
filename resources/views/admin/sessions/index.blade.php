@extends('admin.spa-inner')

@section('title', 'جلسات الدورة')
@section('heading', 'جلسات الدورة')
@section('subheading', $course->title)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm text-white/70">
            <span class="text-white/50">الدورة:</span> <span class="font-semibold text-white">{{ $course->title }}</span>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.courses.edit', $course) }}" class="admin-btn rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/90 hover:bg-white/10">تعديل الدورة</a>
            <a href="{{ route('admin.courses.sessions.create', $course) }}" class="admin-btn rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-900/20 hover:bg-emerald-500">+ جلسة جديدة</a>
        </div>
    </div>

    <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-4 py-3 text-start">#</th>
                        <th class="px-4 py-3 text-start">العنوان</th>
                        <th class="px-4 py-3 text-start">الترتيب</th>
                        <th class="px-4 py-3 text-start">عدد الفيديوهات</th>
                        <th class="px-4 py-3 text-end">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse ($sessions as $s)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-white/60">{{ $s->id }}</td>
                            <td class="px-4 py-3 font-medium text-white">
                                {{ $s->title }}
                                @if(!empty($s->title_en))
                                    <div class="mt-0.5 text-xs text-white/45" dir="ltr">{{ $s->title_en }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-white/70">{{ $s->sort_order }}</td>
                            <td class="px-4 py-3 text-white/70">{{ $s->videos_count }}</td>
                            <td class="px-4 py-3 text-end whitespace-nowrap">
                                <a href="{{ route('admin.courses.sessions.videos', [$course, $s]) }}" class="text-emerald-200 hover:underline">فيديوهات</a>
                                <a href="{{ route('admin.courses.sessions.edit', [$course, $s]) }}" class="ms-3 text-emerald-200 hover:underline">تعديل</a>
                                <form method="post" action="{{ route('admin.courses.sessions.destroy', [$course, $s]) }}" class="inline ms-3" onsubmit="return confirm('حذف هذه الجلسة؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-300 hover:underline">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-white/55">لا توجد جلسات بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection


