@extends('admin.spa-inner')

@section('title', __('admin.faq.title'))
@section('heading', __('admin.faq.heading'))
@section('subheading', __('admin.faq.subheading'))

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-white/55">{{ __('admin.faq.intro') }}</p>
        <a href="{{ route('admin.faqs.create') }}" class="admin-btn inline-flex rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-900/20 hover:bg-emerald-500">
            + {{ __('admin.faq.new') }}
        </a>
    </div>

    <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-4 py-3 text-start">{{ __('admin.faq.col_order') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.faq.col_question') }}</th>
                        <th class="px-4 py-3 text-end">{{ __('admin.faq.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse ($faqs as $f)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-white/60">{{ $f->sort_order }}</td>
                            <td class="max-w-md px-4 py-3">
                                <div class="font-medium text-white">{{ Str::limit($f->question, 120) }}</div>
                                @if($f->question_en)
                                    <div class="mt-0.5 text-xs text-white/45" dir="ltr">{{ Str::limit($f->question_en, 100) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-end whitespace-nowrap">
                                <a href="{{ route('admin.faqs.edit', $f) }}" class="text-emerald-200 hover:underline">{{ __('admin.faq.edit') }}</a>
                                <form method="post" action="{{ route('admin.faqs.destroy', $f) }}" class="ms-3 inline" onsubmit="return confirm(@json(__('admin.faq.confirm_delete')));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-300 hover:underline">{{ __('admin.faq.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-white/55">{{ __('admin.faq.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
