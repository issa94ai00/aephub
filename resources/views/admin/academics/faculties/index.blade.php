@extends('admin.spa-inner')

@section('title', app()->getLocale() === 'en' ? 'Faculties' : 'الكليات')
@section('heading', app()->getLocale() === 'en' ? 'Faculties' : 'الكليات')
@section('subheading', $university->name)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm text-white/70">
            <span class="text-white/50">{{ app()->getLocale() === 'en' ? 'University:' : 'الجامعة:' }}</span>
            <span class="font-semibold text-white">{{ $university->name }}</span>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.academics.universities.index') }}" class="admin-btn rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/90 hover:bg-white/10">← {{ app()->getLocale() === 'en' ? 'Universities' : 'الجامعات' }}</a>
            <a href="{{ route('admin.academics.universities.faculties.create', $university) }}" class="admin-btn rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-900/20 hover:bg-emerald-500">
                + {{ app()->getLocale() === 'en' ? 'New faculty' : 'كلية جديدة' }}
            </a>
        </div>
    </div>

    <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-4 py-3 text-start">#</th>
                        <th class="px-4 py-3 text-start">{{ app()->getLocale() === 'en' ? 'Name' : 'الاسم' }}</th>
                        <th class="px-4 py-3 text-end">{{ app()->getLocale() === 'en' ? 'Actions' : 'إجراءات' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse ($faculties as $f)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-white/60">{{ $f->id }}</td>
                            <td class="px-4 py-3 text-white">
                                <div class="font-medium">{{ $f->name }}</div>
                                @if(!empty($f->name_en))
                                    <div class="mt-0.5 text-xs text-white/45" dir="ltr">{{ $f->name_en }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-end whitespace-nowrap">
                                <a href="{{ route('admin.academics.universities.faculties.years.index', [$university, $f]) }}" class="text-emerald-200 hover:underline">{{ app()->getLocale() === 'en' ? 'Years' : 'السنوات' }}</a>
                                <a href="{{ route('admin.academics.universities.faculties.edit', [$university, $f]) }}" class="ms-3 text-emerald-200 hover:underline">{{ app()->getLocale() === 'en' ? 'Edit' : 'تعديل' }}</a>
                                <form method="post" action="{{ route('admin.academics.universities.faculties.destroy', [$university, $f]) }}" class="inline ms-3" onsubmit="return confirm('{{ app()->getLocale() === 'en' ? 'Delete this faculty?' : 'حذف هذه الكلية؟' }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-300 hover:underline">{{ app()->getLocale() === 'en' ? 'Delete' : 'حذف' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-white/55">{{ app()->getLocale() === 'en' ? 'No faculties yet.' : 'لا توجد كليات بعد.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($faculties->hasPages())
            <div class="border-t border-white/10 px-4 py-3 text-xs text-white/50">
                {{ $faculties->links() }}
            </div>
        @endif
    </div>
@endsection


