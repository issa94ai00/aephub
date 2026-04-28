@extends('layouts.site')

@section('content')
        @php
            $selectedType = old('account_type', 'student');
        @endphp
        <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-14">
            <section class="reveal site-panel" x-data="{ tab: @js($selectedType) }">
                <div class="max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400/90">{{ __('site.registration_page.kicker') }}</p>
                    <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 sm:text-3xl">{{ __('site.registration_page.title') }}</h1>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        {{ __('site.registration_page.lead') }}
                    </p>
                </div>

                @if (session('status'))
                    <div class="mt-6 rounded-2xl border border-emerald-200/50 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-900 shadow-sm dark:border-emerald-800/40 dark:bg-emerald-950/35 dark:text-emerald-200">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-6 rounded-2xl border border-rose-200/50 bg-rose-50/80 px-4 py-3 text-sm text-rose-900 shadow-sm dark:border-rose-800/40 dark:bg-rose-950/35 dark:text-rose-200">
                        <ul class="list-disc space-y-1 ps-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-8">
                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50/80 p-1.5 dark:border-slate-700/60 dark:bg-slate-800/50">
                        <div class="grid grid-cols-2 gap-1.5">
                            <button
                                type="button"
                                @click="tab = 'student'"
                                :class="tab === 'student'
                                    ? 'bg-gradient-to-b from-amber-500 to-orange-600 text-white shadow-md shadow-orange-900/25'
                                    : 'bg-white/90 text-slate-700 hover:bg-white dark:bg-slate-800/90 dark:text-slate-200 dark:hover:bg-slate-800'"
                                class="rounded-xl px-3 py-2.5 text-sm font-bold transition duration-300"
                            >
                                {{ __('site.registration_page.tab_student') }}
                            </button>
                            <button
                                type="button"
                                @click="tab = 'teacher'"
                                :class="tab === 'teacher'
                                    ? 'bg-gradient-to-b from-amber-500 to-orange-600 text-white shadow-md shadow-orange-900/25'
                                    : 'bg-white/90 text-slate-700 hover:bg-white dark:bg-slate-800/90 dark:text-slate-200 dark:hover:bg-slate-800'"
                                class="rounded-xl px-3 py-2.5 text-sm font-bold transition duration-300"
                            >
                                {{ __('site.registration_page.tab_teacher') }}
                            </button>
                        </div>
                    </div>

                    <div
                        x-show="tab === 'student'"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        @unless($selectedType === 'student') style="display: none" @endunless
                        class="mt-5 rounded-2xl border border-slate-200/70 bg-slate-50/70 p-5 dark:border-slate-700/60 dark:bg-slate-800/50"
                    >
                        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-50">{{ __('site.registration_page.panel_student_title') }}</h2>
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('site.registration_page.panel_student_hint') }}</p>
                        @include('subscriptions._form', ['accountType' => 'student', 'submitLabel' => __('registration.submit_student'), 'universities' => $universities, 'pickerConfig' => $pickerConfig])
                    </div>

                    <div
                        x-show="tab === 'teacher'"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        @unless($selectedType === 'teacher') style="display: none" @endunless
                        class="mt-5 rounded-2xl border border-emerald-200/50 bg-emerald-50/50 p-5 dark:border-emerald-800/40 dark:bg-emerald-950/25"
                    >
                        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-50">{{ __('site.registration_page.panel_teacher_title') }}</h2>
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('site.registration_page.panel_teacher_hint') }}</p>
                        @include('subscriptions._form', ['accountType' => 'teacher', 'submitLabel' => __('registration.submit_teacher'), 'universities' => $universities, 'pickerConfig' => $pickerConfig])
                    </div>
                </div>
            </section>
        </div>
@endsection
