<form
    method="post"
    action="{{ route('subscription.register.store') }}"
    class="mt-4 space-y-4"
    @if($accountType === 'student' && isset($pickerConfig))
        x-data="registrationAcademicPicker(@js($pickerConfig))"
    @endif
>
    @csrf
    <input type="hidden" name="account_type" value="{{ $accountType }}">

    <div>
        <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('site.form.full_name') }}</label>
        <input name="name" value="{{ old('account_type') === $accountType ? old('name') : '' }}" required autocomplete="name" class="site-input" />
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('site.form.email') }}</label>
        <input type="email" name="email" value="{{ old('account_type') === $accountType ? old('email') : '' }}" required autocomplete="email" dir="ltr" class="site-input" />
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('site.form.phone') }}</label>
        <input type="tel" name="phone" value="{{ old('account_type') === $accountType ? old('phone') : '' }}" required autocomplete="tel" dir="ltr" placeholder="{{ __('site.form.phone_placeholder') }}" class="site-input" />
        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-500">{{ __('site.form.phone_hint') }}</p>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('site.form.password') }}</label>
            <input type="password" name="password" required autocomplete="new-password" class="site-input" />
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('site.form.password_confirmation') }}</label>
            <input type="password" name="password_confirmation" required autocomplete="new-password" class="site-input" />
        </div>
    </div>
    @if($accountType === 'student')
        <p class="text-[11px] leading-relaxed text-slate-600 dark:text-slate-400">{{ __('site.registration_page.academic_edit_hint') }}</p>
        @if(isset($universities) && $universities->isEmpty())
            <div class="rounded-xl border border-amber-200/80 bg-amber-50/80 px-3 py-2 text-xs text-amber-950 dark:border-amber-800/50 dark:bg-amber-950/30 dark:text-amber-100">
                {{ __('site.registration_page.universities_empty') }}
            </div>
        @endif

        <template x-if="fetchError">
            <p class="rounded-lg border border-rose-200/80 bg-rose-50/90 px-3 py-2 text-xs text-rose-900 dark:border-rose-800/50 dark:bg-rose-950/40 dark:text-rose-100" x-text="fetchError"></p>
        </template>

        <div class="grid gap-3 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('site.form.university') }}</label>
                <select
                    x-model="universityId"
                    @change="loadFaculties()"
                    class="site-input"
                    :disabled="!hasUniversities"
                    :required="hasUniversities"
                >
                    <option value="">{{ __('site.form.select_university') }}</option>
                    @foreach(($universities ?? collect()) as $uni)
                        <option value="{{ $uni->id }}">{{ $uni->localized_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('site.form.faculty') }}</label>
                <select
                    x-model="facultyId"
                    @change="loadYears()"
                    class="site-input"
                    :disabled="!universityId || loadingFaculties"
                    :required="hasUniversities"
                    x-effect="patchSelectFromModel($el, facultyOptions, labels.select_faculty, 'facultyId')"
                ></select>
                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-500" x-show="loadingFaculties" x-cloak>{{ __('site.form.loading') }}</p>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('site.form.study_year') }}</label>
                <select
                    x-model="studyYearId"
                    @change="loadTerms()"
                    class="site-input"
                    :disabled="!facultyId || loadingYears"
                    :required="hasUniversities"
                    x-effect="patchSelectFromModel($el, yearOptions, labels.select_year, 'studyYearId')"
                ></select>
                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-500" x-show="loadingYears" x-cloak>{{ __('site.form.loading') }}</p>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('site.form.study_term') }}</label>
                <select
                    name="study_term_id"
                    x-model="studyTermId"
                    class="site-input"
                    :disabled="!studyYearId || loadingTerms"
                    :required="hasUniversities"
                    x-effect="patchSelectFromModel($el, termOptions, labels.select_term, 'studyTermId')"
                ></select>
                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-500" x-show="loadingTerms" x-cloak>{{ __('site.form.loading') }}</p>
            </div>
        </div>
    @endif

    <div class="rounded-xl border border-slate-200/80 bg-white/60 p-4 dark:border-slate-600/60 dark:bg-slate-900/30">
        <label class="flex cursor-pointer items-start gap-3">
            <input type="checkbox" name="terms_accepted" value="1" class="mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800" {{ old('account_type') === $accountType && old('terms_accepted') ? 'checked' : '' }} required />
            <span class="text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                {{ __('registration.terms_agree_prefix') }}
                <a href="{{ route('legal.privacy-terms') }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-emerald-700 underline decoration-emerald-700/30 underline-offset-2 hover:text-emerald-800 dark:text-emerald-400 dark:decoration-emerald-400/30 dark:hover:text-emerald-300">{{ __('registration.terms_link_label') }}</a>{{ __('registration.terms_agree_suffix') }}
            </span>
        </label>
    </div>

    <button
        type="submit"
        class="site-btn-primary text-xs"
        @if($accountType === 'student')
            :disabled="academicSubmitBlocked()"
        @endif
    >
        {{ $submitLabel }}
    </button>
</form>
