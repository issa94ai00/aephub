@extends('layouts.site')

@section('content')
        <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-14">
            <section class="reveal site-panel">
                <div class="max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400/90">{{ __('site.android.kicker') }}</p>
                    <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 sm:text-3xl">{{ __('site.android.title') }}</h1>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        {{ __('site.android.lead') }}
                    </p>
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50/90 p-5 shadow-inner transition duration-300 hover:border-emerald-200/50 dark:border-slate-700/60 dark:bg-slate-800/80">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-b from-amber-500 to-orange-600 text-sm font-bold text-white shadow-md shadow-orange-900/20">1</div>
                        <h2 class="mt-4 text-sm font-bold text-slate-900 dark:text-slate-50">{{ __('site.android.step1_title') }}</h2>
                        <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">{{ __('site.android.step1_text') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50/90 p-5 shadow-inner transition duration-300 hover:border-emerald-200/50 dark:border-slate-700/60 dark:bg-slate-800/80">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-b from-amber-500 to-orange-600 text-sm font-bold text-white shadow-md shadow-orange-900/20">2</div>
                        <h2 class="mt-4 text-sm font-bold text-slate-900 dark:text-slate-50">{{ __('site.android.step2_title') }}</h2>
                        <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">{{ __('site.android.step2_text') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50/90 p-5 shadow-inner transition duration-300 hover:border-emerald-200/50 dark:border-slate-700/60 dark:bg-slate-800/80">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-b from-amber-500 to-orange-600 text-sm font-bold text-white shadow-md shadow-orange-900/20">3</div>
                        <h2 class="mt-4 text-sm font-bold text-slate-900 dark:text-slate-50">{{ __('site.android.step3_title') }}</h2>
                        <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">{{ __('site.android.step3_text') }}</p>
                    </div>
                </div>

                <div class="mt-10 flex flex-wrap items-center gap-3">
                    @if($androidApkUrl !== '')
                        <a href="{{ $androidApkUrl }}" class="site-btn-primary px-6 py-3 text-sm">
                            {{ __('site.android.download_apk') }}
                        </a>
                    @else
                        <span class="inline-flex items-center rounded-2xl border border-amber-200/60 bg-amber-50/80 px-4 py-3 text-sm text-amber-900 shadow-sm dark:border-amber-800/40 dark:bg-amber-950/30 dark:text-amber-200">
                            {{ __('site.android.apk_missing') }}
                        </span>
                    @endif
                    <a href="{{ url('/#courses') }}" class="site-btn-secondary px-6 py-3 text-sm">
                        {{ __('site.android.browse_courses') }}
                    </a>
                </div>
            </section>
        </div>
@endsection
