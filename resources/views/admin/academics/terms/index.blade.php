@extends('admin.spa-inner')

@section('title', app()->getLocale() === 'en' ? 'Study terms' : 'الفصول الدراسية')
@section('heading', app()->getLocale() === 'en' ? 'Study terms' : 'الفصول الدراسية')
@section('subheading', $university->name . ' • ' . $faculty->name . ' • ' . ($year->year_number))

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm text-white/70">
            <span class="text-white/50">{{ app()->getLocale() === 'en' ? 'Year:' : 'السنة:' }}</span>
            <span class="font-semibold text-white">{{ $year->year_number }}</span>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.academics.universities.faculties.years.index', [$university, $faculty]) }}" class="admin-btn rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/90 hover:bg-white/10">← {{ app()->getLocale() === 'en' ? 'Years' : 'السنوات' }}</a>
            <a href="{{ route('admin.academics.universities.faculties.years.terms.create', [$university, $faculty, $year]) }}" class="admin-btn rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-900/20 hover:bg-emerald-500">
                + {{ app()->getLocale() === 'en' ? 'New term' : 'فصل جديد' }}
            </a>
        </div>
    </div>

    <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-4 py-3 text-start">#</th>
                        <th class="px-4 py-3 text-start">{{ app()->getLocale() === 'en' ? 'Term number' : 'رقم الفصل' }}</th>
                        <th class="px-4 py-3 text-start">{{ app()->getLocale() === 'en' ? 'Label' : 'اسم/وصف' }}</th>
                        <th class="px-4 py-3 text-end">{{ app()->getLocale() === 'en' ? 'Actions' : 'إجراءات' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse ($terms as $t)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-white/60">{{ $t->id }}</td>
                            <td class="px-4 py-3 font-medium text-white">{{ $t->term_number }}</td>
                            <td class="px-4 py-3 text-white/75">
                                <div>{{ $t->name ?: '—' }}</div>
                                @if(!empty($t->name_en))
                                    <div class="mt-0.5 text-xs text-white/45" dir="ltr">{{ $t->name_en }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-end whitespace-nowrap">
                                <a href="{{ route('admin.academics.universities.faculties.years.terms.edit', [$university, $faculty, $year, $t]) }}" class="text-emerald-200 hover:underline">{{ app()->getLocale() === 'en' ? 'Edit' : 'تعديل' }}</a>
                                <form method="post" action="{{ route('admin.academics.universities.faculties.years.terms.destroy', [$university, $faculty, $year, $t]) }}" class="inline ms-3" onsubmit="return confirm('{{ app()->getLocale() === 'en' ? 'Delete this term?' : 'حذف هذا الفصل؟' }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-300 hover:underline">{{ app()->getLocale() === 'en' ? 'Delete' : 'حذف' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-white/55">{{ app()->getLocale() === 'en' ? 'No terms yet.' : 'لا توجد فصول بعد.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($terms->hasPages())
            <div class="border-t border-white/10 px-4 py-3 text-xs text-white/50">
                {{ $terms->links() }}
            </div>
        @endif
    </div>
@endsection


