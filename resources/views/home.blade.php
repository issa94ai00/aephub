@extends('layouts.site')

@section('content')
            <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-6 sm:pt-10 pb-10">
                @if ($carouselSlides->isNotEmpty())
                    <div
                        data-hero-carousel
                        class="hero-carousel reveal"
                        dir="ltr"
                        data-carousel-dot-template="{{ e(__('site.carousel.dot_aria_js')) }}"
                    >
                        <h1 class="sr-only">{{ __('site.home.hero_sr') }}</h1>
                        <div class="hero-carousel__track" data-carousel-track style="transform: translateX(0%);">
                            @foreach ($carouselSlides as $i => $slide)
                                <div class="hero-carousel__slide" data-carousel-slide>
                                    <img
                                        class="hero-carousel__img"
                                        src="{{ $slide['src'] }}"
                                        alt="{{ $slide['title'] }}"
                                        @if($i > 0) loading="lazy" @endif
                                        decoding="async"
                                    >
                                    <div class="hero-carousel__overlay" aria-hidden="true"></div>
                                    <div class="hero-carousel__caption">
                                        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-200/95 sm:text-sm">{{ __('site.home.hero_kicker') }}</p>
                                        <h2 class="mt-2 text-xl font-bold leading-snug tracking-tight drop-shadow-sm sm:text-3xl">{{ $slide['title'] }}</h2>
                                        @if (trim((string) ($slide['subtitle'] ?? '')) !== '')
                                            <p class="mt-2 max-w-xl text-sm font-medium leading-relaxed text-white/90 drop-shadow-sm sm:text-base">{{ $slide['subtitle'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" data-carousel-prev class="absolute left-3 top-1/2 z-10 -translate-y-1/2 rounded-full border border-slate-200/80 bg-white/95 p-2.5 text-slate-700 shadow-lg shadow-slate-900/10 ring-1 ring-white/80 backdrop-blur-md transition duration-300 hover:scale-105 hover:bg-white dark:border-slate-600 dark:bg-slate-900/90 dark:text-slate-100" aria-label="{{ __('site.carousel.prev') }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" data-carousel-next class="absolute right-3 top-1/2 z-10 -translate-y-1/2 rounded-full border border-slate-200/80 bg-white/95 p-2.5 text-slate-700 shadow-lg shadow-slate-900/10 ring-1 ring-white/80 backdrop-blur-md transition duration-300 hover:scale-105 hover:bg-white dark:border-slate-600 dark:bg-slate-900/90 dark:text-slate-100" aria-label="{{ __('site.carousel.next') }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>

                        <div class="absolute bottom-3 left-0 right-0 z-10 flex justify-center gap-2" data-carousel-dots></div>
                    </div>
                @else
                    <div class="reveal rounded-3xl border border-slate-200/70 bg-gradient-to-br from-emerald-50/90 via-white to-slate-50/90 p-8 shadow-lg shadow-slate-900/5 dark:border-slate-700/60 dark:from-slate-900/80 dark:via-slate-900/60 dark:to-slate-950/80 dark:shadow-black/20 sm:p-10">
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 sm:text-3xl">{{ __('site.home.hero_sr') }}</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ __('site.home.carousel_empty_hint') }}</p>
                    </div>
                @endif

                <div class="mt-10 grid items-stretch gap-6 lg:grid-cols-2">
                    <div class="reveal site-panel !p-5 sm:!p-7">
                        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200/60 bg-emerald-50/90 px-3 py-1.5 text-xs font-semibold text-emerald-800 shadow-sm dark:border-emerald-800/40 dark:bg-emerald-950/50 dark:text-emerald-300">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                            {{ __('site.home.snapshot') }}
                        </div>
                        <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-400">
                            {{ __('site.home.snapshot_text') }}
                        </p>
                        <div class="mt-6 grid grid-cols-3 gap-3">
                            <div class="rounded-2xl border border-slate-200/70 bg-slate-50/90 p-4 shadow-inner shadow-slate-900/5 dark:border-slate-700/60 dark:bg-slate-800/80">
                                <div class="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50">{{ $stats['courses'] }}</div>
                                <div class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('site.home.stat_courses') }}</div>
                            </div>
                            <div class="rounded-2xl border border-slate-200/70 bg-slate-50/90 p-4 shadow-inner shadow-slate-900/5 dark:border-slate-700/60 dark:bg-slate-800/80">
                                <div class="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50">{{ $stats['videos'] }}</div>
                                <div class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('site.home.stat_videos') }}</div>
                            </div>
                            <div class="rounded-2xl border border-slate-200/70 bg-slate-50/90 p-4 shadow-inner shadow-slate-900/5 dark:border-slate-700/60 dark:bg-slate-800/80">
                                <div class="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50">{{ $stats['students'] }}</div>
                                <div class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('site.home.stat_students') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="reveal site-panel !p-6 sm:!p-8">
                        <div class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ __('site.home.featured') }}</div>
                        @if($featuredCourse)
                            @if($featuredCourse->cover_image_url)
                                <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200/60 shadow-md dark:border-slate-700/60">
                                    <img src="{{ $featuredCourse->cover_image_url }}" alt="" class="h-40 w-full object-cover transition duration-500 hover:scale-105" loading="lazy" />
                                </div>
                            @endif
                            <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">{{ $featuredCourse->localized_title }}</h2>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                {{ $featuredCourse->localized_description ?: __('site.home.featured_desc_fallback') }}
                            </p>
                            <div class="mt-5 flex flex-wrap items-center gap-2 text-xs">
                                <span class="rounded-full border border-emerald-200/60 bg-emerald-50/90 px-3 py-1 font-semibold text-emerald-800 dark:border-emerald-800/45 dark:bg-emerald-950/40 dark:text-emerald-300">{{ __('site.home.instructor') }}: {{ $featuredCourse->teacher->name ?? __('site.home.not_set') }}</span>
                                <span class="rounded-full border border-slate-200/70 bg-slate-50/90 px-3 py-1 font-medium text-slate-700 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-200">{{ __('site.home.videos_n', ['count' => $featuredCourse->videos_count]) }}</span>
                                <span class="rounded-full border border-slate-200/70 bg-slate-50/90 px-3 py-1 font-medium text-slate-700 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-200">{{ __('site.home.students_n', ['count' => $featuredCourse->enrollments_count]) }}</span>
                            </div>
                            <div class="mt-6 text-lg font-bold text-emerald-700 dark:text-emerald-400">
                                {{ number_format(($featuredCourse->price_cents ?? 0) / 100, 2) }} {{ $featuredCourse->currency ?? 'SYP' }}
                            </div>
                            <a href="{{ route('courses.show', $featuredCourse) }}" class="site-btn-primary mt-4 px-5 py-2.5 text-xs">
                                {{ __('site.home.view_course') }}
                            </a>
                        @else
                            <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">{{ __('site.home.no_courses') }}</h2>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                {{ __('site.home.no_courses_hint') }}
                            </p>
                        @endif
                    </div>
                </div>
            </section>

            <section id="universities" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-16">
                <div class="reveal flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400/90">{{ __('site.universities.section_kicker') }}</p>
                        <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">{{ __('site.universities.section_title') }}</h3>
                        <p class="mt-2 max-w-2xl text-slate-600 dark:text-slate-400">{{ __('site.universities.section_sub') }}</p>
                    </div>
                </div>

                @if($universities->isEmpty())
                    <div class="reveal site-panel mt-8 py-12 text-center">
                        <p class="text-slate-600 dark:text-slate-400">{{ __('site.universities.empty_list') }}</p>
                    </div>
                @else
                    <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($universities as $uni)
                            <a
                                href="{{ route('universities.show', $uni) }}"
                                class="reveal group relative block overflow-hidden rounded-3xl border border-slate-200/70 bg-gradient-to-br from-white via-white to-sky-50/40 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-white/80 transition duration-500 hover:-translate-y-1 hover:border-sky-200/50 hover:shadow-2xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 dark:border-slate-700/60 dark:from-slate-900/95 dark:via-slate-900/85 dark:to-sky-950/30 dark:ring-slate-700/50 dark:hover:border-sky-800/40"
                            >
                                <div class="pointer-events-none absolute -bottom-10 -start-10 h-36 w-36 rounded-full bg-sky-400/10 blur-2xl transition duration-500 group-hover:bg-sky-400/20 dark:bg-sky-500/10 dark:group-hover:bg-sky-500/20" aria-hidden="true"></div>
                                <div class="relative flex items-start justify-between gap-4">
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-sky-200/50 bg-sky-50 text-sky-700 shadow-sm transition duration-300 group-hover:scale-105 dark:border-sky-800/40 dark:bg-sky-950/50 dark:text-sky-300">
                                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    </div>
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200/80 bg-white/90 text-slate-500 shadow-sm transition duration-300 group-hover:border-emerald-200/60 group-hover:bg-emerald-50 group-hover:text-emerald-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400 dark:group-hover:border-emerald-800/50 dark:group-hover:bg-emerald-950/50 dark:group-hover:text-emerald-300" aria-hidden="true">
                                        <svg class="h-4 w-4 transition duration-300 group-hover:translate-x-0.5 rtl:-scale-x-100 rtl:group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </span>
                                </div>
                                <h4 class="relative mt-5 text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">{{ $uni->localized_name }}</h4>
                                <p class="relative mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('site.universities.faculty_count', ['count' => $uni->faculties_count]) }}</p>
                                <p class="relative mt-4 inline-flex items-center gap-1 text-sm font-semibold text-emerald-700 dark:text-emerald-400">
                                    {{ __('site.universities.view_faculties') }}
                                    <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                </p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section id="courses" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-16">
                <div class="reveal flex items-end justify-between gap-4">
                    <div>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">{{ __('site.home.latest_title') }}</h3>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">{{ __('site.home.latest_sub') }}</p>
                    </div>
                </div>

                @if($latestCourses->isEmpty())
                    <div class="reveal site-panel mt-8 py-12 text-center">
                        <p class="text-slate-600 dark:text-slate-400">{{ __('site.home.no_published') }}</p>
                    </div>
                @else
                    <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($latestCourses as $course)
                            <article class="reveal course-card">
                                @if($course->cover_image_url)
                                    <div class="mb-3 -mx-1 -mt-1 overflow-hidden rounded-2xl border border-slate-200/50 dark:border-slate-700/50">
                                        <img src="{{ $course->cover_image_url }}" alt="" class="h-32 w-full object-cover transition duration-500 hover:scale-105" loading="lazy" />
                                    </div>
                                @endif
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-xs font-medium text-slate-400 dark:text-slate-500">#{{ $course->id }}</div>
                                    <div class="rounded-full border border-emerald-200/60 bg-emerald-50/90 px-2.5 py-1 text-[11px] font-bold text-emerald-800 dark:border-emerald-800/45 dark:bg-emerald-950/45 dark:text-emerald-300">
                                        {{ $course->status === 'published' ? __('site.home.status_available') : __('site.home.status_draft') }}
                                    </div>
                                </div>
                                <h4 class="mt-3 text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">{{ $course->localized_title }}</h4>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                    {{ $course->localized_description ?: __('site.home.desc_placeholder') }}
                                </p>
                                <div class="mt-4 flex flex-wrap gap-2 text-xs">
                                    <span class="rounded-full border border-slate-200/70 bg-slate-50/90 px-3 py-1 font-medium text-slate-700 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-200">{{ __('site.home.videos_n', ['count' => $course->videos_count]) }}</span>
                                    <span class="rounded-full border border-slate-200/70 bg-slate-50/90 px-3 py-1 font-medium text-slate-700 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-200">{{ __('site.home.students_n', ['count' => $course->enrollments_count]) }}</span>
                                    <span class="rounded-full border border-slate-200/70 bg-slate-50/90 px-3 py-1 font-medium text-slate-700 dark:border-slate-600 dark:bg-slate-800/80 dark:text-slate-200">{{ $course->teacher->name ?? __('site.home.not_set') }}</span>
                                </div>
                                <div class="mt-5 flex items-center justify-between gap-3">
                                    <div class="text-base font-bold text-emerald-700 dark:text-emerald-400">
                                        {{ number_format(($course->price_cents ?? 0) / 100, 2) }} {{ $course->currency ?? 'SYP' }}
                                    </div>
                                    <a href="{{ route('courses.show', $course) }}" class="site-btn-primary shrink-0 px-3 py-2 text-xs">
                                        {{ __('site.home.view_course_short') }}
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section id="why" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-16" aria-labelledby="why-heading">
                <div class="reveal mx-auto max-w-2xl text-center">
                    <p class="text-sm font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400/90">{{ __('site.home.why_kicker') }}</p>
                    <h3 id="why-heading" class="mt-3 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">
                        {{ __('site.home.why_title') }}
                    </h3>
                    <p class="mt-3 text-base leading-relaxed text-slate-600 dark:text-slate-400">
                        {{ __('site.home.why_lead') }}
                    </p>
                </div>

                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <article class="reveal group rounded-3xl border border-slate-200/70 bg-gradient-to-b from-white/95 to-slate-50/80 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-white/60 transition duration-500 hover:-translate-y-1 hover:border-emerald-200/60 hover:shadow-xl dark:border-slate-700/60 dark:from-slate-900/90 dark:to-slate-950/70 dark:ring-slate-700/40 dark:hover:border-emerald-800/40">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-emerald-200/50 bg-emerald-50 text-emerald-700 shadow-sm transition duration-300 group-hover:scale-105 group-hover:shadow-md dark:border-emerald-800/40 dark:bg-emerald-950/50 dark:text-emerald-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <h4 class="mt-5 text-base font-bold text-slate-900 dark:text-slate-50">{{ __('site.home.why_1_title') }}</h4>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ __('site.home.why_1_text') }}</p>
                    </article>

                    <article class="reveal group rounded-3xl border border-slate-200/70 bg-gradient-to-b from-white/95 to-slate-50/80 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-white/60 transition duration-500 hover:-translate-y-1 hover:border-emerald-200/60 hover:shadow-xl dark:border-slate-700/60 dark:from-slate-900/90 dark:to-slate-950/70 dark:ring-slate-700/40 dark:hover:border-emerald-800/40">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-emerald-200/50 bg-emerald-50 text-emerald-700 shadow-sm transition duration-300 group-hover:scale-105 group-hover:shadow-md dark:border-emerald-800/40 dark:bg-emerald-950/50 dark:text-emerald-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <h4 class="mt-5 text-base font-bold text-slate-900 dark:text-slate-50">{{ __('site.home.why_2_title') }}</h4>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ __('site.home.why_2_text') }}</p>
                    </article>

                    <article class="reveal group rounded-3xl border border-slate-200/70 bg-gradient-to-b from-white/95 to-slate-50/80 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-white/60 transition duration-500 hover:-translate-y-1 hover:border-emerald-200/60 hover:shadow-xl dark:border-slate-700/60 dark:from-slate-900/90 dark:to-slate-950/70 dark:ring-slate-700/40 dark:hover:border-emerald-800/40">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-emerald-200/50 bg-emerald-50 text-emerald-700 shadow-sm transition duration-300 group-hover:scale-105 group-hover:shadow-md dark:border-emerald-800/40 dark:bg-emerald-950/50 dark:text-emerald-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <h4 class="mt-5 text-base font-bold text-slate-900 dark:text-slate-50">{{ __('site.home.why_3_title') }}</h4>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ __('site.home.why_3_text') }}</p>
                    </article>

                    <article class="reveal group rounded-3xl border border-slate-200/70 bg-gradient-to-b from-white/95 to-slate-50/80 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-white/60 transition duration-500 hover:-translate-y-1 hover:border-emerald-200/60 hover:shadow-xl dark:border-slate-700/60 dark:from-slate-900/90 dark:to-slate-950/70 dark:ring-slate-700/40 dark:hover:border-emerald-800/40">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-emerald-200/50 bg-emerald-50 text-emerald-700 shadow-sm transition duration-300 group-hover:scale-105 group-hover:shadow-md dark:border-emerald-800/40 dark:bg-emerald-950/50 dark:text-emerald-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <h4 class="mt-5 text-base font-bold text-slate-900 dark:text-slate-50">{{ __('site.home.why_4_title') }}</h4>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ __('site.home.why_4_text') }}</p>
                    </article>
                </div>

                <div class="reveal mt-12 rounded-3xl border border-dashed border-emerald-200/60 bg-gradient-to-br from-emerald-50/50 via-white/60 to-sky-50/40 px-6 py-6 text-center shadow-inner sm:px-10 dark:border-emerald-800/35 dark:from-emerald-950/25 dark:via-slate-900/40 dark:to-sky-950/20">
                    <p class="text-sm font-medium leading-relaxed text-slate-800 dark:text-slate-300">
                        {!! __('site.home.why_goal_html') !!}
                    </p>
                </div>
            </section>

            <section id="faq" class="site-faq mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-24">
                <div class="reveal mb-8">
                    <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">{{ __('site.home.faq_title') }}</h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('site.home.faq_sub') }}</p>
                </div>
                <div class="grid gap-4 lg:grid-cols-2">
                    @forelse ($faqs as $faq)
                        <details class="reveal group rounded-3xl border border-slate-200/70 bg-white/75 p-5 shadow-lg shadow-slate-900/5 ring-1 ring-white/70 backdrop-blur-xl transition duration-300 open:border-emerald-200/50 open:shadow-xl dark:border-slate-700/60 dark:bg-slate-900/70 dark:ring-slate-700/35 dark:open:border-emerald-800/40 sm:p-6">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-bold text-slate-900 dark:text-slate-100">
                                <span>{{ $faq->localized_question }}</span>
                                <span class="site-faq-chevron grid h-8 w-8 shrink-0 place-items-center rounded-full border border-slate-200/80 bg-slate-50 text-slate-500 transition duration-300 group-open:border-emerald-200/60 group-open:bg-emerald-50 group-open:text-emerald-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400 dark:group-open:border-emerald-800/50 dark:group-open:bg-emerald-950/50 dark:group-open:text-emerald-300" aria-hidden="true">⌄</span>
                            </summary>
                            <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $faq->localized_answer }}</p>
                        </details>
                    @empty
                        <p class="text-sm text-slate-600 dark:text-slate-400 lg:col-span-2">{{ __('site.home.faq_empty') }}</p>
                    @endforelse
                </div>
            </section>
@endsection
