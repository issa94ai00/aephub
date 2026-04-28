@extends('layouts.site')

@section('content')
        <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-14">
            <section class="reveal site-panel">
                @if($course->cover_image_url)
                    <div class="mb-6 overflow-hidden rounded-2xl border border-slate-200/60 shadow-lg dark:border-slate-700/60">
                        <img src="{{ $course->cover_image_url }}" alt="" class="h-48 w-full object-cover transition duration-500 hover:scale-105 sm:h-56" loading="lazy" />
                    </div>
                @endif
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="max-w-3xl">
                        <p class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400/90">#{{ $course->id }} • {{ $course->status === 'published' ? __('site.course.available_now') : __('site.course.unavailable') }}</p>
                        <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 sm:text-3xl">{{ $course->localized_title }}</h1>
                        <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                            {{ $course->localized_description ?: __('site.course.desc_pending') }}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-emerald-200/60 bg-gradient-to-b from-emerald-50/95 to-emerald-100/40 px-5 py-4 text-center shadow-inner dark:border-emerald-800/45 dark:from-emerald-950/40 dark:to-emerald-950/20">
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('site.course.price') }}</div>
                        <div class="mt-1 text-xl font-bold text-emerald-700 dark:text-emerald-400">
                            {{ number_format(($course->price_cents ?? 0) / 100, 2) }} {{ $course->currency ?? 'SYP' }}
                        </div>
                    </div>
                </div>

                <div class="mt-8 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50/90 p-4 shadow-inner dark:border-slate-700/60 dark:bg-slate-800/80">
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('site.course.instructor') }}</div>
                        <div class="mt-1 text-sm font-bold text-slate-900 dark:text-slate-50">{{ $course->teacher->name ?? __('site.home.not_set') }}</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50/90 p-4 shadow-inner dark:border-slate-700/60 dark:bg-slate-800/80">
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('site.course.video_count') }}</div>
                        <div class="mt-1 text-sm font-bold text-slate-900 dark:text-slate-50">{{ $course->videos_count }}</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50/90 p-4 shadow-inner dark:border-slate-700/60 dark:bg-slate-800/80">
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('site.course.student_count') }}</div>
                        <div class="mt-1 text-sm font-bold text-slate-900 dark:text-slate-50">{{ $course->enrollments_count }}</div>
                    </div>
                </div>

                <div class="mt-8 rounded-2xl border border-dashed border-emerald-200/60 bg-emerald-50/40 p-5 dark:border-emerald-800/40 dark:bg-emerald-950/25">
                    <p class="text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                        {{ __('site.course.app_hint') }}
                    </p>
                    <a href="{{ route('android.download') }}" class="site-btn-primary mt-4 text-xs">
                        {{ __('site.course.go_download') }}
                    </a>
                </div>
            </section>

            @if($relatedCourses->isNotEmpty())
                <section class="mt-10">
                    <h2 class="mb-5 text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50">{{ __('site.course.related') }}</h2>
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($relatedCourses as $item)
                            <article class="reveal course-card">
                                <h3 class="text-base font-bold text-slate-900 dark:text-slate-50">{{ $item->localized_title }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                    {{ $item->localized_description ?: __('site.home.desc_placeholder') }}
                                </p>
                                <div class="mt-4 flex items-center justify-between gap-3">
                                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('site.home.videos_n', ['count' => $item->videos_count]) }}</span>
                                    <a href="{{ route('courses.show', $item) }}" class="site-btn-primary px-3 py-2 text-xs">{{ __('site.course.view_details') }}</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
@endsection
