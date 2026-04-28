@extends('admin.spa-inner')

@section('title', __('admin.carousel.title'))
@section('heading', __('admin.carousel.heading'))
@section('subheading', __('admin.carousel.subheading'))

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-white/55">{{ __('admin.carousel.intro') }}</p>
        <a href="{{ route('admin.carousel.create') }}" class="admin-btn inline-flex rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-900/20 hover:bg-emerald-500">
            + {{ __('admin.carousel.new') }}
        </a>
    </div>

    <div class="admin-table-wrap overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] ring-1 ring-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/50">
                    <tr>
                        <th class="px-4 py-3 text-start">{{ __('admin.carousel.col_order') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.carousel.col_preview') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.carousel.col_title') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('admin.carousel.col_status') }}</th>
                        <th class="px-4 py-3 text-end">{{ __('admin.carousel.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse ($slides as $s)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-white/60">{{ $s->sort_order }}</td>
                            <td class="px-4 py-3">
                                @php $src = $s->resolvedImageUrl(); @endphp
                                @if($src !== '')
                                    <button type="button" class="group block" onclick="window.open(@json($src), '_blank', 'noopener,noreferrer')" title="{{ __('admin.carousel.open_image') }}">
                                        <img src="{{ $src }}" alt="" class="h-14 w-24 rounded-lg border border-white/10 object-cover transition group-hover:ring-2 group-hover:ring-emerald-400/40" loading="lazy" decoding="async" />
                                    </button>
                                @else
                                    <span class="text-white/35">—</span>
                                @endif
                            </td>
                            <td class="max-w-[220px] px-4 py-3">
                                <div class="font-medium text-white">{{ Str::limit($s->title, 80) }}</div>
                                @if($s->title_en)
                                    <div class="mt-0.5 text-xs text-white/45" dir="ltr">{{ Str::limit($s->title_en, 60) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($s->is_active)
                                    <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs text-emerald-100">{{ __('admin.carousel.status_on') }}</span>
                                @else
                                    <span class="rounded-full bg-white/10 px-2 py-0.5 text-xs text-white/55">{{ __('admin.carousel.status_off') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-end whitespace-nowrap">
                                <a href="{{ route('admin.carousel.edit', $s) }}" class="text-emerald-200 hover:underline">{{ __('admin.carousel.edit') }}</a>
                                <form method="post" action="{{ route('admin.carousel.destroy', $s) }}" class="ms-3 inline" onsubmit="return confirm(@json(__('admin.carousel.confirm_delete')));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-300 hover:underline">{{ __('admin.carousel.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-white/55">{{ __('admin.carousel.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

