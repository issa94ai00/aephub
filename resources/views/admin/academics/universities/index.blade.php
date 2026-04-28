@extends('admin.spa-inner')

@section('title', __('admin.nav.academics'))
@section('heading', __('admin.nav.academics'))
@section('subheading', __('admin.nav.academics'))

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.academics.universities.create') }}" class="admin-btn rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-900/20 hover:bg-emerald-500">
            + {{ app()->getLocale() === 'en' ? 'New university' : 'جامعة جديدة' }}
        </a>
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
                    @forelse ($universities as $u)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-white/60">{{ $u->id }}</td>
                            <td class="px-4 py-3 text-white">
                                <div class="font-medium">{{ $u->name }}</div>
                                @if(!empty($u->name_en))
                                    <div class="mt-0.5 text-xs text-white/45" dir="ltr">{{ $u->name_en }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-end whitespace-nowrap">
                                <a href="{{ route('admin.academics.universities.faculties.index', $u) }}" class="text-emerald-200 hover:underline">{{ app()->getLocale() === 'en' ? 'Faculties' : 'الكليات' }}</a>
                                <a href="{{ route('admin.academics.universities.edit', $u) }}" class="ms-3 text-emerald-200 hover:underline">{{ app()->getLocale() === 'en' ? 'Edit' : 'تعديل' }}</a>
                                <form method="post" action="{{ route('admin.academics.universities.destroy', $u) }}" class="inline ms-3" onsubmit="return confirm('{{ app()->getLocale() === 'en' ? 'Delete this university?' : 'حذف هذه الجامعة؟' }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-300 hover:underline">{{ app()->getLocale() === 'en' ? 'Delete' : 'حذف' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-white/55">{{ app()->getLocale() === 'en' ? 'No universities yet.' : 'لا توجد جامعات بعد.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($universities->hasPages())
            <div class="border-t border-white/10 px-4 py-3 text-xs text-white/50">
                {{ $universities->links() }}
            </div>
        @endif
    </div>
@endsection


