@extends('admin.spa-inner')

@section('title', app()->getLocale() === 'en' ? 'Study years' : 'السنوات الدراسية')
@section('heading', app()->getLocale() === 'en' ? 'Study years' : 'السنوات الدراسية')
@section('subheading', $university->name . ' • ' . $faculty->name)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm text-white/70">
            <span class="text-white/50">{{ app()->getLocale() === 'en' ? 'Faculty:' : 'الكلية:' }}</span>
            <span class="font-semibold text-white">{{ $faculty->name }}</span>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.academics.universities.faculties.index', $university) }}" class="admin-btn rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/90 hover:bg-white/10">← {{ app()->getLocale() === 'en' ? 'Faculties' : 'الكليات' }}</a>
            <a href="{{ route('admin.academics.universities.faculties.years.create', [$university, $faculty]) }}" class="admin-btn rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-900/20 hover:bg-emerald-500">
                + {{ app()->getLocale() === 'en' ? 'New year' : 'سنة جديدة' }}
            </a>
        </div>
    </div>

    <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-4 py-3 text-start">#</th>
                        <th class="px-4 py-3 text-start">{{ app()->getLocale() === 'en' ? 'Year number' : 'رقم السنة' }}</th>
                        <th class="px-4 py-3 text-start">{{ app()->getLocale() === 'en' ? 'Label' : 'اسم/وصف' }}</th>
                        <th class="px-4 py-3 text-end">{{ app()->getLocale() === 'en' ? 'Actions' : 'إجراءات' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse ($years as $y)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-white/60">{{ $y->id }}</td>
                            <td class="px-4 py-3 font-medium text-white">{{ $y->year_number }}</td>
                            <td class="px-4 py-3 text-white/75">
                                <div>{{ $y->name ?: '—' }}</div>
                                @if(!empty($y->name_en))
                                    <div class="mt-0.5 text-xs text-white/45" dir="ltr">{{ $y->name_en }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-end whitespace-nowrap">
                                <a href="{{ route('admin.academics.universities.faculties.years.terms.index', [$university, $faculty, $y]) }}" class="text-emerald-200 hover:underline">{{ app()->getLocale() === 'en' ? 'Terms' : 'الفصول' }}</a>
                                <a href="{{ route('admin.academics.universities.faculties.years.edit', [$university, $faculty, $y]) }}" class="ms-3 text-emerald-200 hover:underline">{{ app()->getLocale() === 'en' ? 'Edit' : 'تعديل' }}</a>
                                <form method="post" action="{{ route('admin.academics.universities.faculties.years.destroy', [$university, $faculty, $y]) }}" class="inline ms-3" onsubmit="return confirm('{{ app()->getLocale() === 'en' ? 'Delete this year?' : 'حذف هذه السنة؟' }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-300 hover:underline">{{ app()->getLocale() === 'en' ? 'Delete' : 'حذف' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-white/55">{{ app()->getLocale() === 'en' ? 'No years yet.' : 'لا توجد سنوات بعد.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($years->hasPages())
            <div class="border-t border-white/10 px-4 py-3 text-xs text-white/50">
                {{ $years->links() }}
            </div>
        @endif
    </div>
@endsection


